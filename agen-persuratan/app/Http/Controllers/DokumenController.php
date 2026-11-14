<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dokumen;
use App\Models\SuratMasuk;
use App\Services\ArsipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function unduh(Request $request, Dokumen $dokumen)
    {
        $user = $request->user();
        $induk = $dokumen->dokumenable;

        $boleh = $induk instanceof SuratMasuk
            ? SuratMasuk::dapatDilihat($user)->whereKey($induk->id)->exists()
            : $user->bolehLihatSemua();
        abort_unless($boleh, 403);

        $disk = Storage::disk(ArsipService::DISK);
        abort_unless($disk->exists($dokumen->path), 404, 'Berkas tidak ditemukan di arsip.');

        AuditLog::catat('unduh', $dokumen, ['path' => $dokumen->path]);

        return $disk->response($dokumen->path, $dokumen->nama_asli, [
            'Content-Type' => $dokumen->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
