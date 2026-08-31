# Rencana dan Kasus Uji — SISHD

**Sistem**: SISHD (Sistem Informasi Standar Harga Daerah)
**Versi/Rilis**: ......................... *(diisi sesuai penomoran rilis instansi)*
**Disusun tanggal**: ......................... *(diisi)*
**Disusun oleh**: ......................... *(diisi)*

---

## 1. Ruang Lingkup Pengujian

Pengujian mencakup modul-modul berikut:

| Kode | Modul | Cakupan |
|---|---|---|
| A | Autentikasi & Otorisasi | Login, batas akses per role, pemisahan data antar-OPD |
| B | Usulan OPD | Pengajuan usulan SSH/SBU/HSPK/ASB, validasi input |
| C | Approval Berjenjang | Rantai Verifikator → Tim Standar Harga → Pejabat Berwenang |
| D | API Publik | Endpoint baca-saja `/api/v1` untuk konsumsi sistem lain |
| E | Kaskade Harga | Perubahan harga SSH/SBU merambat ke HSPK |
| F | Formula ASB | Evaluasi formula terparameter tanpa `eval()` |
| G | Master Data & Pendukung | CRUD master, import/export Excel, survei harga, pemetaan kode, laporan |

**Di luar cakupan**: pengujian beban/kinerja (load testing), pengujian penetrasi keamanan mendalam,
dan pengujian kompatibilitas lintas peramban. Ketiganya disarankan sebagai kegiatan terpisah.

## 2. Strategi Pengujian

Pengujian dilakukan dua lapis:

1. **Otomatis (PHPUnit)** — untuk logika yang harus tetap benar setiap kali kode diubah: alur
   approval, batas otorisasi, pemetaan data, perhitungan, dan kontrak API. Dijalankan dengan
   `php artisan test` dan otomatis dieksekusi CI pada setiap perubahan.
2. **Manual (UAT)** — untuk hal yang perlu penilaian manusia: kenyamanan tampilan, kesesuaian
   istilah dengan kebiasaan instansi, dan modul yang belum tercakup pengujian otomatis (Modul G).

## 3. Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| Bahasa/Framework | PHP 8.4, Laravel 11 |
| Basis data | PostgreSQL 16 (ekstensi `pg_trgm` aktif) |
| Basis data pengujian | `sishd_test` — terpisah dari basis data pengembangan/produksi |
| Antarmuka | Bootstrap 5, aset dibundel Vite |
| Otomasi | PHPUnit, dijalankan lokal dan via GitHub Actions |

> Pengujian otomatis **tidak** dapat dijalankan di SQLite karena aplikasi memakai operator `ilike`
> dan ekstensi `pg_trgm` milik PostgreSQL.

## 4. Kriteria Lulus / Gagal

- **Lulus**: seluruh kasus uji otomatis berstatus lulus, dan seluruh kasus uji manual bertanda
  wajib (ditandai **[W]**) berstatus lulus.
- **Lulus dengan catatan**: kasus uji wajib lulus, namun terdapat temuan kategori Rendah/Kosmetik
  yang disepakati diperbaiki pada rilis berikutnya.
- **Gagal**: terdapat kasus uji wajib yang tidak lulus, atau ditemukan cacat kategori
  Tinggi/Kritis yang belum diperbaiki.

## 5. Asumsi dan Risiko

| No | Asumsi / Risiko | Mitigasi |
|---|---|---|
| 1 | Data uji adalah data contoh, bukan data harga riil daerah | Sebelum produksi, lakukan pengujian ulang memakai cuplikan data riil |
| 2 | Modul G belum tercakup pengujian otomatis | Diuji manual pada UAT; disarankan ditambah pengujian otomatis pada iterasi berikut |
| 3 | Integrasi SIPD Level 2 (tulis) belum tersedia karena spesifikasi resmi belum diperoleh | API baca-saja diuji; integrasi tulis diuji setelah spesifikasi tersedia |
| 4 | Pengujian dijalankan pada lingkungan pengembangan, bukan server produksi instansi | Ulangi pengujian asap (smoke test) setelah pemasangan di server instansi |

---

# BAGIAN I — KASUS UJI OTOMATIS

Seluruh kasus uji pada bagian ini telah diimplementasikan sebagai pengujian otomatis dan
dijalankan pada setiap perubahan kode. Kolom "Berkas Uji" merujuk lokasi implementasinya.

## Modul A — Autentikasi & Otorisasi

