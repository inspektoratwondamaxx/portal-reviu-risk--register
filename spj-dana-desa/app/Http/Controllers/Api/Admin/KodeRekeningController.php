<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KodeRekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KodeRekeningController extends Controller
{
    public function index(Request $request)
    {
        $query = KodeRekening::query();

        if ($tahun = $request->query('tahun_anggaran')) {
            $query->where('tahun_anggaran', $tahun);
        }

        return $this->ok($query->orderBy('kode')->paginate($request->integer('per_page', 200))->items());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => ['required', 'string', 'max:20'],
            'uraian' => ['required', 'string', 'max:200'],
            'jenis_belanja' => ['required', 'in:pegawai,barang_jasa,modal,tak_terduga'],
            'tahun_anggaran' => ['required', 'integer', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $exists = KodeRekening::where('kode', $request->input('kode'))
            ->where('tahun_anggaran', $request->input('tahun_anggaran'))
            ->exists();

        if ($exists) {
            return $this->fail('Kode rekening sudah terdaftar pada tahun anggaran tersebut.', status: 422);
        }

        return $this->ok(KodeRekening::create($validator->validated()), status: 201);
    }

    public function update(Request $request, KodeRekening $kodeRekening)
    {
        $validator = Validator::make($request->all(), [
            'uraian' => ['sometimes', 'string', 'max:200'],
            'jenis_belanja' => ['sometimes', 'in:pegawai,barang_jasa,modal,tak_terduga'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $kodeRekening->update($validator->validated());

        return $this->ok($kodeRekening->fresh());
    }
}
