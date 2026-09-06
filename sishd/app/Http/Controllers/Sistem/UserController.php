<?php

namespace App\Http\Controllers\Sistem;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sistem > User (Bab 17-18 kajian): kelola akun & 6 level akses. */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['role', 'opd'])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'ilike', '%'.$request->string('q').'%')->orWhere('email', 'ilike', '%'.$request->string('q').'%'))
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->integer('role_id')))
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('sistem.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('id')->get(),
            'opds' => Opd::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'opd_id' => ['nullable', 'exists:opds,id'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create($validated + ['is_active' => true, 'email_verified_at' => now()]);

        return back()->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'opd_id' => ['nullable', 'exists:opds,id'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $nonaktif = ! $request->boolean('is_active');

        if ($this->menghabiskanSuperAdminTerakhir($user, (int) $validated['role_id'], $nonaktif)) {
            return back()->withErrors([
                'user' => 'Tidak bisa mengubah role atau menonaktifkan Super Admin terakhir — '.
                          'sistem akan kehilangan satu-satunya akun yang bisa mengelola pengguna. '.
                          'Angkat Super Admin lain lebih dulu.',
            ]);
        }

        $user->update($validated + ['is_active' => ! $nonaktif]);

        return back()->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus akun sendiri.']);
        }

        if ($this->menghabiskanSuperAdminTerakhir($user, roleBaru: null, dinonaktifkan: true)) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus Super Admin terakhir.']);
        }

        $user->delete();

        return back()->with('status', 'Pengguna dihapus.');
    }

    /**
     * Benarkah tindakan ini menyisakan nol Super Admin aktif?
     *
     * Tanpa penjagaan ini, Super Admin terakhir bisa menurunkan role-nya sendiri (atau menonaktifkan
     * akunnya) hanya lewat form biasa, dan sesudahnya tidak ada seorang pun yang bisa membuka menu
     * Sistem untuk mengangkatnya kembali — hanya bisa dipulihkan lewat akses langsung ke basis data.
     *
     * @param  int|null  $roleBaru  role tujuan; null berarti pengguna akan dihapus
     */
    private function menghabiskanSuperAdminTerakhir(User $user, ?int $roleBaru, bool $dinonaktifkan): bool
    {
        if (! $user->isSuperAdmin() || ! $user->is_active) {
            return false;
        }

        $tetapSuperAdminAktif = $roleBaru !== null
            && $roleBaru === $user->role_id
            && ! $dinonaktifkan;

        if ($tetapSuperAdminAktif) {
            return false;
        }

        $superAdminAktifLain = User::query()
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('name', RoleName::SuperAdmin->value))
            ->exists();

        return ! $superAdminAktifLain;
    }
}
