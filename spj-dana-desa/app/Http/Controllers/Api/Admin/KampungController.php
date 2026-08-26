<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kampung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** KF-17 — modul pengelolaan master data (admin only, digerbangi middleware role:admin). */
class KampungController extends Controller
{
    public function index(Request $request)
    {
        $query = Kampung::query();

        if ($request->boolean('hanya_aktif')) {
            $query->where('status_aktif', true);
        }

        return $this->ok($query->orderBy('nama_kampung')->paginate($request->integer('per_page', 100))->items());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_kampung' => ['required', 'string', 'max:15', 'unique:kampungs,kode_kampung'],
            'nama_kampung' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'status_aktif' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        return $this->ok(Kampung::create($validator->validated()), status: 201);
    }

    public function show(Kampung $kampung)
    {
        return $this->ok($kampung);
    }

    public function update(Request $request, Kampung $kampung)
    {
        $validator = Validator::make($request->all(), [
            'kode_kampung' => ['sometimes', 'string', 'max:15', 'unique:kampungs,kode_kampung,'.$kampung->id],
            'nama_kampung' => ['sometimes', 'string', 'max:100'],
            'kecamatan' => ['sometimes', 'string', 'max:100'],
            'status_aktif' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $kampung->update($validator->validated());

        return $this->ok($kampung->fresh());
    }

    /** Nonaktifkan (soft delete) — bukan hapus permanen, sesuai Bab IV.4. */
    public function destroy(Kampung $kampung)
    {
        $kampung->update(['status_aktif' => false]);
        $kampung->delete();

        return $this->ok(['message' => 'Kampung dinonaktifkan.']);
    }
}
