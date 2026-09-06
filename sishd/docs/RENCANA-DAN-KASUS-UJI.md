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
| I | Import & Export Excel | Import master SSH/SBU dari berkas, templat, export biasa & format SIPD |
| H | Master Data (CRUD) | CRUD SSH/SBU/HSPK/ASB, kode otomatis, riwayat harga, nonaktifkan/aktifkan |
| J | Survei Harga | Perekaman survei lapangan, bukti digital, rekap statistik harga |
| K | Pemetaan Kode | Kode Aset → Kode Rekening → Kode SIPD beserta status validasinya |
| G | Laporan & Pendukung | Dashboard, laporan perubahan harga, jejak audit |

**Di luar cakupan**: pengujian beban/kinerja (load testing), pengujian penetrasi keamanan mendalam,
dan pengujian kompatibilitas lintas peramban. Ketiganya disarankan sebagai kegiatan terpisah.

## 2. Strategi Pengujian

Pengujian dilakukan dua lapis:

1. **Otomatis (PHPUnit)** — untuk logika yang harus tetap benar setiap kali kode diubah: alur
   approval, batas otorisasi, pemetaan data, perhitungan, dan kontrak API. Dijalankan dengan
   `php artisan test` dan otomatis dieksekusi CI pada setiap perubahan.
2. **Manual (UAT)** — untuk hal yang perlu penilaian manusia: kenyamanan tampilan, kesesuaian
   istilah dengan kebiasaan instansi, dan modul yang belum tercakup pengujian otomatis (Modul G
   dan sisa Modul H/I/J/K yang bergantung pada peramban atau format resmi instansi).

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
| 2 | Modul G (dashboard, laporan, jejak audit) belum tercakup pengujian otomatis | Diuji manual pada UAT; disarankan ditambah pengujian otomatis pada iterasi berikut |
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

## Modul I — Import & Export Excel