Berkas: `tests/Feature/OtorisasiAksesTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| A-01 | Login dengan kredensial benar | 1. Buka `/login` 2. Isi email & kata sandi 3. Klik Masuk | `operator@sishd.test` / `password` | Berhasil login, diarahkan ke `/dashboard` | Lulus |
| A-02 | Login dengan kata sandi salah | 1. Buka `/login` 2. Isi email benar, kata sandi salah 3. Klik Masuk | `operator@sishd.test` / `salah-total` | Muncul pesan galat, pengguna tetap belum login | Lulus |
| A-03 | Akses halaman terproteksi tanpa login | 1. Pastikan belum login 2. Buka langsung `/dashboard`, `/usulan`, `/admin/ssh` | Tanpa sesi login | Ketiganya dialihkan ke `/login`, isi halaman tidak tampil | Lulus |
| A-04 | Situs publik tetap terbuka tanpa login | 1. Pastikan belum login 2. Buka `/` | Tanpa sesi login | Halaman publik tampil normal (HTTP 200) | Lulus |
| A-05 | OPD tidak bisa membuka usulan OPD lain | 1. Login sebagai operator Dinas PU 2. Buka URL detail usulan milik OPD lain langsung | ID usulan milik OPD berbeda | Akses ditolak (HTTP 403), data OPD lain tidak tampil | Lulus |
| A-06 | Daftar usulan hanya menampilkan milik sendiri | 1. Siapkan 1 usulan milik OPD sendiri & 1 milik OPD lain 2. Login sebagai operator 3. Buka `/usulan` | Dua usulan beda OPD | Hanya nomor usulan milik OPD sendiri yang tampil | Lulus |
| A-07 | OPD tidak bisa mengajukan ulang usulan OPD lain | 1. Login sebagai operator 2. Kirim POST ajukan-ulang untuk usulan OPD lain | ID usulan OPD berbeda | Ditolak (HTTP 403) | Lulus |
| A-08 | Verifikator memakai layar verifikasi, bukan layar OPD | 1. Login sebagai verifikator 2. Buka `/usulan/{id}` 3. Buka `/verifikasi/{id}` | Usulan status menunggu verifikasi | Layar OPD ditolak (403); layar verifikasi tampil (200) | Lulus |
| A-09 | Super Admin boleh lintas OPD | 1. Login sebagai Super Admin 2. Buka detail usulan OPD mana pun | Usulan milik Dinas PU | Halaman detail tampil (200) | Lulus |
| A-10 | Operator OPD tidak bisa mengubah master langsung | 1. Login sebagai operator 2. Buka `/admin/ssh` dan `/verifikasi` | — | Keduanya ditolak (403) | Lulus |
| A-11 | Operator tidak bisa memutuskan verifikasi lewat URL langsung | 1. Login sebagai operator 2. Kirim POST `/verifikasi/{id}/putuskan` dengan `keputusan=setuju` | Usulan miliknya sendiri | Ditolak (403); status usulan tetap `menunggu_verifikasi` | Lulus |
| A-12 | Verifikator tidak bisa membuka menu Sistem | 1. Login sebagai verifikator 2. Buka `/sistem/opd` | — | Ditolak (403) | Lulus |
| A-13 | Super Admin bisa membuka seluruh menu | 1. Login sebagai Super Admin 2. Buka `/admin/ssh`, `/verifikasi`, `/sistem/opd` | — | Ketiganya tampil (200) | Lulus |
| A-14 | Keluar (logout) mencabut akses | 1. Login 2. Klik Keluar 3. Buka `/dashboard` | — | Sesi berakhir; `/dashboard` dialihkan ke `/login` | Lulus |

## Modul B — Usulan OPD

Berkas: `tests/Feature/UsulanOpdTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| B-01 | Usulan SSH tersimpan sampai master | 1. Ajukan usulan SSH 2. Setujui seluruh tahap approval 3. Periksa master SSH | Uraian "Semen Portland 50 Kg", Satuan "Zak", Harga 82.000 | Item muncul di master SSH dengan harga 82.000 dan OPD pengusul benar | Lulus |
| B-02 | Usulan SBU menyimpan besaran (bukan 0) | 1. Ajukan usulan SBU 2. Setujui seluruh tahap 3. Periksa master SBU | Uraian "Honorarium Narasumber Uji", Kategori "honorarium", Satuan "OJ", Besaran 900.000 | Besaran tersimpan 900.000, tidak berubah menjadi 0 | Lulus |
| B-03 | Usulan ASB menyimpan nama kegiatan | 1. Ajukan usulan ASB 2. Setujui seluruh tahap 3. Periksa master ASB | Nama Kegiatan "Pembangunan Gedung Uji", Kelompok "Belanja Modal Gedung", Satuan Variabel "M2" | Data ASB terbentuk tanpa melanggar batasan NOT NULL | Lulus |
| B-04 | Usulan HSPK tersimpan sampai master | 1. Ajukan usulan HSPK 2. Setujui seluruh tahap 3. Periksa master HSPK | Uraian "Pekerjaan Plesteran Uji", Jenis "Pekerjaan Dinding", Satuan "M2" | Item muncul di master HSPK | Lulus |
| B-05 | Tolak usulan SSH tanpa harga | 1. Isi form SSH tanpa mengisi Harga 2. Simpan | Harga: (kosong) | Muncul galat validasi pada field `harga`; usulan tidak tersimpan | Lulus |
| B-06 | Tolak usulan SBU tanpa besaran & kategori | 1. Isi form SBU tanpa Besaran dan Kategori 2. Simpan | Besaran & Kategori: (kosong) | Galat validasi pada `besaran` dan `kategori`; usulan tidak tersimpan | Lulus |
| B-07 | Tolak usulan ASB tanpa nama kegiatan | 1. Isi form ASB tanpa Nama Kegiatan 2. Simpan | Nama Kegiatan: (kosong) | Galat validasi pada `nama_kegiatan`; usulan tidak tersimpan | Lulus |
| B-08 | Tolak jenis usulan di luar daftar | 1. Kirim request dengan jenis usulan tidak dikenal | `jenis_usulan = "jenis-karangan"` | Ditolak validasi, bukan galat 500 | Lulus |
| B-09 | Tolak harga negatif | 1. Isi form SSH dengan harga negatif 2. Simpan | Harga: -5.000 | Galat validasi pada `harga`; usulan tidak tersimpan | Lulus |
| B-10 | Usulan perubahan wajib menunjuk item | 1. Pilih tipe "perubahan" tanpa memilih item yang diubah 2. Simpan | `tipe_perubahan = perubahan`, tanpa `existing_item_id` | Galat validasi pada `existing_item_id` | Lulus |
| B-11 | Input berisi skrip tampil sebagai teks | 1. Isi Alasan Usulan dengan payload skrip 2. Simpan 3. Buka halaman detail | `<script>alert(1)</script>` | Teks tampil ter-escape sebagai teks biasa; skrip tidak dieksekusi | Lulus |
| B-12 | Usulan belum disetujui tidak mengubah master | 1. Ajukan usulan SSH 2. Jangan setujui 3. Periksa master SSH | Uraian "Barang Belum Disetujui" | Master SSH tidak bertambah; item belum ada | Lulus |
| B-13 | Nomor usulan otomatis dan unik | 1. Ajukan dua usulan berturut-turut 2. Bandingkan nomor usulan | Dua usulan SSH | Kedua nomor terisi dan berbeda satu sama lain | Lulus |
| B-14 | Usulan tercatat atas nama OPD pengusul | 1. Ajukan usulan sebagai operator 2. Periksa data usulan | Operator Dinas PU | `opd_id`, `created_by`, dan tahun anggaran aktif tercatat benar | Lulus |

