# SISHD — Sistem Informasi Standar Harga Daerah

Implementasi Laravel dari **kajian desain aplikasi SSH** yang diupload user: bukan sekadar katalog
harga, tetapi database terpusat SSH (Standar Satuan Harga), SBU (Standar Biaya Umum), HSPK (Harga
Satuan Pokok Kegiatan), dan ASB (Analisis Standar Belanja) yang saling terhubung sebagai satu
*data ecosystem* — perubahan harga di SSH/SBU otomatis menghitung ulang HSPK yang memakainya, dan
berantai ke ASB yang variabelnya bersumber dari HSPK tersebut.

## Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Basis data**: PostgreSQL (memakai ekstensi `pg_trgm` untuk deteksi kemiripan uraian barang)
- **Tampilan**: Bootstrap 5 + Chart.js, dibundel lewat Vite (bukan CDN — beberapa CDN publik
  diblokir kebijakan jaringan di lingkungan pengembangan ini)
- **Excel**: PhpSpreadsheet (import/export SSH/SBU, template, format SIPD)
- **Auth**: session (Laravel default) + Sanctum disiapkan untuk jalur integrasi API SIPD Level 2
  di masa depan (belum diaktifkan penuh — lihat "Di Luar Cakupan" di bawah)

## Menjalankan Secara Lokal

> **Belum pernah memasang aplikasi Laravel / bukan orang IT?** Ikuti
> **[`docs/CARA-MENJALANKAN.md`](docs/CARA-MENJALANKAN.md)** — panduan langkah demi langkah dari nol
> (memasang PHP, PostgreSQL, membuat basis data, sampai membuka aplikasinya di peramban).

Untuk yang sudah terbiasa, siapkan dulu PHP 8.2+, Composer, PostgreSQL 16, dan Node.js, buat basis
data beserta penggunanya, salin `.env.example` menjadi `.env` dan isi bagian `DB_*`, lalu jalankan
skrip pemasangan:

```bash
bash setup.sh      # Linux/macOS  — setup.bat untuk Windows
php artisan serve
```

Skrip tersebut memeriksa prasyarat, memasang komponen, menyiapkan `.env` dan kunci aplikasi,
memigrasi + mengisi data contoh, lalu membangun tampilan. Bila lebih suka manual:

```bash
composer install
cp .env.example .env
php artisan key:generate

# Buat database & user PostgreSQL, lalu set DB_* di .env (lihat .env.example)
php artisan migrate --seed

npm install && npm run build   # atau `npm run dev` saat mengembangkan tampilan
php artisan serve
```

> **Sebelum dipakai dengan data sungguhan**, baca `docs/KAJIAN-KEAMANAN.md` bagian Rekomendasi:
> ganti seluruh kata sandi di bawah, aktifkan HTTPS beserta `SESSION_SECURE_COOKIE=true`, dan set
> `APP_DEBUG=false`. Tiga hal itu tidak bisa dijamin dari sisi kode.

Akun demo (password semua: `password`) — data contoh untuk uji coba, bukan data riil:

| Role | Email |
|---|---|
| Super Admin | inspektoratwondamaxx@gmail.com |
| Admin SSH | adminssh@sishd.test |
| Admin HSPK/ASB | adminhspk@sishd.test |
| OPD/Operator | operator@sishd.test |
| Verifikator (tahap 1) | verifikator@sishd.test |
| Tim Standar Harga (tahap 2) | timstandarharga@sishd.test |
| Pejabat Berwenang (tahap 3) | pejabat@sishd.test |
| Pimpinan (dashboard saja) | pimpinan@sishd.test |

Situs publik (tanpa login) ada di `/`; back office di `/dashboard` setelah login.

## Menjalankan Tes

Tes berjalan di PostgreSQL (bukan SQLite in-memory) karena query aplikasi memakai operator `ilike`
dan ekstensi `pg_trgm`. Siapkan basis data tesnya sekali saja:

```bash
sudo -u postgres psql -c "CREATE DATABASE sishd_test OWNER sishd"
sudo -u postgres psql -d sishd_test -c "CREATE EXTENSION IF NOT EXISTS pg_trgm"

php artisan test
```

Nama basis data tes sudah dikunci di `phpunit.xml` (`sishd_test`) supaya tidak pernah menyentuh
basis data pengembangan. Cakupan tes difokuskan ke bagian yang paling mahal kalau rusak diam-diam:

- `ApprovalBerjenjangTest` — rantai 3 tahap approval, termasuk jaminan data master **tidak** dibuat
  sebelum tahap terakhir, penolakan reviewer yang bukan pemilik tahap (403), dan revisi yang
  mengulang dari tahap awal.
