<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** KF-17 — pengelolaan akun pengguna seluruh aktor. */
class UserController extends Controller
{
    private const ROLES = ['kaur_keuangan', 'kepala_kampung', 'pendamping', 'inspektorat', 'admin'];

    public function index(Request $request)
    {
        $query = User::query()->with('kampung');

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($kampungId = $request->query('kampung_id')) {
            $query->where('kampung_id', $kampungId);
        }

        return $this->ok($query->orderBy('name')->paginate($request->integer('per_page', 50))->items());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'kampung_id' => ['required_if:role,kaur_keuangan,kepala_kampung', 'nullable', 'integer', 'exists:kampungs,id'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $passwordSementara = Str::password(12);

        $user = User::create([
            ...$validator->validated(),
            'password' => Hash::make($passwordSementara),
        ]);

        return $this->ok([
            'user' => $user,
            'password_sementara' => $passwordSementara,
        ], status: 201);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', Rule::in(self::ROLES)],
            'kampung_id' => ['sometimes', 'nullable', 'integer', 'exists:kampungs,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $user->update($validator->validated());

        return $this->ok($user->fresh());
    }

    /** Tetapkan kampung binaan untuk role pendamping (Bab VI.7). */
    public function setWilayahBinaan(Request $request, User $user)
    {
        if ($user->role !== 'pendamping') {
            return $this->fail('Wilayah binaan hanya berlaku untuk role pendamping.', status: 422);
        }

        $validator = Validator::make($request->all(), [
            'kampung_ids' => ['required', 'array'],
            'kampung_ids.*' => ['integer', 'exists:kampungs,id'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $user->kampungBinaan()->sync($request->input('kampung_ids'));

        return $this->ok($user->kampungBinaan()->get());
    }
}
