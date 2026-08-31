# BERITA ACARA
# USER ACCEPTANCE TESTING (UAT)

**Nomor**: ................................................ *(diisi sesuai penomoran instansi)*

Pada hari ini, ...................... tanggal ...................... bulan ......................
tahun ......................, bertempat di ................................................,
telah dilaksanakan User Acceptance Testing (UAT) atas sistem:

| | |
|---|---|
| **Nama Sistem** | SISHD — Sistem Informasi Standar Harga Daerah |
| **Versi / Rilis** | ................................................ |
| **Lingkungan Pengujian** | ☐ Pengembangan ☐ Staging ☐ Produksi |
| **Alamat Akses** | ................................................ |

---

## 1. Dasar Pelaksanaan

1. ................................................................................
   *(mis. kontrak/perjanjian kerja/surat tugas — diisi sesuai dokumen instansi)*
2. Jadwal pengembangan dan serah terima sistem.
3. Dokumen `Rencana dan Kasus Uji — SISHD` sebagai acuan skenario pengujian.

## 2. Ruang Lingkup Pengujian

UAT ini mencakup modul-modul berikut:

- ☐ Autentikasi & Otorisasi (hak akses per role dan pemisahan data antar-OPD)
- ☐ Usulan OPD (pengajuan SSH/SBU/HSPK/ASB beserta validasinya)
- ☐ Approval Berjenjang (Verifikator → Tim Standar Harga → Pejabat Berwenang)
- ☐ Master Data (SSH, SBU, HSPK, ASB)
- ☐ Import & Export Excel, termasuk format SIPD
- ☐ Survei Harga
- ☐ Pemetaan Kode & Laporan
- ☐ Situs Publik & API Publik
- ☐ Dashboard

## 3. Pihak yang Terlibat

| No | Nama | Jabatan / Instansi | Peran dalam Pengujian |
|---|---|---|---|
| 1 | .......................... | .......................... | Perwakilan Pengguna |
| 2 | .......................... | .......................... | Perwakilan Pengguna |
| 3 | .......................... | .......................... | Penguji |
| 4 | .......................... | .......................... | Pengembang |
| 5 | .......................... | .......................... | .......................... |

## 4. Hasil Pengujian

Kolom **Hasil** diisi: **L** (Lulus) / **LC** (Lulus dengan Catatan) / **TL** (Tidak Lulus).
Rincian langkah tiap skenario merujuk dokumen `Rencana dan Kasus Uji — SISHD`.

### 4.1 Modul Terverifikasi Otomatis

Modul berikut telah diverifikasi melalui 60 kasus uji otomatis dengan hasil seluruhnya lulus.
Pengujian pada sesi UAT bersifat konfirmasi oleh perwakilan pengguna.

| No | Modul / Fitur | Skenario Diuji | Hasil | Catatan |
|---|---|---|---|---|
| 1 | Autentikasi | Login dengan akun masing-masing role | | |
| 2 | Otorisasi | OPD tidak dapat melihat usulan OPD lain | | |
| 3 | Otorisasi | Role tanpa kewenangan ditolak saat membuka menu teknis | | |
| 4 | Usulan OPD | Pengajuan usulan SSH beserta validasi field wajib | | |
| 5 | Usulan OPD | Pengajuan usulan SBU, HSPK, dan ASB | | |
| 6 | Approval Berjenjang | Persetujuan berurutan tiga tahap sampai data induk terbentuk | | |
| 7 | Approval Berjenjang | Reviewer tahap lain tidak dapat mendahului giliran | | |
| 8 | Approval Berjenjang | Usulan direvisi dan diajukan ulang dari tahap awal | | |
| 9 | Kaskade Harga | Perubahan harga SSH mengubah HSPK terkait secara otomatis | | |
| 10 | Situs Publik / API | Pencarian data harga tanpa login | | |

### 4.2 Modul Diuji Manual pada Sesi Ini

| No | Modul / Fitur | Skenario Diuji | Hasil | Catatan |
|---|---|---|---|---|
| 11 | Master Data | Tambah dan ubah item SSH; riwayat harga tercatat | | |
| 12 | Master Data | Nonaktifkan item; item hilang dari situs publik | | |
| 13 | Import Excel | Import data master dari berkas sesuai templat | | |
| 14 | Import Excel | Import berkas berisi baris bermasalah; galat dilaporkan per baris | | |
| 15 | Export Excel | Export daftar data ke berkas `.xlsx` | | |
| 16 | Export SIPD | Export dengan susunan kolom format SIPD | | |
| 17 | Survei Harga | Input hasil survei beserta bukti pendukung | | |
| 18 | Pemetaan Kode | Validasi kelengkapan pemetaan kode barang/rekening | | |
| 19 | Laporan | Laporan perubahan harga dan jejak audit | | |
| 20 | Dashboard | Kesesuaian angka ringkasan dengan data sebenarnya | | |
| 21 | Antarmuka | Tampilan pada perangkat ponsel dan kejelasan istilah | | |

### 4.3 Rekapitulasi

| Kategori | Jumlah |
|---|---|
| Skenario diuji | .......... |
| Lulus (L) | .......... |
| Lulus dengan Catatan (LC) | .......... |
| Tidak Lulus (TL) | .......... |

## 5. Daftar Perbaikan yang Disepakati

| No | Uraian Temuan | Tingkat Kepentingan | Target Penyelesaian |
|---|---|---|---|
| 1 | .................................................. | ☐ Tinggi ☐ Sedang ☐ Rendah | .................. |
| 2 | .................................................. | ☐ Tinggi ☐ Sedang ☐ Rendah | .................. |
| 3 | .................................................. | ☐ Tinggi ☐ Sedang ☐ Rendah | .................. |

## 6. Kesimpulan

Berdasarkan hasil pengujian sebagaimana tercantum pada angka 4, para pihak menyatakan bahwa
sistem SISHD:

- ☐ **DITERIMA** — seluruh skenario pengujian dinyatakan lulus dan sistem siap dipergunakan.
- ☐ **DITERIMA DENGAN CATATAN** — sistem dapat dipergunakan, dengan perbaikan sebagaimana
  tercantum pada angka 5 diselesaikan paling lambat tanggal ..............................
- ☐ **BELUM DITERIMA** — terdapat temuan yang harus diselesaikan terlebih dahulu, dan pengujian
  ulang dijadwalkan pada tanggal ..............................

Catatan tambahan:

................................................................................................

................................................................................................

Demikian Berita Acara ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.

---

## 7. Lembar Persetujuan

| Perwakilan Pengguna / OPD | Pengembang |
|---|---|
| <br><br><br> | <br><br><br> |
| ( .......................................... ) | ( .......................................... ) |
| NIP/ID: .................................. | NIP/ID: .................................. |

<br>

**Mengetahui,**
**Pimpinan / Pejabat Berwenang**

<br><br><br>

( .......................................... )
NIP: ..........................................

---

> **Catatan pengisian**: seluruh titik-titik, kotak centang, dan kolom tanda tangan pada dokumen
> ini adalah isian kosong yang harus dilengkapi oleh instansi sesuai keadaan sebenarnya. Jangan
> menandatangani atau mengisi nama sebelum pengujian benar-benar dilaksanakan.
