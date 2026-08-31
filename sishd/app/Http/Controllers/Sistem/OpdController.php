<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\Opd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpdController extends Controller
{
    public function index(): View
    {
        return view('sistem.opd.index', ['items' => Opd::withCount('users')->orderBy('kode')->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:opds,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'singkatan' => ['nullable', 'string', 'max:40'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'kepala_opd' => ['nullable', 'string', 'max:255'],
        ]);

        Opd::create($validated + ['is_active' => true]);

        return back()->with('status', 'OPD berhasil ditambahkan.');
    }

    public function update(Request $request, Opd $opd): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:opds,kode,'.$opd->id],
            'nama' => ['required', 'string', 'max:255'],
            'singkatan' => ['nullable', 'string', 'max:40'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'kepala_opd' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $opd->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'OPD berhasil diperbarui.');
    }

    public function destroy(Opd $opd): RedirectResponse
    {
        if ($opd->users()->exists() || $opd->proposals()->exists()) {
            return back()->withErrors(['opd' => 'OPD masih memiliki pengguna/usulan, tidak bisa dihapus.']);
        }

        $opd->delete();

        return back()->with('status', 'OPD dihapus.');
    }
}
