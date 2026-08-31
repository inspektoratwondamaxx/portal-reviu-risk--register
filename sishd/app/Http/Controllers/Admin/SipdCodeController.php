<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCode;
use App\Models\SipdCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SipdCodeController extends Controller
{
    public function index(): View
    {
        return view('admin.referensi.kode-sipd', [
            'items' => SipdCode::with('accountCode')->orderBy('kode')->paginate(20),
            'accountCodes' => AccountCode::orderBy('kode')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:sipd_codes,kode'],
            'uraian' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:ssh,sbu,hspk,asb'],
            'account_code_id' => ['nullable', 'exists:account_codes,id'],
        ]);

        SipdCode::create($validated + ['is_active' => true]);

        return back()->with('status', 'Kode SIPD ditambahkan.');
    }

    public function update(Request $request, SipdCode $sipdCode): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:sipd_codes,kode,'.$sipdCode->id],
            'uraian' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:ssh,sbu,hspk,asb'],
            'account_code_id' => ['nullable', 'exists:account_codes,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sipdCode->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Kode SIPD diperbarui.');
    }

    public function destroy(SipdCode $sipdCode): RedirectResponse
    {
        $sipdCode->delete();

        return back()->with('status', 'Kode SIPD dihapus.');
    }
}
