<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Kegiatan::query()->with(['kampung', 'bidangKegiatan']);

        if ($kampungId = $request->query('kampung_id')) {
            $query->where('kampung_id', $kampungId);
        }

        if ($tahun = $request->query('tahun_anggaran')) {
            $query->where('tahun_anggaran', $tahun);
        }

        return $this->ok($query->orderBy('nama_kegiatan')->paginate($request->integer('per_page', 100))->items());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kampung_id' => ['required', 'integer', 'exists:kampungs,id'],
            'bidang_kegiatan_id' => ['required', 'integer', 'exists:bidang_kegiatan,id'],
            'nama_kegiatan' => ['required', 'string', 'max:200'],
            'tahun_anggaran' => ['required', 'integer', 'digits:4'],
            'pagu_total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        return $this->ok(Kegiatan::create($validator->validated()), status: 201);
    }

    public function show(Kegiatan $kegiatan)
    {
        return $this->ok($kegiatan->load(['kampung', 'bidangKegiatan', 'paguRekening.kodeRekening']));
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $validator = Validator::make($request->all(), [
            'nama_kegiatan' => ['sometimes', 'string', 'max:200'],
            'pagu_total' => ['sometimes', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $kegiatan->update($validator->validated());

        return $this->ok($kegiatan->fresh());
    }

    /** Set/replace pagu per kode rekening untuk kegiatan ini — dasar KF-08. */
    public function setPagu(Request $request, Kegiatan $kegiatan)
    {
        $validator = Validator::make($request->all(), [
            'pagu' => ['required', 'array', 'min:1'],
            'pagu.*.kode_rekening_id' => ['required', 'integer', 'exists:kode_rekening,id'],
            'pagu.*.pagu_anggaran' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        foreach ($validator->validated()['pagu'] as $item) {
            $kegiatan->paguRekening()->updateOrCreate(
                ['kode_rekening_id' => $item['kode_rekening_id']],
                ['pagu_anggaran' => $item['pagu_anggaran']]
            );
        }

        return $this->ok($kegiatan->paguRekening()->with('kodeRekening')->get());
    }
}
