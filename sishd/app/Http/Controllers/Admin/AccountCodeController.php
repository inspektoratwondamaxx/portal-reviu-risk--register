<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountCodeController extends Controller
{
    public function index(): View
    {
        return view('admin.referensi.kode-rekening', [
            'items' => AccountCode::with('parent')->orderBy('kode')->paginate(20),
            'parents' => AccountCode::orderBy('kode')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:account_codes,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:account_codes,kode'],
            'uraian' => ['required', 'string', 'max:255'],
            'jenis_belanja' => ['nullable', 'string', 'max:40'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        AccountCode::create($validated + ['is_active' => true]);

        return back()->with('status', 'Kode rekening ditambahkan.');
    }

    public function update(Request $request, AccountCode $accountCode): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:account_codes,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:account_codes,kode,'.$accountCode->id],
            'uraian' => ['required', 'string', 'max:255'],
            'jenis_belanja' => ['nullable', 'string', 'max:40'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $accountCode->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Kode rekening diperbarui.');
    }

    public function destroy(AccountCode $accountCode): RedirectResponse
    {
        if ($accountCode->children()->exists() || $accountCode->codeMappings()->exists()) {
            return back()->withErrors(['account_code' => 'Masih dipakai data lain, tidak bisa dihapus.']);
        }

        $accountCode->delete();

        return back()->with('status', 'Kode rekening dihapus.');
    }
}
