<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Pembuat kode otomatis SSH/SBU/HSPK/ASB (format PREFIX-yymmdd-NNNN).
 *
 * Dibuat terpusat karena nomor urut dari max(id) saja tidak cukup: kolom kodenya unik, dan kode
 * yang diketik petugas boleh memakai format yang sama persis dengan format otomatis ini. Begitu
 * ada satu kode manual "SSH-260906-0002" sementara id terbesar masih 1, pembuatan berikutnya akan
 * menghasilkan kode yang sama dan gagal di level basis data (halaman error, data batal tersimpan).
 * Karena itu nomornya dinaikkan sampai benar-benar belum terpakai, bukan sekali hitung lalu pasrah.
 *
 * Aturan ini pernah ditulis ulang di lima tempat dengan cara yang berbeda-beda dan ketiganya
 * bermasalah: kode SSH hasil import dibangkitkan dari nomor baris ditambah angka acak 0-999
 * (impor puluhan baris pada tanggal yang sama bentrok sendiri, dan baris yang gagal berpindah-
 * pindah tiap kali dijalankan), kode SBU dari nomor baris saja (berkas kedua di hari yang sama
 * pasti bentrok), dan keempat controller master data dari max(id) tanpa pemeriksaan. Disatukan di
 * sini supaya satu perbaikan berlaku untuk seluruh jalur pembuatan kode.
 */
class KodeGenerator
{
    /**
     * @param  class-string<Model>  $model  model yang menyimpan kode (mis. SshItem::class)
     * @param  string  $kolom  nama kolom kodenya (mis. 'kode_barang')
     * @param  string  $prefix  awalan kode (mis. 'SSH')
     */
    public function next(string $model, string $kolom, string $prefix): string
    {
        $awalan = $prefix.'-'.now()->format('ymd').'-';
        $urutan = ((int) $model::max('id')) + 1;

        while ($model::where($kolom, $awalan.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT))->exists()) {
            $urutan++;
        }

        return $awalan.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }
}
