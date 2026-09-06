# Kajian Keamanan Sistem — SISHD

**Sistem**: SISHD (Sistem Informasi Standar Harga Daerah)
**Versi/Rilis**: ......................... *(diisi sesuai penomoran rilis instansi)*
**Tanggal review**: ......................... *(diisi)*
**Direview oleh**: ......................... *(diisi)*

---

## 1. Latar Belakang dan Ruang Lingkup

SISHD menyimpan data standar harga daerah (SSH/SBU/HSPK/ASB) yang menjadi dasar penyusunan dan
reviu anggaran seluruh OPD, beserta jejak audit perubahannya. Nilai yang dilindungi bukan
kerahasiaan data harga — data itu justru sengaja dipublikasikan lewat situs publik — melainkan
**keutuhan (integritas) angka dan keabsahan jejak siapa yang mengubahnya**. Angka harga yang diubah
tanpa kewenangan, atau persetujuan yang terjadi tanpa sepengetahuan pejabat berwenang, berdampak
langsung pada keabsahan dokumen anggaran yang mengacu padanya.

Review ini mencakup pemeriksaan kode terhadap kategori OWASP Top 10 pada seluruh kode aplikasi di
direktori `sishd/`: injeksi (SQL/perintah), autentikasi & manajemen sesi, kontrol akses termasuk
IDOR, Cross-Site Scripting (XSS), Cross-Site Request Forgery (CSRF), mass assignment, penanganan
berkas unggahan, kebocoran data sensitif, dan kesalahan konfigurasi.

**Di luar cakupan**: pengujian penetrasi terhadap server instansi yang sudah berjalan, keamanan
jaringan dan sistem operasi server, serta keamanan fisik ruang server. Ketiganya perlu ditangani
terpisah oleh pengelola infrastruktur instansi.

## 2. Metodologi

Pemeriksaan tidak berhenti pada pembacaan kode. **Setiap dugaan celah dibuktikan lebih dulu dengan
menjalankan serangannya** terhadap aplikasi di lingkungan pengujian — jika serangan tidak berhasil,
dugaan itu dicoret dan tidak dilaporkan sebagai temuan. Setelah diperbaiki, dibuat kasus uji
otomatis yang **dipastikan gagal pada kode sebelum perbaikan dan lulus sesudahnya**, sehingga celah
yang sama tidak bisa terbuka lagi tanpa ketahuan.

## 3. Identifikasi Aset dan Ancaman

| Aset Informasi | Ancaman Utama | Kemungkinan | Dampak | Tingkat Risiko |
|---|---|---|---|---|
| Angka harga master (SSH/SBU/HSPK/ASB) | Perubahan tanpa kewenangan; anggaran OPD mengacu ke angka yang salah | Sedang | Tinggi | **Tinggi** |
| Rantai persetujuan usulan | Persetujuan terjadi tanpa sepengetahuan pejabat berwenang (mis. clickjacking, pembajakan sesi) | Rendah | Tinggi | **Sedang** |
| Jejak audit (audit log) | Dihapus/diubah untuk menutupi perubahan data | Rendah | Tinggi | **Sedang** |
| Kredensial pengguna | Kata sandi ditebak (brute force); cookie sesi disadap di jaringan | Sedang | Tinggi | **Tinggi** |
| Bukti survei harga | Berkas berbahaya diunggah lalu dibuka petugas lain | Sedang | Tinggi | **Tinggi** |
| Ketersediaan akun pengelola | Tidak tersisa Super Admin sehingga sistem tak bisa dikelola | Rendah | Sedang | **Sedang** |

## 4. Hasil Pemeriksaan

### 4.1 Kategori yang diperiksa dan dinyatakan aman

