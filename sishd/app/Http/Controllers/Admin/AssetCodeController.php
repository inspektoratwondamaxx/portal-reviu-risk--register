<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetCode;
use App\Models\AssetGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetCodeController extends Controller
{
    public function index(): View
    {
        return view('admin.referensi.kode-aset', [
            'items' => AssetCode::with('assetGroup')->orderBy('kode')->paginate(20),
            'groups' => AssetGroup::orderBy('kode')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_group_id' => ['nullable', 'exists:asset_groups,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:asset_codes,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        AssetCode::create($validated + ['is_active' => true]);

        return back()->with('status', 'Kode aset ditambahkan.');
    }

    public function update(Request $request, AssetCode $assetCode): RedirectResponse
    {
        $validated = $request->validate([
            'asset_group_id' => ['nullable', 'exists:asset_groups,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:asset_codes,kode,'.$assetCode->id],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $assetCode->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Kode aset diperbarui.');
    }

    public function destroy(AssetCode $assetCode): RedirectResponse
    {
        if ($assetCode->sshItems()->exists()) {
            return back()->withErrors(['asset_code' => 'Masih dipakai Master SSH, tidak bisa dihapus.']);
        }

        $assetCode->delete();

        return back()->with('status', 'Kode aset dihapus.');
    }
}
