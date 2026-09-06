<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetGroupController extends Controller
{
    public function index(): View
    {
        return view('admin.referensi.kelompok-barang', ['items' => AssetGroup::withCount('assetCodes')->orderBy('kode')->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:asset_groups,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        AssetGroup::create($validated + ['is_active' => true]);

        return back()->with('status', 'Kelompok barang ditambahkan.');
    }

    public function update(Request $request, AssetGroup $assetGroup): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:asset_groups,kode,'.$assetGroup->id],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $assetGroup->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Kelompok barang diperbarui.');
    }

    public function destroy(AssetGroup $assetGroup): RedirectResponse
    {
        if ($assetGroup->assetCodes()->exists()) {
            return back()->withErrors(['asset_group' => 'Masih dipakai kode aset, tidak bisa dihapus.']);
        }

        $assetGroup->delete();

        return back()->with('status', 'Kelompok barang dihapus.');
    }
}
