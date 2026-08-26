<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidangKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BidangKegiatanController extends Controller
{
    public function index(Request $request)
    {
        $query = BidangKegiatan::query();

        if ($tahun = $request->query('tahun_anggaran')) {
            $query->where('tahun_anggaran', $tahun);
        }

        return $this->ok($query->orderBy('kode')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => ['required', 'string', 'max:10'],
            'nama_bidang' => ['required', 'string', 'max:150'],
            'tahun_anggaran' => ['required', 'integer', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        return $this->ok(BidangKegiatan::create($validator->validated()), status: 201);
    }
}