| Kategori | Hasil pemeriksaan |
|---|---|
| SQL Injection | Aman. Seluruh query memakai Eloquent/Query Builder. Tiga tempat memakai SQL mentah (`selectRaw`/`whereRaw` untuk `similarity()` dan agregasi laporan), seluruhnya dengan parameter binding (`?`) atau nama kolom yang ditulis tetap di kode — tidak ada input pengguna yang disambung langsung ke string query. |
| Cross-Site Scripting (XSS) tersimpan/terpantul | Aman pada tampilan. Seluruh penampilan data pengguna memakai `{{ }}` yang otomatis meng-escape. Delapan pemakaian `{!! !!}` seluruhnya mencetak potongan HTML tetap berdasarkan nilai boolean, bukan data dari pengguna. *(Untuk XSS lewat berkas unggahan, lihat temuan K-01.)* |
| CSRF | Aman. Seluruh form yang mengubah data memuat `@csrf`, dan tidak ada rute yang dikecualikan dari pemeriksaan token. |
| Mass assignment | Aman. Seluruh model memakai `$fillable` eksplisit; tidak ditemukan `$guarded = []` maupun `update($request->all())`. Kolom kewenangan (`role_id`, `opd_id`) hanya bisa diubah lewat menu Sistem yang khusus Super Admin. |
| Penyimpanan kata sandi | Aman. Model `User` memakai cast `'password' => 'hashed'` (bcrypt) dan kolom `password` masuk `$hidden` sehingga tidak pernah ikut terkirim di respons JSON. |
| Manajemen sesi | Aman. Sesi diregenerasi setelah login berhasil (mencegah session fixation), dan pada logout sesi di-invalidate beserta token-nya. Cookie sesi `HttpOnly` dan `SameSite=lax` sesuai bawaan Laravel. |
| Enumerasi pengguna | Aman. Pesan galat login sama persis untuk email terdaftar maupun tidak terdaftar. |
| IDOR / kontrol akses antar-OPD | Aman. Data yang punya kepemilikan (usulan OPD, survei harga) diperiksa kepemilikannya di sisi server; data master bersifat lintas-OPD sehingga tidak punya konsep kepemilikan per baris. Seluruhnya sudah tercakup pengujian otomatis Modul A dan H. |
| Kebocoran data lewat API publik | Aman. Respons API hanya memuat kolom bisnis yang memang untuk publik; tidak ada `id` internal, `created_by`, `opd_id`, maupun data pengguna. |
| Unggahan berkas import | Aman. Dibatasi `mimes:xlsx,xls` maksimal 10 MB dan disimpan pada disk `local` (di luar direktori yang bisa diakses langsung lewat URL). |
| `.env` dan kredensial | Aman. `.env` tercantum di `.gitignore` dan tidak pernah masuk repositori; hanya `.env.example` yang di-commit. |

### 4.2 Temuan yang perlu diperbaiki

Seluruh temuan di bawah ini **sudah diperbaiki dan diverifikasi** dalam siklus review ini.

---

#### K-01 — Berkas SVG bermuatan skrip dapat diunggah sebagai bukti survei (**Tinggi**)

**Lokasi**: `app/Http/Controllers/SurveiHarga/PriceSurveyController.php` (aturan validasi `bukti.*`)

**Kondisi**: aturan validasi memakai `'image'` bawaan Laravel. Aturan tersebut ikut menerima format
**SVG**, sedangkan SVG bukan gambar biasa melainkan dokumen XML yang boleh memuat tag `<script>`.
Berkas bukti kemudian ditampilkan dan ditautkan langsung ke domain aplikasi sendiri
(`resources/views/survei-harga/show.blade.php`).

**Skenario eksploitasi** (sudah dibuktikan berhasil pada kode sebelum perbaikan):
1. Operator OPD mana pun — peran ini dimiliki banyak pegawai lintas OPD — mengunggah "bukti survei"
   berupa berkas `.svg` yang di dalamnya berisi `<script>`.
2. Admin SSH atau Super Admin membuka survei tersebut untuk diverifikasi (pekerjaan rutinnya), lalu
   mengklik gambar buktinya untuk melihat versi besar.
3. Skrip di dalam SVG berjalan **pada domain aplikasi dengan sesi milik admin tersebut**. Skrip itu
   dapat membaca token CSRF dari halaman dan mengirim permintaan atas nama admin: mengubah harga
   master, mengangkat akun baru menjadi Super Admin, atau menyetujui usulan.

**Perbaikan yang diterapkan**: format disebut satu per satu, tanpa SVG.

```php
// sebelum
'bukti.*' => ['nullable', 'image', 'max:4096'],

// sesudah
'bukti.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
```

---

#### K-02 — Percobaan kata sandi tidak dibatasi (**Tinggi**)

**Lokasi**: `routes/web.php` (rute `POST /login`)

**Kondisi**: rute login tidak memakai middleware pembatas laju. Pengujian membuktikan 30 percobaan
kata sandi salah berturut-turut seluruhnya diterima tanpa hambatan, sehingga jumlah percobaan
praktis tak terbatas.

**Skenario eksploitasi**: penyerang yang mengetahui satu alamat email pegawai (mudah diperoleh dari
dokumen publik instansi) dapat mencoba ribuan kata sandi secara otomatis. Risikonya diperbesar oleh
akun contoh bawaan sistem yang memakai kata sandi lemah dan seragam.