- `HargaApiTest` — API publik: item nonaktif tidak bocor, filter benar-benar diterapkan, `per_page`
  dibatasi.
- `HspkCascadeTest` — perubahan harga SSH/SBU merambat otomatis ke HSPK beserta jejak auditnya.
- `SafeFormulaEvaluatorTest` — hasil hitung formula ASB dan penolakan input di luar tata bahasanya.
- `ImportExportTest` — import Excel: seluruh baris valid benar-benar masuk, baris bermasalah
  dilaporkan per nomor baris, berkas boleh diimpor ulang di hari yang sama; plus templat dan export.
- `MasterDataCrudTest` — CRUD SSH/SBU/HSPK/ASB: kode otomatis tidak bentrok dengan kode yang
  diketik petugas, perubahan harga selalu meninggalkan riwayat, item nonaktif tetap tersimpan, dan
  admin satu domain tidak bisa menyunting master domain lain.
- `SurveiHargaTest` — survei harga lapangan: surveyor tidak bisa dipalsukan lewat form, bukti
  digital ikut terhapus bersama datanya, survei orang lain tidak bisa dihapus, dan angka
  rekomendasi memakai median sehingga tahan terhadap satu penawaran nyeleneh.
- `PemetaanKodeTest` — pemetaan Kode Aset → Rekening → SIPD: statusnya dihitung ulang dari keadaan
  data terkini (valid / belum ada rekening / duplikasi / belum dipetakan), bukan status yang basi.
- `LaporanDashboardTest` — laporan, dashboard, dan jejak audit: filter tahun benar-benar diterapkan
  (bukan diabaikan lalu menjumlahkan seluruh tahun di bawah judul satu tahun), angka dashboard hanya
  menghitung data aktif dan dibatasi per OPD untuk operator, serta audit log yang mencatat nilai
  sebelum/sesudah dan tidak punya jalur ubah/hapus sama sekali di level server.
- `KeamananTest` — pemagar temuan review keamanan: unggahan SVG/HTML/PHP ditolak sebagai bukti
  survei, percobaan login dibatasi 5 per menit, sistem selalu menyisakan satu Super Admin aktif,
  dan header keamanan terpasang di seluruh respons.

CI GitHub Actions (`.github/workflows/sishd-tests.yml`) menjalankan tes ini dengan service
PostgreSQL setiap kali ada perubahan di `sishd/`.

### Dokumen QA

Untuk keperluan serah terima/administrasi, tersedia dokumen pengujian di `docs/`:

| Dokumen | Isi |
|---|---|
| `docs/RENCANA-DAN-KASUS-UJI.md` | Rencana pengujian + 75 kasus uji otomatis (Modul A–I) dan kasus uji manual untuk UAT (Modul H, J–L), lengkap dengan langkah dan data uji konkret |
| `docs/LAPORAN-HASIL-PENGUJIAN.md` | Laporan hasil pengujian: statistik per modul, daftar temuan beserta tingkat keparahan, risiko, dan rekomendasi |
| `docs/BERITA-ACARA-UAT.md` | Templat Berita Acara UAT — seluruh kolom nama, nomor, tanggal, dan tanda tangan berupa isian kosong untuk dilengkapi instansi |
| `docs/KAJIAN-KEAMANAN.md` | Kajian keamanan (OWASP Top 10): kategori yang diperiksa & dinyatakan aman, 5 temuan beserta skenario eksploitasi dan perbaikannya, checklist hardening pra-deploy, serta rencana tindak lanjut |

## Yang Membuatnya "Dinamis" (bukan hard-coded)

- **Kaskade HSPK**: `App\Services\HspkCalculationService` menghitung ulang HSPK otomatis begitu
  harga SSH/SBU komponennya berubah (lewat `Concerns\HasPriceHistory` yang terpasang di
  `SshItem`/`SbuItem`), dan mencatat tiap perhitungan ke `hspk_analysis` untuk jejak audit.
- **Formula ASB parameterized**: `App\Services\SafeFormulaEvaluator` adalah tokenizer + parser
  aritmatika (+ − × ÷, kurung, variabel `{nama}`) buatan sendiri — **tidak memakai `eval()` PHP**.
  Variabel ASB bisa berupa input manual atau ditarik otomatis dari SSH/SBU/HSPK
  (`App\Services\AsbCalculationService`), sehingga formula tetap "hidup" saat sumbernya berubah.