Berkas: `tests/Feature/ImportExportTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| I-01 | Seluruh baris valid masuk tanpa ada yang gagal | 1. Siapkan berkas 60 baris valid 2. Jalankan import SSH 3. Periksa ringkasan | 60 baris: uraian, satuan "Unit", harga 10.001–10.060 | 60 sukses, 0 gagal, 60 item di master, status "selesai" | Lulus |
| I-02 | Kode barang hasil import unik | 1. Import 40 baris valid 2. Bandingkan seluruh kode barang | 40 baris valid | Tidak ada kode barang kembar | Lulus |
| I-03 | Import ulang di hari yang sama tetap berhasil | 1. Import berkas 15 baris 2. Import berkas yang sama lagi | Berkas sama, dua kali | Kedua import 0 gagal; 30 item; 30 kode unik | Lulus |
| I-04 | Baris bermasalah dilaporkan beserta nomor barisnya | 1. Import berkas campuran valid & tidak valid | 2 valid + 1 tanpa uraian + 1 tanpa satuan | 2 sukses, 2 gagal, status "selesai", galat menyebut "Baris 3" dan "Baris 4" | Lulus |
| I-05 | Berkas yang seluruh barisnya bermasalah berstatus gagal | 1. Import berkas yang semua barisnya tidak valid | 2 baris tanpa uraian | 0 sukses, 2 gagal, status "gagal", master tidak bertambah | Lulus |
| I-06 | Baris kosong di tengah berkas diabaikan | 1. Import berkas dengan baris kosong di tengah | Baris data, baris kosong, baris data | Total baris dihitung 2 (bukan 3), 2 sukses | Lulus |
| I-07 | Import tercatat atas nama penggunanya | 1. Import sebagai Admin SSH 2. Periksa catatan import | 2 baris valid | `user_id`, jenis "ssh", nama berkas, dan `created_by` item tercatat benar | Lulus |
| I-08 | Import SBU memakai kode dari berkas bila diisi | 1. Import SBU dengan kolom kode terisi | Kode "SBU-UJI-001", besaran 900.000 | Item tersimpan dengan kode & besaran sesuai berkas | Lulus |
| I-09 | Import SBU tanpa kode tidak bentrok antar berkas | 1. Import berkas A tanpa kolom kode 2. Import berkas B dengan isi sama | Dua berkas, masing-masing 2 baris | Keduanya 0 gagal; 4 item; 4 kode unik | Lulus |
| I-10 | Import SBU menolak baris tanpa besaran | 1. Import SBU dengan besaran dikosongkan | 1 baris tanpa besaran | 0 sukses, 1 gagal, master tidak bertambah | Lulus |
| I-11 | Judul kolom templat sama dengan yang dibaca importer | 1. Unduh templat SSH & SBU 2. Bandingkan judul kolomnya dengan definisi importer | Templat SSH & SBU | Judul kolom sama persis untuk kedua jenis | Lulus |
| I-12 | Templat SSH bisa langsung diimpor kembali | 1. Unduh templat SSH 2. Import berkas templat itu apa adanya | Templat beserta baris contohnya | 1 baris terbaca, 0 gagal (baris contoh valid) | Lulus |
| I-13 | Export SSH berisi data dan judul kolom | 1. Siapkan 1 item SSH 2. Jalankan export 3. Buka berkasnya | Kode "EXP-0001", harga 82.000 | Berkas ada; judul kolom "Kode Barang"; baris data sesuai isi master | Lulus |
| I-14 | Export tanpa data tetap menghasilkan berkas | 1. Jalankan export saat master kosong | Master tanpa data | Berkas tetap terbentuk berisi judul kolom saja — bukan galat | Lulus |
| I-15 | Export SBU dan format SIPD menghasilkan berkas | 1. Siapkan 1 item SBU 2. Jalankan export SBU dan export SIPD | Kode "SBU-EXP-1" | Kedua berkas terbentuk; export tercatat atas nama penggunanya | Lulus |

---

## Modul H — Master Data (CRUD SSH/SBU/HSPK/ASB)

Berkas: `tests/Feature/MasterDataCrudTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| H-01 | Kode barang dibuat otomatis bila dikosongkan | 1. Login sebagai Admin SSH 2. Tambah item SSH tanpa mengisi Kode Barang 3. Simpan | Uraian "Semen Portland 40 Kg", Satuan "Zak", Harga 65.000 | Tersimpan dengan kode berformat `SSH-yymmdd-NNNN`, status aktif, pembuat tercatat | Lulus |
| H-02 | Kode barang boleh diisi manual | 1. Tambah item SSH dengan Kode Barang diisi 2. Simpan | Kode "SSH-MANUAL-01" | Kode tersimpan persis seperti yang diketik | Lulus |
| H-03 | Kode barang ganda ditolak | 1. Simpan item dengan kode tertentu 2. Simpan item lain dengan kode yang sama | Kode "SSH-DUP-01" dua kali | Galat validasi pada `kode_barang`; hanya satu item tersimpan | Lulus |
| H-04 | Field wajib divalidasi | 1. Kirim form SSH kosong 2. Simpan | Seluruh field kosong | Galat validasi pada `uraian`, `satuan`, `harga`, `tahun_anggaran_id`; tidak ada data tersimpan | Lulus |
| H-05 | Perubahan harga tercatat di riwayat | 1. Ubah harga item SSH 2. Isi Dasar Perubahan 3. Simpan 4. Periksa riwayat harga | 65.000 → 71.500, dasar "Hasil survei harga Maret 2026" | Riwayat mencatat harga lama, harga baru, persentase 10%, dasar perubahan, dan penggunanya | Lulus |
| H-06 | Menyunting tanpa mengubah harga tidak membuat riwayat palsu | 1. Ubah hanya uraian item 2. Simpan | Harga tetap 65.000 | Uraian berubah; riwayat harga tidak bertambah | Lulus |
| H-07 | Item nonaktif hilang dari daftar tetapi tetap tersimpan | 1. Nonaktifkan item 2. Buka daftar SSH 3. Buka daftar dengan filter status nonaktif | Item aktif | Kode barang hilang dari daftar bawaan, tetap muncul pada filter nonaktif, baris tetap ada di basis data | Lulus |
| H-08 | Item nonaktif dapat diaktifkan kembali | 1. Nonaktifkan item 2. Aktifkan kembali | Item hasil H-07 | Status kembali aktif | Lulus |
| H-09 | Deteksi barang serupa lintas penulisan | 1. Simpan item "Semen Portland 40 Kg" 2. Jalankan cek serupa dengan penulisan berbeda | Kata kunci "semen portland 40kg" | Item lama muncul sebagai kandidat serupa | Lulus |
| H-10 | Kode SBU dibuat otomatis | 1. Tambah item SBU tanpa mengisi Kode 2. Simpan | Kategori "honorarium", Besaran 1.400.000 | Tersimpan dengan kode berformat `SBU-yymmdd-NNNN` | Lulus |
| H-11 | Kategori SBU di luar daftar ditolak | 1. Tambah SBU dengan kategori karangan 2. Simpan | `kategori = "kategori-karangan"` | Galat validasi pada `kategori`; data tidak tersimpan | Lulus |
| H-12 | HSPK menghitung ulang setelah komponen ditambah | 1. Buat HSPK 2. Tambahkan komponen SSH dengan koefisien | Semen Rp65.000 × koefisien 2,5 | Harga satuan HSPK menjadi Rp162.500 (hasil hitung, bukan ketikan) | Lulus |
| H-13 | Komponen HSPK lain tidak bisa dihapus lewat URL | 1. Buat dua HSPK 2. Hapus komponen milik HSPK B lewat jalur HSPK A | ID komponen milik HSPK berbeda | Ditolak (HTTP 404); komponen tetap ada | Lulus |
| H-14 | ASB menghitung hasil dari formula & variabelnya | 1. Buat ASB 2. Tambah 2 variabel manual 3. Simpan formula | 40 peserta × Rp250.000 | Hasil perhitungan ASB = Rp10.000.000 | Lulus |
| H-15 | Formula memakai variabel belum terdaftar ditolak | 1. Simpan formula yang menyebut variabel tak dikenal | `{variabel_hantu} * 2` | Galat validasi pada `ekspresi`; formula tidak tersimpan | Lulus |
| H-16 | Kode otomatis tidak bentrok dengan kode manual | 1. Simpan item berkode manual berformat sama dengan format otomatis 2. Simpan item berikutnya tanpa kode | Kode manual `SSH-yymmdd-0002` | Kedua item tersimpan dengan kode berbeda; tidak ada halaman galat | Lulus |
| H-17 | Perlindungan bentrok kode berlaku juga di SBU/HSPK/ASB | 1. Ulangi H-16 pada SBU, HSPK, dan ASB | Kode manual berformat sama pada tiap jenis | Masing-masing menyimpan dua data tanpa bentrok | Lulus |
| H-18 | Admin SSH tidak bisa menyunting master HSPK/ASB | 1. Login sebagai Admin SSH 2. Buka menu HSPK 3. Kirim POST pembuatan ASB | — | Keduanya ditolak (403); tidak ada ASB terbentuk | Lulus |
| H-19 | Admin HSPK/ASB tidak bisa menyunting master SSH/SBU | 1. Login sebagai Admin HSPK/ASB 2. Buka menu SSH 3. Kirim POST pembuatan SSH | — | Keduanya ditolak (403); tidak ada SSH terbentuk | Lulus |
| H-20 | Operator OPD tidak bisa mengubah master data | 1. Login sebagai operator 2. Kirim PUT perubahan harga item SSH | Harga diubah menjadi 1 | Ditolak (403); harga item tidak berubah | Lulus |