## Modul C — Approval Berjenjang

Berkas: `tests/Feature/ApprovalBerjenjangTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| C-01 | Usulan baru mulai dari tahap pertama | 1. Ajukan usulan 2. Periksa tahap saat ini | Usulan SSH baru | Tahap = Verifikator (tahap 1 dari 3), status menunggu verifikasi | Lulus |
| C-02 | Master baru dibuat setelah tahap terakhir | 1. Setujui tahap 1 2. Setujui tahap 2 3. Setujui tahap 3 4. Periksa master di tiap tahap | Usulan SSH "Kabel NYM 3x2.5 Uji", Harga 12.000 | Master **tidak** bertambah pada tahap 1 & 2; bertambah hanya setelah tahap 3 | Lulus |
| C-03 | Tiap tahap tercatat di riwayat | 1. Lalui ketiga tahap 2. Buka riwayat verifikasi | Tiga keputusan berurutan | Riwayat memuat tepat 3 catatan berurutan: verifikator, tim standar harga, pejabat berwenang | Lulus |
| C-04 | Reviewer tahap lain tidak bisa memutuskan | 1. Usulan berada di tahap Verifikator 2. Login sebagai Pejabat Berwenang 3. Kirim POST keputusan | Usulan tahap 1 | Ditolak (403); tahap tidak berubah; tidak ada catatan verifikasi tercipta | Lulus |
| C-05 | Form keputusan hanya muncul untuk pemilik tahap | 1. Buka layar verifikasi sebagai Pejabat Berwenang 2. Buka layar sama sebagai Verifikator | Usulan tahap 1 | Pejabat: form tidak tampil + pesan "bukan tahap Anda". Verifikator: form tampil | Lulus |
| C-06 | Antrean verifikasi disaring per tahap | 1. Buka daftar verifikasi sebagai Verifikator 2. Buka sebagai Pejabat Berwenang | Usulan tahap 1 | Verifikator melihat usulan; Pejabat Berwenang tidak melihatnya | Lulus |
| C-07 | Revisi mengulang dari tahap pertama | 1. Setujui tahap 1 2. Minta revisi di tahap 2 3. Ajukan ulang | Catatan revisi "Harga perlu dilengkapi survei" | Setelah diajukan ulang, tahap kembali ke Verifikator (tahap 1) | Lulus |
| C-08 | Penolakan menghentikan rantai | 1. Tolak usulan di tahap 1 2. Periksa master | Catatan "Tidak sesuai kebutuhan" | Status menjadi ditolak; master tidak bertambah | Lulus |
| C-09 | Pimpinan hanya dapat membuka dashboard | 1. Login sebagai Pimpinan 2. Buka `/dashboard` 3. Buka `/admin/ssh` dan `/verifikasi` | Akun Pimpinan | Dashboard tampil (200); kedua menu teknis ditolak (403) | Lulus |

## Modul D — API Publik

Berkas: `tests/Feature/HargaApiTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| D-01 | Daftar SSH hanya item aktif | 1. Siapkan 1 item aktif & 1 nonaktif 2. Panggil `GET /api/v1/ssh` | Item aktif + item nonaktif | Hanya item aktif muncul; item nonaktif tidak bocor | Lulus |
| D-02 | Detail SSH lewat kode barang | 1. Panggil `GET /api/v1/ssh/TEST-1234` | Kode "TEST-1234", harga 99.500 | Data item benar (kode, uraian, harga) | Lulus |
| D-03 | Detail menolak item nonaktif & kode asing | 1. Panggil detail untuk item nonaktif 2. Panggil detail untuk kode tidak dikenal | "TEST-NONAKTIF", "TIDAK-ADA" | Keduanya HTTP 404 | Lulus |
| D-04 | Filter tahun tak dikenal menghasilkan kosong | 1. Panggil `?tahun=2099` 2. Panggil `?tahun=<tahun aktif>` 3. Panggil tanpa filter | Satu item pada tahun aktif | 2099 → 0 baris; tahun aktif → 1 baris; tanpa filter → 1 baris | Lulus |
| D-05 | Pencarian dan filter kode | 1. Panggil `?q=Cat` 2. Panggil `?kode=BBB` | "AAA-0001 Cat Tembok Putih", "BBB-0002 Pasir Pasang" | Masing-masing mengembalikan tepat 1 item yang sesuai | Lulus |
| D-06 | `per_page` dibatasi | 1. Panggil `?per_page=9999` 2. Panggil `?per_page=0` | — | Dibatasi maksimal 100; minimal 1 | Lulus |
| D-07 | Ringkasan menghitung hanya data aktif | 1. Siapkan 1 aktif & 1 nonaktif 2. Panggil `GET /api/v1/ringkasan` | — | Jumlah SSH = 1; tahun aktif sesuai | Lulus |
| D-08 | API tidak memerlukan login | 1. Pastikan belum login 2. Panggil `GET /api/v1/ssh` | Tanpa sesi | Berhasil (200) | Lulus |