- **Deteksi duplikasi**: `App\Services\DuplicateDetectionService` memakai `similarity()` dari
  ekstensi PostgreSQL `pg_trgm`, bukan pencocokan teks persis, agar "Semen Portland 40 Kg" tetap
  terdeteksi mirip walau beda spasi/merek/ukuran.
- **Validasi mapping kode**: `App\Services\CodeMappingValidationService` menghitung ulang status
  (valid / belum ada rekening / duplikasi / tidak ditemukan) setiap dipanggil, bukan status statis
  yang bisa basi.

## Yang Membuatnya "Convertible" (Import/Export)

- Import Excel (SSH/SBU) dengan template unduhan, validasi baris, dan peringatan kemiripan data
  tanpa memblokir import (`App\Services\ImportService`).
- Export Excel biasa, per tahun anggaran, dan per kode aset; serta export format SIPD Level 1
  (`App\Services\ExportService`) — struktur kolom SIPD bersifat praktik terbaik umum dan perlu
  disesuaikan dengan template resmi SIPD daerah masing-masing sebelum diunggah.

## Approval Berjenjang

Usulan OPD (Bab 11 & 22.3 kajian) berjalan lewat 3 tahap berurutan, masing-masing perannya sendiri
— materialisasi ke tabel master (SSH/SBU/HSPK/ASB) baru terjadi setelah lolos tahap terakhir:

```
Operator (OPD) → Verifikator → Tim Standar Harga → Pejabat Berwenang → (data master diperbarui)
```

- `proposals.tahapan_saat_ini` menyimpan tahap yang sedang berjalan; `App\Models\Proposal`
  menyediakan `nextTahapan()`/`roleForTahapan()`/`tahapanKe()` sebagai state machine-nya.
- `ProposalWorkflowService::review()` selalu menentukan tahap dari `tahapan_saat_ini` milik usulan
  itu sendiri (bukan dari parameter yang dikirim caller), dan `ProposalReviewController` menolak
  (403) keputusan dari role yang bukan pemilik tahap saat ini — dicoba lompat tahap lewat POST
  langsung pun ditolak di level server, bukan cuma disembunyikan di tampilan.
- Ditolak/revisi mengembalikan usulan ke tahap pertama saat diajukan ulang, bukan melanjutkan dari
  tahap terakhir.
- Role **Pimpinan** sengaja hanya diberi akses Dashboard (tanpa menu Verifikasi/Master
  Data/Laporan), sesuai kajian: *"Pimpinan cukup membuka dashboard tanpa masuk ke menu teknis."*

## REST API Publik (v1)

Fondasi integrasi SIPD Level 2 (Bab 21 kajian): endpoint JSON baca-saja yang menyajikan data yang
sama dengan pencarian di website publik (`SshItem`/`SbuItem`/`Hspk`/`Asb` yang aktif), agar bisa
dikonsumsi sistem lain. Tanpa login, dibatasi laju `throttle:60,1` per menit.

| Endpoint | Keterangan |
|---|---|
| `GET /api/v1/ringkasan` | Jumlah data aktif per jenis + tahun anggaran aktif |
| `GET /api/v1/ssh?q=&kode=&tahun=&per_page=` | Daftar SSH (berpaginasi, `per_page` maks. 100) |
| `GET /api/v1/ssh/{kode_barang}` | Detail satu SSH |
| `GET /api/v1/sbu`, `/api/v1/sbu/{kode}` | Daftar & detail SBU |
| `GET /api/v1/hspk`, `/api/v1/hspk/{kode}` | Daftar & detail HSPK (detail menyertakan rincian komponen) |
| `GET /api/v1/asb`, `/api/v1/asb/{kode}` | Daftar & detail ASB (detail menyertakan variabel & formula) |

Contoh: `curl http://localhost:8000/api/v1/ssh?q=semen&tahun=2026`

Mengirim `tahun` dengan tahun yang tidak ada di data akan menghasilkan 0 baris (bukan diam-diam
menampilkan semua tahun) — filter selalu diterapkan persis seperti yang diminta.

## Di Luar Cakupan Sesi Ini

- **Integrasi API SIPD Level 2 yang sesuai spesifikasi resmi**: endpoint baca-saja di atas adalah
  fondasinya, tetapi belum ada endpoint tulis (mis. terima usulan dari SIPD) maupun format kolom
  yang dicocokkan ke spesifikasi/kredensial API SIPD resmi, karena tidak ada spesifikasi resmi yang
  diberikan. Sanctum sudah terpasang untuk mengamankan endpoint tulis tersebut nanti.
- Data seed adalah contoh secukupnya untuk mendemonstrasikan tiap fitur (bukan ribuan baris data
  riil seperti pada mockup dashboard).
