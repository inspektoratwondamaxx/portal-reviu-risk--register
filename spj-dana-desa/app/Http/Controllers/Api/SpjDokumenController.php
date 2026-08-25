<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpjDokumen;
use Illuminate\Support\Facades\Storage;

class SpjDokumenController extends Controller
{
    public function show(SpjDokumen $spjDokumen)
    {
        $this->authorize('view', $spjDokumen->periodeSpj);

        $disk = Storage::disk('bukti');

        // temporaryUrl() hanya didukung driver s3; disk "local" (dev) memakai url() biasa.
        $url = config('filesystems.disks.bukti.driver') === 's3'
            ? $disk->temporaryUrl($spjDokumen->path_pdf, now()->addMinutes(15))
            : $disk->url($spjDokumen->path_pdf);

        return $this->ok([
            'dokumen' => $spjDokumen,
            'url' => $url,
        ]);
    }
}