## Modul E — Kaskade Harga HSPK

Berkas: `tests/Feature/HspkCascadeTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| E-01 | Komponen peralatan bersumber SBU ikut terhitung | 1. Buat HSPK dengan komponen material (SSH) & peralatan (SBU) 2. Hitung ulang | Semen 82.000 × 2; Sewa Molen 25.000 × 4 | Total = 264.000; komponen peralatan berharga 25.000 (bukan 0), subtotal 100.000 | Lulus |
| E-02 | Perubahan harga SSH memicu hitung ulang HSPK | 1. Hitung HSPK awal 2. Ubah harga SSH komponennya 3. Periksa harga HSPK | Semen 82.000 → 90.000, koefisien 10 | HSPK berubah otomatis dari 820.000 menjadi 900.000 | Lulus |
| E-03 | Perubahan harga meninggalkan jejak audit | 1. Ubah harga SSH 2. Periksa riwayat harga & analisis HSPK | Semen 100.000 → 120.000 | Riwayat harga tercatat; analisis HSPK mencatat harga sebelum 100.000 dan sesudah 120.000 | Lulus |

## Modul F — Formula ASB

Berkas: `tests/Unit/SafeFormulaEvaluatorTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| F-01 | Aritmatika dasar | Evaluasi empat operasi dasar | `3+4`, `10-4`, `3*4`, `10/4` | 7; 6; 12; 2,5 | Lulus |
| F-02 | Urutan operasi & tanda kurung | Evaluasi ekspresi campuran | `2+3*4`, `(2+3)*4`, `2*3+10/2` | 14; 20; 11 | Lulus |
| F-03 | Substitusi variabel | Evaluasi formula dengan dua variabel | `{luas_bangunan} * {standar_biaya_per_m2}`, nilai 500 dan 7.500.000 | 3.750.000.000 | Lulus |
| F-04 | Tolak variabel tanpa nilai | Evaluasi formula bervariabel tanpa memberi nilainya | `{luas} * 2`, variabel kosong | Ditolak dengan galat formula | Lulus |
| F-05 | Tolak pembagian nol | Evaluasi pembagian dengan nol | `10 / 0` | Ditolak dengan galat formula | Lulus |
| F-06 | Tolak formula kosong | Evaluasi string kosong | `"   "` | Ditolak dengan galat formula | Lulus |
| F-07 | Tolak kurung tidak seimbang | Evaluasi kurung yang tidak ditutup | `(2 + 3` | Ditolak dengan galat formula | Lulus |
| F-08 | Tolak karakter di luar tata bahasa | Evaluasi ekspresi menyerupai kode PHP | `phpinfo()` | Ditolak; tidak dieksekusi sebagai kode | Lulus |
| F-09 | Tolak sisipan perintah lain | Evaluasi formula dengan perintah tambahan | `{sistem}; DROP TABLE ssh_items;` | Ditolak; tidak dieksekusi | Lulus |
| F-10 | Daftar variabel tanpa perlu nilai | Ambil daftar nama variabel dari formula | `{luas} * {tarif} + {luas}` | Mengembalikan `luas`, `tarif` (tanpa duplikat) | Lulus |