**Perbaikan yang diterapkan**:

```php
// maksimal 5 percobaan per menit per IP
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')->name('login.attempt');
```

---

#### K-03 — Super Admin terakhir dapat menghilangkan kewenangannya sendiri (**Sedang**)

**Lokasi**: `app/Http/Controllers/Sistem/UserController.php` (`update`, `destroy`)

**Kondisi**: penjagaan yang ada hanya melarang menghapus akun sendiri. Pengujian membuktikan Super
Admin satu-satunya dapat menurunkan role-nya sendiri menjadi Pimpinan lewat form biasa, dan
sesudahnya jumlah Super Admin aktif menjadi **nol**.

**Dampak**: menu Sistem tidak lagi dapat dibuka siapa pun. Pengelolaan pengguna, OPD, dan tahun
anggaran berhenti total, dan hanya dapat dipulihkan lewat akses langsung ke basis data oleh tenaga
teknis — bukan celah kerahasiaan, melainkan risiko ketersediaan yang mahal dipulihkan.

**Perbaikan yang diterapkan**: penjagaan `menghabiskanSuperAdminTerakhir()` menolak perubahan role,
penonaktifan, maupun penghapusan yang akan menyisakan nol Super Admin aktif. Perubahan tetap
diizinkan bila masih ada Super Admin aktif lain.

---

#### K-04 — Header keamanan dasar belum dipasang (**Sedang**)

**Lokasi**: seluruh respons HTML (tidak ada middleware yang memasang header keamanan)

**Kondisi**: respons tidak memuat `X-Frame-Options`, `X-Content-Type-Options`, maupun
`Referrer-Policy`.

**Skenario eksploitasi**: tanpa `X-Frame-Options`, halaman aplikasi dapat dimuat di dalam `<iframe>`
tak terlihat pada situs milik penyerang. Pejabat berwenang yang sedang login lalu diarahkan
mengklik sesuatu yang tampak biasa di situs tersebut, padahal klik itu jatuh tepat pada tombol
**Setujui** di halaman verifikasi usulan yang tersembunyi di baliknya (*clickjacking*) — persetujuan
tercatat atas namanya tanpa ia sadari.

**Perbaikan yang diterapkan**: middleware `App\Http\Middleware\SecurityHeaders` memasang
`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`,
serta `Strict-Transport-Security` khusus saat permintaan datang lewat HTTPS.

---

#### K-05 — Cookie sesi belum dipaksa hanya lewat HTTPS (**Sedang** — konfigurasi, bukan kode)

**Lokasi**: `.env.example` / `.env` server (`SESSION_SECURE_COOKIE`)

**Kondisi**: nilai belum ditetapkan, sehingga cookie sesi juga dikirim lewat koneksi HTTP biasa.

**Skenario eksploitasi**: pada jaringan kantor bersama, cookie sesi pegawai yang membuka aplikasi
lewat HTTP dapat disadap dan langsung dipakai orang lain untuk masuk sebagai pegawai tersebut —
tanpa perlu mengetahui kata sandinya.

**Perbaikan yang diterapkan**: `.env.example` kini memuat `SESSION_SECURE_COOKIE` beserta penjelasan
bahwa nilainya **wajib** `true` di server instansi yang memakai HTTPS. Ini adalah item konfigurasi
yang harus dipastikan pengelola server, bukan sesuatu yang dapat dikunci dari sisi kode.

## 5. Checklist Hardening Sebelum Dipasang di Server Instansi

Status di bawah ini mencerminkan keadaan kode saat review; kolom bertanda **[server]** hanya dapat
diselesaikan oleh pengelola infrastruktur instansi.

