<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kampung;
use Illuminate\Http\Request;

class KampungController extends Controller
{
    public function index()
    {
        return view('admin.kampung.index', [
            'kampungList' => Kampung::orderBy('nama_kampung')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_kampung' => ['required', 'string', 'max:15', 'unique:kampungs,kode_kampung'],
            'nama_kampung' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
        ]);

        Kampung::create($validated);

        return back()->with('status', 'Kampung berhasil ditambahkan.');
    }

    public function update(Request $request, Kampung $kampung)
    {
        $validated = $request->validate([
            'nama_kampung' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'status_aktif' => ['sometimes', 'boolean'],
        ]);

        $validated['status_aktif'] = $request->boolean('status_aktif');

        $kampung->update($validated);

        return back()->with('status', 'Kampung berhasil diperbarui.');
    }
}
