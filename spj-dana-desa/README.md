# SPJ Dana Desa Digital

Implementasi backend Laravel + dashboard web dari **Kajian Teknis Pengembangan Sistem Informasi
SPJ Dana Desa Digital — Kabupaten Teluk Wondama (2026)**. Dibangun berdasarkan kajian yang
diupload user; lihat pemetaan fitur di bawah untuk apa yang sudah diimplementasikan penuh, apa
yang di-stub, dan apa yang di luar cakupan sesi ini.

## Stack

Sesuai Bab III.1 kajian teknis:

- **Backend & dashboard web**: Laravel 11 (PHP 8.2+)
- **Basis data**: PostgreSQL (skema memakai fitur Postgres — Row-Level Security, `jsonb`;
  SQLite didukung untuk development/testing cepat, RLS otomatis no-op di driver non-Postgres)
- **Autentikasi**: Laravel Sanctum (token untuk aplikasi Android, session untuk dashboard web),
  2FA (TOTP) wajib untuk role `inspektorat` & `admin`
- **Object storage bukti transaksi & PDF SPJ**: S3-compatible (MinIO), disk `bukti` — default ke
  disk lokal saat development (`BUKTI_FILESYSTEM_DISK=local`)
- **PDF**: barryvdh/laravel-dompdf
- **Queue**: driver `database` (job OCR diproses asinkron, KNF-06)
- **Aplikasi Android (Kotlin, KF-01–KF-04)**: **di luar cakupan sesi ini** — lihat bagian "Yang
  Belum Diimplementasikan". Backend API pada `/api/v1/*` sudah lengkap sesuai Bab VI kajian
  teknis sehingga siap dikonsumsi aplikasi Android saat dibangun.

## Menjalankan Secara Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate

# Development cepat (SQLite) — cukup untuk melihat seluruh alur kerja:
touch database/database.sqlite
php artisan migrate --seed

# Produksi/staging (PostgreSQL) — aktifkan Row-Level Security (Bab V.4):
# set DB_CONNECTION=pgsql beserta kredensial di .env, lalu:
# php artisan migrate --seed

npm install && npm run build   # atau `npm run dev` saat mengembangkan tampilan
php artisan serve
php artisan queue:work   # proses job OCR asinkron (KNF-06) di terminal terpisah
```

Akun demo (password semua: `password`) — **data contoh, bukan 75 kampung riil Teluk Wondama**,
lihat `database/seeders/DatabaseSeeder.php`:

| Role | Email |
|---|---|
| admin | admin@spjdanadesa.test |
| inspektorat | inspektorat@spjdanadesa.test |
| pendamping | pendamping@spjdanadesa.test |
| kaur_keuangan | kaur.demo01@spjdanadesa.test |
| kepala_kampung | kepala.demo01@spjdanadesa.test |

Role `inspektorat`/`admin` akan diminta setup 2FA pada login pertama — halaman verifikasi
menampilkan `otpauth://` URL untuk ditambahkan manual ke aplikasi authenticator (Google
Authenticator/Authy), karena scaffold ini tidak menyertakan pustaka rendering QR agar tidak
menambah dependensi native (GD/Imagick).

## Pemetaan Kebutuhan Fungsional

Seluruh kode rujuk balik ke kajian teknis lewat komentar `KF-xx`/`KNF-xx`/`Bab x.x` di sumbernya.

