<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Role tetap sesuai Bab III/IV kajian teknis: kaur_keuangan, kepala_kampung, pendamping,
 * inspektorat, admin. kampung_id NULL untuk role lintas-kampung (pendamping/inspektorat/admin).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ROLE_KAUR_KEUANGAN = 'kaur_keuangan';

    public const ROLE_KEPALA_KAMPUNG = 'kepala_kampung';

    public const ROLE_PENDAMPING = 'pendamping';

    public const ROLE_INSPEKTORAT = 'inspektorat';

    public const ROLE_ADMIN = 'admin';

    public const ROLES_WAJIB_2FA = [self::ROLE_INSPEKTORAT, self::ROLE_ADMIN];

    protected $fillable = [
        'kampung_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function kampung(): BelongsTo
    {
        return $this->belongsTo(Kampung::class);
    }

    public function pendampingWilayah(): HasMany
    {
        return $this->hasMany(PendampingWilayah::class);
    }

    public function kampungBinaan()
    {
        return $this->belongsToMany(Kampung::class, 'pendamping_wilayah');
    }

    public function notifikasis(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    public function wajib2fa(): bool
    {
        return in_array($this->role, self::ROLES_WAJIB_2FA, true);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
