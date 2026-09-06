<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        return view('admin.referensi.penyedia', ['items' => Vendor::withCount('priceSurveys')->orderBy('nama')->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['nullable', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'kecamatan' => ['nullable', 'string', 'max:60'],
            'kelurahan' => ['nullable', 'string', 'max:60'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'kontak_person' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
        ]);

        Vendor::create($validated + ['is_active' => true]);

        return back()->with('status', 'Penyedia/toko ditambahkan.');
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['nullable', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'kecamatan' => ['nullable', 'string', 'max:60'],
            'kelurahan' => ['nullable', 'string', 'max:60'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'kontak_person' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $vendor->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Penyedia/toko diperbarui.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $vendor->delete();

        return back()->with('status', 'Penyedia/toko dihapus.');
    }
}