| Kode | Kebutuhan | Status |
|---|---|---|
| KF-01–04 | Input transaksi, foto kamera, GPS, offline sync | API siap (`/api/v1/transaksi/*`, `sync-batch`); UI Android **belum dibangun** |
| KF-05 | OCR ekstraksi bukti | Pipeline job asinkron ada; **layanan OCR sungguhan belum terpasang** (stub fallback ke input manual — lihat `App\Jobs\ProcessOcrBukti`) |
| KF-06 | Verifikasi/koreksi manual hasil OCR | Implementasi penuh (`POST /transaksi/{id}/verifikasi-ocr`) |
| KF-07 | Narasi otomatis | Implementasi penuh dengan fallback deterministik (`App\Services\TemplateNarasiAiGenerator`), **bukan LLM sungguhan** — lihat "Yang Belum Diimplementasikan" |
| KF-08 | Deteksi kewajaran vs pagu | Implementasi penuh, ambang batas dapat dikonfigurasi (`SPJ_AMBANG_KEWAJARAN_PERSEN`) |
| KF-09 | Chat AI | Stub (dicatat ke `chat_ai_logs`, jawaban template) — lihat "Yang Belum Diimplementasikan" |
| KF-10–12 | BKU otomatis, lampiran bukti, PDF SPJ | Implementasi penuh (`App\Services\BkuSpjPdfService`) — **lihat catatan cakupan data di bawah** |
| KF-13 | Dashboard real-time multi-kampung | Implementasi penuh (web + API) |
| KF-14 | Persetujuan berjenjang | Implementasi penuh — lihat "Catatan Ambiguitas Dokumen" soal peran kepala_kampung |
| KF-15 | Audit trail status | Implementasi penuh (`riwayat_status_transaksi`, `riwayat_status_spj`) |
| KF-16 | Ekspor kompatibel Siskeudes | Implementasi sebagai unduhan CSV terstruktur |
| KF-17 | Modul master data admin | Implementasi penuh (web + API) |
| KF-18 | Notifikasi in-app/push | Tabel & model `notifikasis` tersedia; **pengiriman push (FCM) belum diimplementasikan** karena terikat ke aplikasi Android yang belum dibangun |
| KNF-01–10 | Non-fungsional | Lihat rincian per item di bawah |

Catatan KNF:
- **KNF-01 Skalabilitas 75 tenant**: arsitektur multi-tenant hybrid dengan `kampung_id` +
  RLS Postgres sudah diterapkan; **load testing 75-tenant belum dijalankan** (Bab VII.1 Tahap 4).
- **KNF-02 Offline Android**: di luar cakupan (bagian aplikasi Android).
- **KNF-03 Keamanan transmisi**: HTTPS/TLS adalah tanggung jawab konfigurasi web
  server/reverse proxy saat deploy, bukan kode aplikasi.
- **KNF-04 Isolasi multi-tenant**: dua lapis — `App\Models\Concerns\BelongsToKampung` (global
  scope aplikasi) + RLS Postgres (`2025_02_01_000200_enable_row_level_security.php`).
- **KNF-05 Audit trail**: `App\Models\Concerns\LogsAudit` mencatat otomatis ke `audit_logs`
  pada setiap create/update/delete model transaksional.
- **KNF-06 Performa OCR asinkron**: job queue `database` driver — ganti ke Redis/SQS untuk
  produksi skala penuh.
- **KNF-07 Android 8.0+**: di luar cakupan (aplikasi Android).
- **KNF-08 Kompresi foto**: dilakukan sisi Android; backend memvalidasi ukuran maksimum
  (`SPJ_MAKS_UKURAN_BUKTI_KB`), bukan mengompres.
- **KNF-09 Struktur data tahunan**: `kode_rekening`/`bidang_kegiatan` sudah menyertakan kolom
  `tahun_anggaran`.
- **KNF-10 Kejelasan peran AI**: seluruh endpoint AI (`/api/v1/ai/*`) mengembalikan hasil
  berstatus draft dan tidak pernah mengubah status transaksi menjadi final secara otomatis.

## Yang Belum Diimplementasikan (Perlu Tindak Lanjut)

1. **Aplikasi Android (Kotlin)** — KF-01–04, KNF-02, KNF-07. Backend API `/api/v1/*` sudah
   lengkap dan siap dikonsumsi; pembangunan aplikasi native adalah paket pekerjaan terpisah
   (Bab VII.1 Tahap 1 mencakup ini bersamaan dengan backend).
2. **Layanan OCR pihak ketiga sungguhan** — saat ini `App\Jobs\ProcessOcrBukti` menandai bukti
   sebagai gagal diproses dan mengarahkan ke input manual (fallback yang justru diminta Bab
   VII.3). Untuk mengaktifkan OCR nyata, ganti isi job ini dengan pemanggilan API layanan OCR
   pilihan (mis. Google Vision, AWS Textract, atau model lokal).
3. **Layanan AI narasi & chat sungguhan (LLM)** — `App\Contracts\NarasiAiGenerator` sudah
   berbentuk kontrak/interface agar penyedia LLM sungguhan tinggal di-bind di
   `AppServiceProvider` tanpa mengubah controller. `AiController::chat()` juga perlu diarahkan
   ke penyedia LLM sungguhan dengan cara yang sama.
4. **Deployment MinIO/S3 produksi** — disk `bukti` sudah dikonfigurasi (`config/filesystems.php`)
   namun perlu instance MinIO nyata + kredensial di `.env` produksi.