## Modul J — Survei Harga

Berkas: `tests/Feature/SurveiHargaTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| J-01 | Admin harga dapat membuka daftar survei | 1. Login sebagai Admin SSH 2. Buka menu Survei Harga | — | Halaman tampil (HTTP 200) | Lulus |
| J-02 | Survei tersimpan atas nama pengguna yang login | 1. Login sebagai operator 2. Isi form survei 3. Simpan | Item SSH, vendor, harga 67.500 | Tersimpan; surveyor terisi pengguna yang login, terhubung ke item SSH yang benar | Lulus |
| J-03 | Nama surveyor tidak bisa dipalsukan lewat form | 1. Kirim form survei sambil menyisipkan `surveyor_id` pengguna lain | `surveyor_id` milik Admin SSH | Surveyor tetap tercatat sebagai pengguna yang login | Lulus |
| J-04 | Bukti survei tersimpan sebagai berkas | 1. Isi survei 2. Lampirkan foto daftar harga 3. Simpan | Berkas gambar | Baris bukti terbentuk dengan jenis yang dipilih; berkasnya benar-benar ada di penyimpanan | Lulus |
| J-05 | Survei tanpa tanggal / berharga negatif ditolak | 1. Kirim form tanpa tanggal survei dan harga negatif | Harga −1 | Galat validasi pada `tanggal_survei` dan `harga`; data tidak tersimpan | Lulus |
| J-06 | Menghapus survei ikut menghapus berkas buktinya | 1. Simpan survei berbukti 2. Hapus survei 3. Periksa penyimpanan | Survei dengan 1 berkas bukti | Baris survei, baris bukti, dan berkasnya hilang — tidak ada berkas tertinggal | Lulus |
| J-07 | Operator tidak bisa menghapus survei milik orang lain | 1. Login sebagai operator 2. Hapus survei milik pengguna lain lewat URL | Survei milik Admin SSH | Ditolak (403); survei tetap ada | Lulus |
| J-08 | Surveyor boleh menghapus survei buatannya sendiri | 1. Login sebagai operator 2. Hapus survei buatannya | Survei miliknya sendiri | Berhasil dihapus | Lulus |
| J-09 | Admin harga boleh menghapus survei siapa pun | 1. Login sebagai Admin SSH 2. Hapus survei milik operator | Survei milik operator | Berhasil dihapus | Lulus |
| J-10 | Daftar survei dapat disaring per item SSH | 1. Simpan survei untuk dua item berbeda 2. Saring berdasarkan satu item | Survei "Semen" & "Besi Beton" | Hanya survei item terpilih yang tampil | Lulus |
| J-11 | Halaman detail survei menampilkan rekap harga | 1. Buka detail salah satu survei | 2 survei untuk item yang sama | Halaman tampil beserta rekapnya (HTTP 200) | Lulus |
| J-12 | Median untuk jumlah data ganjil | 1. Hitung rekap atas 3 harga | 65.000; 70.000; 90.000 | Min 65.000; Maks 90.000; Median 70.000; Rata-rata 75.000 | Lulus |
| J-13 | Median untuk jumlah data genap | 1. Hitung rekap atas 4 harga | 60.000; 70.000; 80.000; 100.000 | Median 75.000 (rata-rata dua nilai tengah); Rata-rata 77.500 | Lulus |
| J-14 | Harga rekomendasi tahan terhadap harga nyeleneh | 1. Hitung rekap dengan satu penawaran ekstrem | 65.000; 66.000; 67.000; 5.000.000 | Rekomendasi 66.500 (median), lebih rendah daripada rata-rata yang tertarik ke atas | Lulus |
| J-15 | Rekap tanpa data tidak menimbulkan galat | 1. Hitung rekap atas daftar kosong | Tidak ada survei | Seluruh nilai 0, bukan halaman galat | Lulus |
| J-16 | Rekap per item hanya menghitung survei item itu | 1. Simpan survei untuk dua item 2. Hitung rekap salah satu item | 2 survei "Semen", 1 survei "Besi" berharga ekstrem | Rekap "Semen" hanya menghitung 2 survei miliknya | Lulus |
| J-17 | Role tanpa kewenangan tidak bisa membuka survei | 1. Login sebagai Verifikator / Pimpinan / Admin HSPK 2. Buka menu Survei Harga | — | Ketiganya ditolak (403) | Lulus |
| J-18 | Role tanpa kewenangan tidak bisa menyimpan survei lewat POST | 1. Login sebagai Verifikator 2. Kirim POST simpan survei | — | Ditolak (403); tidak ada survei tersimpan | Lulus |
| J-19 | Tamu diarahkan ke halaman login | 1. Tanpa login, buka menu Survei Harga | — | Dialihkan ke `/login` | Lulus |

## Modul K — Pemetaan Kode

Berkas: `tests/Feature/PemetaanKodeTest.php`

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| K-01 | Pemetaan lengkap berstatus valid | 1. Simpan pemetaan kode aset + rekening + SIPD | Kode aset, rekening, dan kode SIPD terisi | Status `valid`; kolom waktu pemeriksaan terisi (bukti status dihitung ulang) | Lulus |
| K-02 | Pemetaan tanpa kode rekening ditandai belum lengkap | 1. Simpan pemetaan tanpa kode rekening | Hanya kode aset | Status `belum_rekening` | Lulus |
| K-03 | Dua pemetaan untuk kode aset sama ditandai duplikasi | 1. Simpan dua pemetaan untuk kode aset yang sama | Dua rekening berbeda | Kedua baris ditandai `duplikasi`, bukan hanya yang terakhir | Lulus |
| K-04 | Kode aset yang belum dipetakan ikut terhitung | 1. Simpan dua kode aset tanpa pemetaan 2. Jalankan validasi | 2 kode aset | Ringkasan mencatat 2 "belum dipetakan", 0 valid | Lulus |
| K-05 | Kode aset nonaktif tidak ikut dihitung | 1. Nonaktifkan kode aset 2. Jalankan validasi | Kode aset nonaktif tanpa pemetaan | Tidak dihitung sebagai belum dipetakan | Lulus |
| K-06 | Melengkapi rekening mengubah status jadi valid | 1. Simpan pemetaan tanpa rekening 2. Sunting, isi rekening | Status awal `belum_rekening` | Status berubah menjadi `valid` | Lulus |
| K-07 | Menghapus satu duplikat memulihkan status sisanya | 1. Buat dua pemetaan duplikat 2. Hapus salah satunya | Status awal keduanya `duplikasi` | Baris tersisa kembali berstatus `valid` | Lulus |
| K-08 | Ringkasan validasi ditampilkan ke petugas | 1. Siapkan 1 pemetaan valid & 1 kode aset belum dipetakan 2. Jalankan validasi | — | Pesan berisi "1 valid, 0 belum ada rekening, 0 duplikasi, 1 kode aset belum dipetakan" | Lulus |
| K-09 | Cek kode cepat: kode yang sudah dipetakan | 1. Cek kode aset yang sudah punya rekening | Kode aset terpetakan | Balasan JSON berstatus `valid` dengan label "Kode valid" | Lulus |
| K-10 | Cek kode cepat: kode tidak terdaftar | 1. Cek kode yang tidak ada | `9.9.9.99.99.99.999` | Berstatus `tidak_ditemukan`; data kode aset kosong | Lulus |
| K-11 | Cek kode cepat: terdaftar tetapi belum dipetakan | 1. Cek kode aset yang belum punya pemetaan | Kode aset baru | Berstatus `tidak_ditemukan`, namun data kode asetnya tetap dikembalikan | Lulus |
| K-12 | Cek kode cepat menandai duplikasi | 1. Cek kode aset yang punya dua pemetaan | Kode aset terduplikasi | Berstatus `duplikasi` | Lulus |
| K-13 | Cek kode wajib mengirim parameter kode | 1. Panggil cek kode tanpa parameter | — | Ditolak validasi (HTTP 422), bukan galat 500 | Lulus |
| K-14 | Pemetaan ke kode aset tidak dikenal ditolak | 1. Simpan pemetaan dengan ID kode aset karangan | `asset_code_id = 999999` | Galat validasi pada `asset_code_id`; tidak ada pemetaan tersimpan | Lulus |
| K-15 | Role tanpa kewenangan tidak bisa membuka/mengubah pemetaan | 1. Login sebagai Operator/Verifikator/Admin HSPK/Pimpinan 2. Buka & kirim POST pemetaan | — | Seluruhnya ditolak (403); tidak ada pemetaan tersimpan | Lulus |


# BAGIAN II — KASUS UJI MANUAL (UAT)

Modul berikut **belum tercakup pengujian otomatis** dan perlu diuji manual saat UAT.
Tanda **[W]** = wajib lulus sebagai syarat penerimaan.

## Modul H — Master Data (CRUD)

> Modul ini **sudah tercakup pengujian otomatis** — lihat Bagian I, Modul H. Yang tersisa untuk
> diuji manual pada UAT hanya hal yang bergantung pada penilaian pengguna di layar:

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| H-M1 **[W]** | Item nonaktif hilang dari situs publik | 1. Nonaktifkan sebuah item SSH 2. Buka situs publik tanpa login 3. Cari item tersebut | Item SSH aktif | Item tidak muncul di pencarian publik, namun datanya tetap ada di back office | |
| H-M2 | Pencarian & filter daftar master terasa benar | 1. Buka daftar SSH 2. Cari kata kunci 3. Terapkan filter tahun/kategori | Kata kunci "Cat" | Hasil sesuai kata kunci & filter; jumlah data terlihat wajar oleh petugas | |

## Modul I — Import & Export Excel

> Modul ini **sudah tercakup pengujian otomatis** — lihat Bagian I, Modul I. Yang tersisa untuk
> diuji manual pada UAT hanyalah dua hal yang bergantung pada peramban dan format instansi:

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| I-M1 | Import berkas format salah lewat antarmuka | 1. Buka menu Import 2. Unggah berkas selain Excel (mis. `.pdf`) | Berkas PDF | Ditolak dengan pesan jelas; tidak ada data masuk | |
| I-M2 **[W]** | Kesesuaian susunan kolom export SIPD | 1. Buka menu export format SIPD 2. Unduh 3. Bandingkan dengan format SIPD yang berlaku | Data SSH aktif | Susunan kolom sesuai format SIPD yang berlaku di instansi saat ini | |

## Modul J — Survei Harga

> Modul ini **sudah tercakup pengujian otomatis** — lihat Bagian I, Modul J. Yang tersisa untuk
> diuji manual pada UAT hanya yang bergantung pada peramban dan perangkat petugas lapangan:

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| J-M1 **[W]** | Unggah bukti dari kamera ponsel | 1. Buka Survei Harga dari ponsel 2. Tambah survei 3. Ambil foto bukti langsung dari kamera 4. Simpan | Foto toko/daftar harga | Foto terunggah dan dapat dibuka kembali dengan jelas | |
| J-M2 **[W]** | Angka rekomendasi dipahami petugas | 1. Input ≥3 data survei satu item 2. Buka rekapnya bersama petugas | 3 harga: 80.000; 82.000; 90.000 | Petugas memahami angka min/maks/median dan menyetujui median sebagai dasar rekomendasi | |

## Modul K — Laporan, Dashboard & Jejak Audit

> Validasi pemetaan kode kini **sudah tercakup pengujian otomatis** — lihat Bagian I, Modul K.
> Yang tersisa di bawah ini adalah laporan dan jejak audit yang perlu dicocokkan dengan kebiasaan
> instansi:

| ID | Skenario | Langkah Pengujian | Data Uji | Hasil Diharapkan | Status |
|---|---|---|---|---|---|
| K-M1 **[W]** | Penandaan pemetaan bermasalah terbaca petugas | 1. Buka menu Pemetaan Kode 2. Jalankan validasi 3. Telaah daftar bersama petugas | Data pemetaan yang ada | Item bermasalah (belum ada rekening/duplikasi/belum dipetakan) mudah dikenali dan dipahami tindak lanjutnya | |
| K-M2 | Dashboard menampilkan ringkasan benar | 1. Login 2. Buka Dashboard 3. Bandingkan angka ringkasan dengan jumlah data sebenarnya | — | Angka pada dashboard sama dengan jumlah data di masing-masing menu | |
| K-M3 **[W]** | Laporan perubahan harga | 1. Ubah harga beberapa item 2. Buka Laporan → Perubahan Harga | Minimal 2 perubahan harga | Seluruh perubahan tercatat lengkap beserta tanggal dan pelakunya | |
| K-M4 **[W]** | Jejak audit tidak dapat diubah pengguna | 1. Buka Audit Log 2. Cari opsi ubah/hapus entri | — | Tidak tersedia opsi ubah/hapus; entri hanya dapat dibaca | |

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
