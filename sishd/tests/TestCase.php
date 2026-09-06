<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Layout memakai @vite, sedangkan hasil build (public/build) tidak ikut di-commit. Tanpa ini
     * tes yang merender view akan gagal dengan ViteManifestNotFoundException di mesin/CI yang
     * belum menjalankan `npm run build` — kegagalan soal aset, bukan soal logika yang diuji.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