## Modul G — Pengujian Asap (Smoke Test)

Berkas: `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| G-01 | Halaman depan dapat diakses | Panggil `GET /` | — | HTTP 200 | Lulus |
| G-02 | Kerangka pengujian berjalan | Jalankan pengujian unit dasar | — | Lulus | Lulus |

---

# BAGIAN II — KASUS UJI MANUAL (UAT)

Modul berikut **belum tercakup pengujian otomatis** dan perlu diuji manual saat UAT.
Tanda **[W]** = wajib lulus sebagai syarat penerimaan.

## Modul H — Master Data (CRUD)

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| H-01 **[W]** | Tambah item SSH baru | 1. Login sebagai Admin SSH 2. Buka Master Data → SSH 3. Klik Tambah 4. Isi seluruh field wajib 5. Simpan | Kode "1.02.9001", Uraian "Cat Tembok 5 Kg", Satuan "Kaleng", Harga 185.000 | Data tersimpan, muncul pesan sukses, item tampil di daftar | |
| H-02 **[W]** | Ubah harga item SSH | 1. Buka detail item hasil H-01 2. Ubah harga menjadi 195.000 3. Simpan | Harga 185.000 → 195.000 | Harga berubah; riwayat harga mencatat perubahan beserta penggunanya | |
| H-03 | Tolak kode barang duplikat | 1. Tambah item SSH baru dengan kode yang sudah dipakai 2. Simpan | Kode "1.02.9001" (sudah ada) | Muncul galat "kode sudah digunakan"; data tidak tersimpan ganda | |
| H-04 **[W]** | Nonaktifkan item | 1. Buka item aktif 2. Ubah status menjadi nonaktif 3. Simpan 4. Cek situs publik | Item hasil H-01 | Item hilang dari pencarian publik namun tetap tersimpan di basis data | |
| H-05 | Pencarian & filter daftar master | 1. Buka daftar SSH 2. Cari kata kunci 3. Terapkan filter tahun/kategori | Kata kunci "Cat" | Hasil sesuai kata kunci & filter; jumlah data terlihat benar | |

## Modul I — Import & Export Excel

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| I-01 **[W]** | Export data SSH ke Excel | 1. Buka Master Data → SSH 2. Klik Export | Data minimal 5 baris | Berkas `.xlsx` terunduh; isi & judul kolom sesuai tampilan tabel | |
| I-02 | Export saat hasil filter kosong | 1. Terapkan filter hingga 0 hasil 2. Klik Export | Filter tanpa hasil | Berkas tetap terunduh berisi judul kolom saja, atau muncul pesan informatif — bukan galat | |
| I-03 **[W]** | Import data SSH dari Excel | 1. Siapkan berkas sesuai templat 2. Buka menu Import 3. Unggah 4. Konfirmasi | Berkas berisi 3 baris data valid | Ketiga baris masuk; ringkasan jumlah berhasil ditampilkan | |
| I-04 **[W]** | Import berkas dengan baris bermasalah | 1. Unggah berkas berisi campuran baris valid & tidak valid | 3 baris valid + 2 baris tanpa harga | Baris valid diproses, baris bermasalah dilaporkan beserta nomor barisnya — bukan gagal senyap | |
| I-05 | Import berkas format salah | 1. Unggah berkas selain Excel (mis. `.pdf`) | Berkas PDF | Ditolak dengan pesan jelas; tidak ada data masuk | |
| I-06 **[W]** | Export format SIPD | 1. Buka menu export format SIPD 2. Unduh | Data SSH aktif | Susunan kolom sesuai format SIPD yang berlaku di instansi | |

## Modul J — Survei Harga

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| J-01 **[W]** | Input hasil survei harga | 1. Login sebagai Surveyor/Operator 2. Buka Survei Harga 3. Tambah data survei 4. Simpan | Item SSH tertentu, 3 sumber harga berbeda | Data survei tersimpan dan terhubung ke item yang benar | |
| J-02 | Unggah bukti survei | 1. Tambah survei 2. Lampirkan berkas bukti 3. Simpan | Berkas gambar/PDF | Lampiran tersimpan dan dapat dibuka kembali | |
| J-03 | Analisis hasil survei | 1. Input ≥3 data survei untuk satu item 2. Buka analisis | 3 harga: 80.000; 82.000; 90.000 | Nilai rata-rata/median ditampilkan dan dapat dipertanggungjawabkan hitungannya | |

## Modul K — Pemetaan Kode & Laporan

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| K-01 **[W]** | Validasi pemetaan kode | 1. Buka menu Pemetaan Kode 2. Jalankan validasi | Data pemetaan yang ada | Item dengan pemetaan tidak lengkap/tidak valid ditandai jelas | |
| K-02 | Dashboard menampilkan ringkasan benar | 1. Login 2. Buka Dashboard 3. Bandingkan angka ringkasan dengan jumlah data sebenarnya | — | Angka pada dashboard sama dengan jumlah data di masing-masing menu | |
| K-03 **[W]** | Laporan perubahan harga | 1. Ubah harga beberapa item 2. Buka Laporan → Perubahan Harga | Minimal 2 perubahan harga | Seluruh perubahan tercatat lengkap beserta tanggal dan pelakunya | |
| K-04 **[W]** | Jejak audit tidak dapat diubah pengguna | 1. Buka Audit Log 2. Cari opsi ubah/hapus entri | — | Tidak tersedia opsi ubah/hapus; entri hanya dapat dibaca | |

## Modul L — Antarmuka & Kenyamanan Pakai

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| L-01 | Tampilan pada layar ponsel | 1. Buka aplikasi pada peramban ponsel 2. Telusuri menu utama | Layar ±360px | Tata letak menyesuaikan; tidak ada teks/tabel terpotong | |
| L-02 | Istilah sesuai kebiasaan instansi | 1. Telusuri seluruh menu bersama perwakilan pengguna | — | Istilah (SSH/SBU/HSPK/ASB, nama role, label tombol) sesuai kebiasaan instansi | |
| L-03 | Pesan galat mudah dipahami | 1. Kirim form dengan isian salah | Field wajib dikosongkan | Pesan galat berbahasa Indonesia, jelas menunjuk field bermasalah | |

---

## Cara Menjalankan Pengujian Otomatis

```bash
# Persiapan sekali saja
sudo -u postgres psql -c "CREATE DATABASE sishd_test OWNER sishd"
sudo -u postgres psql -d sishd_test -c "CREATE EXTENSION IF NOT EXISTS pg_trgm"

# Jalankan seluruh pengujian
php artisan test

# Jalankan satu modul saja
php artisan test --filter=OtorisasiAksesTest
```
