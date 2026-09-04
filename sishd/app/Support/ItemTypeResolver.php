<?php

namespace App\Support;

use App\Models\Asb;
use App\Models\Hspk;
use App\Models\SbuItem;
use App\Models\SshItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Pusat pemetaan kode singkat item_type (ssh/sbu/hspk/asb) yang dipakai di price_histories,
 * proposal_items, dsb ke kelas Eloquent-nya. Kolom-kolom itu sengaja bukan morph class name penuh
 * agar aman dipakai lintas format ekspor/impor (Bab 19 kajian).
 */
class ItemTypeResolver
{
    public const MAP = [
        'ssh' => SshItem::class,
        'sbu' => SbuItem::class,
        'hspk' => Hspk::class,
        'asb' => Asb::class,
    ];

    public static function modelClass(string $itemType): ?string
    {
        return self::MAP[$itemType] ?? null;
    }

    public static function resolve(string $itemType, int|string $id): ?Model
    {
        $class = self::modelClass($itemType);

        return $class ? $class::find($id) : null;
    }

    public static function typeFor(string $modelClass): ?string
    {
        $flipped = array_flip(self::MAP);

        return $flipped[$modelClass] ?? null;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'ssh' => 'SSH',
            'sbu' => 'SBU',
            'hspk' => 'HSPK',
            'asb' => 'ASB',
        ];
    }
}
