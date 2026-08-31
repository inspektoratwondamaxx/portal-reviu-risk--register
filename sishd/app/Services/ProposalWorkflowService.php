<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\ProposalStatus;
use App\Models\Asb;
use App\Models\AsbFormula;
use App\Models\AsbVariable;
use App\Models\Hspk;
use App\Models\HspkComponent;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\ProposalReview;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Alur usulan OPD (Bab 11-12 kajian): OPD tidak boleh mengubah master langsung. Semua usulan lewat
 * proposals -> menunggu_verifikasi -> (setuju|revisi|tolak). Saat disetujui, data_usulan
 * dimaterialisasi jadi baris master (ssh_items/sbu_items/hspk/asb) berstatus aktif, dan bila itu
 * perubahan harga item yang sudah ada, price history + kaskade HSPK/ASB otomatis jalan lewat
 * Concerns\HasPriceHistory pada model master itu sendiri.
 */
class ProposalWorkflowService
{
    public function __construct(
        private readonly DuplicateDetectionService $duplicateDetectionService,
        private readonly HspkCalculationService $hspkCalculationService,
        private readonly AsbCalculationService $asbCalculationService,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $items  setiap elemen: ['item_type'=>, 'existing_item_id'=>?, 'data_usulan'=>[]]
     */
    public function createProposal(int $opdId, string $jenisUsulan, string $tipePerubahan, ?string $alasan, array $items, ?User $creator = null): Proposal
    {
        $creator ??= Auth::user();

        return DB::transaction(function () use ($opdId, $jenisUsulan, $tipePerubahan, $alasan, $items, $creator) {
            $proposal = Proposal::create([
                'nomor_usulan' => Proposal::generateNomor($jenisUsulan),
                'opd_id' => $opdId,
                'jenis_usulan' => $jenisUsulan,
                'tipe_perubahan' => $tipePerubahan,
                'status' => ProposalStatus::MenungguVerifikasi,
                'alasan_usulan' => $alasan,
                'diajukan_at' => now(),
                'created_by' => $creator?->id,
            ]);

            foreach ($items as $itemInput) {
                $kemiripan = null;

                if ($jenisUsulan === 'ssh' && ! empty($itemInput['data_usulan']['uraian'])) {
                    $similar = $this->duplicateDetectionService->findSimilar(
                        $itemInput['data_usulan']['uraian'],
                        $itemInput['data_usulan']['merek'] ?? null,
                    );
                    $kemiripan = $similar->isNotEmpty() ? $similar->all() : null;
                }

                ProposalItem::create([
                    'proposal_id' => $proposal->id,
                    'item_type' => $jenisUsulan,
                    'existing_item_id' => $itemInput['existing_item_id'] ?? null,
                    'data_usulan' => $itemInput['data_usulan'],
                    'kemiripan' => $kemiripan,
                ]);
            }

            return $proposal->load('items');
        });
    }

    public function review(Proposal $proposal, User $reviewer, string $keputusan, ?string $catatan = null, string $tahapan = 'verifikator'): Proposal
    {
        if (! in_array($keputusan, ['setuju', 'revisi', 'tolak'], true)) {
            throw new InvalidArgumentException("Keputusan tidak valid: {$keputusan}");
        }

        return DB::transaction(function () use ($proposal, $reviewer, $keputusan, $catatan, $tahapan) {
            ProposalReview::create([
                'proposal_id' => $proposal->id,
                'reviewer_id' => $reviewer->id,
                'tahapan' => $tahapan,
                'keputusan' => $keputusan,
                'catatan' => $catatan,
                'reviewed_at' => now(),
            ]);

            $status = match ($keputusan) {
                'setuju' => ProposalStatus::Disetujui,
                'revisi' => ProposalStatus::Revisi,
                'tolak' => ProposalStatus::Ditolak,
            };

            $proposal->forceFill([
                'status' => $status,
                'catatan_verifikasi' => $catatan,
                'verifikator_id' => $reviewer->id,
                'verified_at' => now(),
            ])->save();

            if ($keputusan === 'setuju') {
                foreach ($proposal->items as $item) {
                    $this->materialize($proposal, $item);
                }
            }

            return $proposal->refresh();
        });
    }

    /** OPD mengajukan ulang usulan yang diminta revisi (Bab 11 kajian: DIAJUKAN -> DITOLAK -> PERBAIKAN -> DIAJUKAN). */
    public function resubmit(Proposal $proposal): Proposal
    {
        $proposal->forceFill(['status' => ProposalStatus::MenungguVerifikasi, 'diajukan_at' => now()])->save();

        return $proposal->refresh();
    }

    private function materialize(Proposal $proposal, ProposalItem $item): void
    {
        if ($proposal->tipe_perubahan === 'nonaktif') {
            $this->materializeNonaktif($item);

            return;
        }

        $data = $item->data_usulan;
        $data['status'] = ItemStatus::Aktif->value;
        $data['is_active'] = true;
        $data['opd_id'] = $proposal->opd_id;
        $data['updated_by'] = $proposal->verifikator_id;

        $createdId = match ($item->item_type) {
            'ssh' => $this->materializeSsh($item, $data, $proposal),
            'sbu' => $this->materializeSbu($item, $data, $proposal),
            'hspk' => $this->materializeHspk($item, $data, $proposal),
            'asb' => $this->materializeAsb($item, $data, $proposal),
            default => null,
        };

        if ($createdId) {
            $item->forceFill(['created_item_id' => $createdId])->save();
        }
    }

    /** Usulan nonaktifkan (tipe_perubahan=nonaktif): hanya matikan status, data lain tidak disentuh. */
    private function materializeNonaktif(ProposalItem $item): void
    {
        $model = $item->existingItem();

        if (! $model) {
            return;
        }

        $model->forceFill(['status' => ItemStatus::Nonaktif->value, 'is_active' => false])->save();
        $item->forceFill(['created_item_id' => $model->getKey()])->save();
    }

    private function materializeSsh(ProposalItem $item, array $data, Proposal $proposal): int
    {
        unset($data['components'], $data['variables'], $data['formula']);

        if ($item->existing_item_id) {
            $ssh = SshItem::findOrFail($item->existing_item_id);
            $ssh->pendingDasarPerubahan = "Usulan OPD {$proposal->nomor_usulan}";
            $ssh->fill(array_diff_key($data, ['created_by' => true]))->save();

            return $ssh->id;
        }

        $data['created_by'] = $proposal->created_by;
        $data['kode_barang'] ??= 'SSH-'.now()->format('ymd').'-'.str_pad((string) (SshItem::max('id') + 1), 4, '0', STR_PAD_LEFT);

        return SshItem::create($data)->id;
    }

    private function materializeSbu(ProposalItem $item, array $data, Proposal $proposal): int
    {
        unset($data['components'], $data['variables'], $data['formula']);

        if ($item->existing_item_id) {
            $sbu = SbuItem::findOrFail($item->existing_item_id);
            $sbu->pendingDasarPerubahan = "Usulan OPD {$proposal->nomor_usulan}";
            $sbu->fill(array_diff_key($data, ['created_by' => true]))->save();

            return $sbu->id;
        }

        $data['created_by'] = $proposal->created_by;
        $data['kode'] ??= 'SBU-'.now()->format('ymd').'-'.str_pad((string) (SbuItem::max('id') + 1), 4, '0', STR_PAD_LEFT);

        return SbuItem::create($data)->id;
    }

    private function materializeHspk(ProposalItem $item, array $data, Proposal $proposal): int
    {
        $components = $data['components'] ?? [];
        unset($data['components'], $data['variables'], $data['formula']);

        if ($item->existing_item_id) {
            $hspk = Hspk::findOrFail($item->existing_item_id);
            $hspk->fill(array_diff_key($data, ['created_by' => true]))->save();
        } else {
            $data['created_by'] = $proposal->created_by;
            $data['kode'] ??= 'HSPK-'.now()->format('ymd').'-'.str_pad((string) (Hspk::max('id') + 1), 4, '0', STR_PAD_LEFT);
            $hspk = Hspk::create($data);
        }

        foreach ($components as $component) {
            HspkComponent::create(array_merge($component, ['hspk_id' => $hspk->id]));
        }

        if (! empty($components)) {
            $this->hspkCalculationService->recalculate($hspk, "Usulan OPD {$proposal->nomor_usulan}");
        }

        return $hspk->id;
    }

    private function materializeAsb(ProposalItem $item, array $data, Proposal $proposal): int
    {
        $variables = $data['variables'] ?? [];
        $formulaExpr = $data['formula'] ?? null;
        unset($data['components'], $data['variables'], $data['formula']);

        if ($item->existing_item_id) {
            $asb = Asb::findOrFail($item->existing_item_id);
            $asb->fill(array_diff_key($data, ['created_by' => true]))->save();
        } else {
            $data['created_by'] = $proposal->created_by;
            $data['kode'] ??= 'ASB-'.now()->format('ymd').'-'.str_pad((string) (Asb::max('id') + 1), 4, '0', STR_PAD_LEFT);
            $asb = Asb::create($data);
        }

        foreach ($variables as $variable) {
            AsbVariable::updateOrCreate(
                ['asb_id' => $asb->id, 'kode_variabel' => $variable['kode_variabel']],
                $variable
            );
        }

        if ($formulaExpr) {
            AsbFormula::updateOrCreate(['asb_id' => $asb->id], ['ekspresi' => $formulaExpr, 'created_by' => $proposal->created_by]);
        }

        if (! empty($variables) || $formulaExpr) {
            $this->asbCalculationService->recalculate($asb->fresh(['variables', 'formula']));
        }

        return $asb->id;
    }
}
