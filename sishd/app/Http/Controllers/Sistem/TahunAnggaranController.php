<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\TahunAnggaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TahunAnggaranController extends Controller
{
    public function index(): View
    {
        return view('sistem.tahun-anggaran.index', ['items' => TahunAnggaran::orderByDesc('tahun')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'digits:4', 'unique:tahun_anggarans,tahun'],
            'status' => ['required', 'in:draft,aktif,tutup'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            if ($validated['status'] === 'aktif') {
                TahunAnggaran::query()->update(['is_active' => false]);
            }
            TahunAnggaran::create($validated + ['is_active' => $validated['status'] === 'aktif']);
        });

        return back()->with('status', 'Tahun anggaran ditambahkan.');
    }

    public function update(Request $request, TahunAnggaran $tahunAnggaran): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,aktif,tutup'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $tahunAnggaran) {
            if ($validated['status'] === 'aktif') {
                TahunAnggaran::query()->where('id', '!=', $tahunAnggaran->id)->update(['is_active' => false]);
            }
            $tahunAnggaran->update($validated + ['is_active' => $validated['status'] === 'aktif']);
        });

        return back()->with('status', 'Tahun anggaran diperbarui.');
    }
}