| # | Item | Status |
|---|---|---|
| 1 | HTTPS aktif di seluruh domain, sertifikat valid & perpanjangan otomatis | **[server]** belum |
| 2 | Redirect otomatis HTTP → HTTPS | **[server]** belum |
| 3 | Header HSTS aktif | Sudah (otomatis aktif begitu diakses lewat HTTPS) |
| 4 | Firewall membatasi port yang terbuka | **[server]** belum |
| 5 | Kata sandi di-hash dengan bcrypt/argon2 | Sudah |
| 6 | Panjang minimum kata sandi diterapkan | Sudah (minimal 8 karakter) |
| 7 | Rate limiting pada endpoint login | Sudah (K-02) |
| 8 | Autentikasi dua faktor untuk akun Super Admin | Belum — lihat Rekomendasi |
| 9 | `SESSION_SECURE_COOKIE=true` di server | **[server]** wajib diisi (K-05) |
| 10 | Logout menghapus sesi di sisi server | Sudah |
| 11 | Setiap endpoint memeriksa kewenangan di sisi server | Sudah (teruji Modul A, H, J, K, M) |
| 12 | Pembatasan data per OPD untuk role operator | Sudah (teruji Modul A) |
| 13 | `APP_DEBUG=false` & `APP_ENV=production` di server | **[server]** wajib diubah (sudah didokumentasikan di `.env.example`) |
| 14 | `.env` tidak ter-commit; `APP_KEY` unik per lingkungan | Sudah |
| 15 | Header keamanan (`X-Frame-Options`, `nosniff`) aktif | Sudah (K-04) |
| 16 | Unggahan dibatasi tipe & ukuran, disimpan di lokasi tak dapat dieksekusi | Sudah (K-01) |
| 17 | Seluruh input divalidasi di sisi server | Sudah |
| 18 | Aktivitas sensitif tercatat di audit log | Sudah (teruji Modul M) |
| 19 | Kata sandi/token tidak pernah masuk log | Sudah (`password` ada di `$hidden`) |
| 20 | Backup basis data terjadwal & pernah diuji pemulihannya | **[server]** belum |
| 21 | Pemeriksaan kerentanan dependency berkala (`composer audit`) | Belum dijadwalkan — lihat Rekomendasi |
| 22 | Kata sandi akun contoh diganti & akun demo dinonaktifkan | **Wajib sebelum produksi** |

## 6. Rekomendasi

Diurutkan menurut prioritas:

1. **Ganti seluruh kata sandi akun contoh dan nonaktifkan akun demo** sebelum sistem dipakai dengan
   data sungguhan. Akun bawaan memakai kata sandi seragam yang tercantum terbuka di dokumentasi.
2. **Pastikan tiga item konfigurasi server** sebelum go-live: HTTPS aktif dengan redirect otomatis,
   `APP_DEBUG=false`, dan `SESSION_SECURE_COOKIE=true`. Ketiganya tidak dapat dijamin dari sisi kode.
3. **Aktifkan autentikasi dua faktor untuk akun Super Admin.** Akun ini dapat mengangkat pengguna
   lain dan membaca seluruh jejak audit, sehingga pembobolan satu akun ini berdampak paling luas.
   Belum diimplementasikan pada rilis ini karena menuntut keputusan instansi soal sarananya (aplikasi
   autentikator, SMS, atau surel).
4. **Jadwalkan pemeriksaan kerentanan dependency** secara berkala dengan `composer audit` dan
   `npm audit`, misalnya sebulan sekali atau setiap sebelum rilis.
5. **Siapkan backup basis data terjadwal beserta uji pemulihannya.** Jejak audit dan riwayat harga
   tidak dapat direkonstruksi ulang bila hilang.
6. **Pertimbangkan pembatasan laju pada situs publik dan API** melebihi `throttle:60,1` yang ada
   sekarang bila pemakaian meningkat, agar pengambilan data massal tidak membebani server.

## 7. Rencana Tindak Lanjut

| No | Tindakan | Penanggung Jawab | Target Waktu | Status |
|---|---|---|---|---|
| 1 | Ganti kata sandi akun contoh & nonaktifkan akun demo | ......................... | ......................... | |
| 2 | Pasang HTTPS + redirect + `SESSION_SECURE_COOKIE=true` | ......................... | ......................... | |
| 3 | Set `APP_DEBUG=false` dan `APP_ENV=production` | ......................... | ......................... | |
| 4 | Konfigurasi firewall server | ......................... | ......................... | |
| 5 | Siapkan backup terjadwal & uji pemulihan | ......................... | ......................... | |
| 6 | Kajian penerapan 2FA untuk Super Admin | ......................... | ......................... | |
| 7 | Jadwalkan pemeriksaan dependency berkala | ......................... | ......................... | |

---

**Catatan**: temuan K-01 sampai K-05 telah diperbaiki dan masing-masing dipagari kasus uji otomatis
pada `tests/Feature/KeamananTest.php` (Modul N, 13 kasus uji). Kasus uji tersebut telah dibuktikan
gagal pada kode sebelum perbaikan dan lulus sesudahnya, sehingga celah yang sama tidak dapat terbuka
kembali tanpa terdeteksi CI.
