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

```bash
composer install
cp .env.example .env
php artisan key:generate

# Buat database & user PostgreSQL, lalu set DB_* di .env (lihat .env.example)
php artisan migrate --seed

npm install && npm run build   # atau `npm run dev` saat mengembangkan tampilan
php artisan serve
```

Akun demo (password semua: `password`) — data contoh untuk uji coba, bukan data riil:

| Role | Email |
|---|---|
| Super Admin | inspektoratwondamaxx@gmail.com |
| Admin SSH | adminssh@sishd.test |
| Admin HSPK/ASB | adminhspk@sishd.test |
| OPD/Operator | operator@sishd.test |
| Verifikator | verifikator@sishd.test |

Situs publik (tanpa login) ada di `/`; back office di `/dashboard` setelah login.

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

## Di Luar Cakupan Sesi Ini

- **Integrasi API SIPD Level 2** (Bab 21 kajian): route `routes/api.php` dan Sanctum sudah
  terpasang sebagai fondasi, tetapi belum ada endpoint REST API SIPD nyata untuk dikonsumsi karena
  tidak ada spesifikasi/kredensial API SIPD resmi yang diberikan.
- **Approval berjenjang penuh** (Operator → Verifikator → Tim Standar Harga → Pejabat Berwenang):
  model `proposal_reviews.tahapan` sudah mendukung banyak tahap, tetapi alur yang diimplementasi
  saat ini satu tahap verifikasi (role `verifikator`) sesuai fitur inti "wajib dipertahankan" di
  kajian; menambah tahap lanjutan tinggal menambah pemanggilan `ProposalWorkflowService::review()`
  dengan parameter `$tahapan` berikutnya.
- Data seed adalah contoh secukupnya untuk mendemonstrasikan tiap fitur (bukan ribuan baris data
  riil seperti pada mockup dashboard).
