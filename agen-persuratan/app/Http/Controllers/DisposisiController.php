<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Disposisi;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisposisiController extends Controller
{
    /** Pimpinan/admin membuat disposisi atas surat masuk. */
    public function store(Request $request, SuratMasuk $suratMasuk)
    {
        $data = $request->validate([
            'kepada_user_id' => ['required', 'exists:users,id', 'different:'.$request->user()->id],
            'instruksi' => ['required', 'array', 'min:1'],
            'instruksi.*' => [Rule::in(Disposisi::INSTRUKSI)],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'batas_waktu' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'instruksi.required' => 'Pilih minimal satu instruksi disposisi.',
            'kepada_user_id.required' => 'Pilih penerima disposisi.',
        ]);

        $disposisi = $suratMasuk->disposisi()->create([
            ...$data,
            'dari_user_id' => $request->user()->id,
            'status' => 'menunggu',
        ]);
        $suratMasuk->update(['status' => 'didisposisi']);

        AuditLog::catat('disposisi', $suratMasuk, ['kepada' => $disposisi->kepada_user_id, 'instruksi' => $data['instruksi']]);

        return back()->with('sukses', 'Disposisi berhasil dibuat.');
    }

    /** Penerima (atau pimpinan/admin) memperbarui status tindak lanjut. */
    public function update(Request $request, Disposisi $disposisi)
    {
        $user = $request->user();
        abort_unless($disposisi->kepada_user_id === $user->id || $user->punyaPeran('admin', 'pimpinan'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['diproses', 'selesai'])],
            'tanggapan' => ['nullable', 'string', 'max:2000'],
        ]);

        $disposisi->update([
            ...$data,
            'selesai_at' => $data['status'] === 'selesai' ? now() : null,
        ]);

        $surat = $disposisi->suratMasuk;
        if ($surat->disposisi()->where('status', '!=', 'selesai')->doesntExist()) {
            $surat->update(['status' => 'selesai']);
        }

        AuditLog::catat('tindak_lanjut', $surat, ['disposisi' => $disposisi->id, 'status' => $data['status']]);

        return back()->with('sukses', 'Status disposisi diperbarui.');
    }
}
