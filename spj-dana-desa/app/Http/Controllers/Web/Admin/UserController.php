<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kampung;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['kaur_keuangan', 'kepala_kampung', 'pendamping', 'inspektorat', 'admin'];

    public function index()
    {
        return view('admin.users.index', [
            'userList' => User::with(['kampung', 'kampungBinaan'])->orderBy('name')->paginate(25),
            'kampungList' => Kampung::orderBy('nama_kampung')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'kampung_id' => ['nullable', 'integer', 'exists:kampungs,id'],
        ]);

        $passwordSementara = Str::password(12);

        User::create([
            ...$validated,
            'password' => Hash::make($passwordSementara),
        ]);

        return back()->with('status', "Akun dibuat. Kata sandi sementara: {$passwordSementara}");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(self::ROLES)],
            'kampung_id' => ['nullable', 'integer', 'exists:kampungs,id'],
            'is_active' => ['sometimes'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        return back()->with('status', 'Akun berhasil diperbarui.');
    }

    public function setWilayahBinaan(Request $request, User $user)
    {
        abort_unless($user->role === 'pendamping', 422, 'Wilayah binaan hanya berlaku untuk role pendamping.');

        $validated = $request->validate([
            'kampung_ids' => ['array'],
            'kampung_ids.*' => ['integer', 'exists:kampungs,id'],
        ]);

        $user->kampungBinaan()->sync($validated['kampung_ids'] ?? []);

        return back()->with('status', 'Wilayah binaan berhasil diperbarui.');
    }
}