5. **Import 75 kampung riil Kabupaten Teluk Wondama** — seeder hanya berisi 3 kampung contoh.
   Data riil wajib diinput Admin lewat modul KF-17 sebelum rollout.
6. **Load testing skala 75 tenant & uji penetrasi RLS+RBAC** — direkomendasikan Bab VII.3
   sebelum go-live, belum dijalankan pada sesi ini.
7. **Notifikasi push (FCM)** — tabel `notifikasis` siap; pengiriman aktual menunggu aplikasi
   Android.

## Catatan Ambiguitas Dokumen

Beberapa bagian kajian teknis tidak sepenuhnya konsisten antar-bab; keputusan implementasi
berikut diambil secara eksplisit dan didokumentasikan di kode agar mudah direvisi bila pemilik
kebutuhan mengklarifikasi maksud sebenarnya:

1. **Peran kepala_kampung dalam persetujuan SPJ** (`App\Policies\PeriodeSpjPolicy::setujui()`):
   Bab IV.3 menyebut kepala_kampung "menyetujui SPJ tingkat internal kampung", tapi tabel
   endpoint Bab VI.5 hanya memberi akses `POST .../setujui` ke `pendamping` & `inspektorat`.
   Implementasi mengikuti Bab VI.5 (lebih rinci/otoritatif sebagai spesifikasi API).
2. **Relasi status transaksi individual vs status periode_spj bulanan**
   (`App\Http\Controllers\Api\PeriodeSpjController`): kajian menyebut kedua alur status tanpa
   menjelaskan relasinya. Implementasi menjadikan `periode_spj` sebagai unit persetujuan
   sesungguhnya — transaksi berstatus `terverifikasi` dirangkai ke periode saat diajukan, lalu
   ikut berpindah status mengikuti periode induknya.
3. **Cakupan data BKU** (`App\Services\BkuSpjPdfService`): skema 15 tabel (Bab V.2) hanya
   memodelkan transaksi belanja (pengeluaran) — tidak ada entitas pencairan/penerimaan dana dari
   RKD. PDF yang dihasilkan karena itu berupa rekapitulasi realisasi belanja kumulatif, bukan
   buku kas dua sisi (penerimaan-pengeluaran) penuh. **Rekomendasi**: tambahkan entitas
   pencairan dana pada iterasi berikutnya bila BKU dua sisi diperlukan untuk pelaporan resmi ke
   BPK/DPMK.
4. **Tabel `pendamping_wilayah`, `bidang_kegiatan`, `periode_spj_transaksi`, `notifikasis`**:
   kajian menyebut nama tabel ini di Bab V.2 tanpa merinci kolomnya. Struktur kolom pada
   migration masing-masing (lihat `database/migrations/`) adalah interpretasi wajar berdasarkan
   konteks fungsional di bab-bab lain.

## Arsitektur Singkat

- **Multi-tenant hybrid** (Bab IV.1): satu basis data Postgres, kolom `kampung_id` di seluruh
  tabel transaksional, isolasi berlapis aplikasi (`BelongsToKampung` global scope) + basis data
  (RLS, lihat `App\Http\Middleware\SetTenantSessionContext`).
- **RBAC**: 5 role tetap (`kaur_keuangan`, `kepala_kampung`, `pendamping`, `inspektorat`,
  `admin`) via `App\Policies\*` + middleware `role:` (`App\Http\Middleware\EnsureRole`).
- **API** (`routes/api.php`): 46 endpoint terprefiks `/api/v1/`, format response baku
  `{success, data, meta}` / `{success:false, message, errors}` sesuai Bab VI.1.
- **Dashboard web** (`routes/web.php`): session-based, untuk pemeriksaan & persetujuan
  berjenjang — bukan untuk input transaksi harian (itu peran aplikasi Android).

## Pengujian Manual yang Sudah Dilakukan

Alur berikut sudah diuji end-to-end secara manual selama pengembangan (login → transaksi →
verifikasi → cek kewajaran → ajukan periode → setujui pendamping → 2FA inspektorat → setujui
inspektorat → generate PDF → ekspor Siskeudes → dashboard ringkasan per role → RBAC 403 pada
role yang tidak berwenang → job queue OCR asinkron). Migration diverifikasi berjalan bersih
(`php artisan migrate:fresh --seed`). Test suite otomatis (PHPUnit/Pest) **belum ditulis** —
disarankan sebagai langkah lanjutan sebelum Tahap 5 (uji coba terbatas) di Bab VII.1.
