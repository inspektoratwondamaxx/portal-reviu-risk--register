# Agen Persuratan (percobaan – Fase 1)

Aplikasi register **surat masuk, surat keluar, dan disposisi** dengan arsip berkas (foto/scan/PDF) yang tersimpan terstruktur dan dapat dicari. Dibangun dengan Laravel 13 (PHP 8.3). Proyek ini terpisah dari portal Reviu Risk Register.

## Fitur Fase 1
- Register surat masuk (`NNNN/SM/TAHUN`) dan surat keluar (`NNNN/SK/TAHUN`), nomor agenda otomatis per tahun.
- Unggah banyak berkas per surat (PDF/JPG/PNG, maks. 10 MB). Dari HP dapat langsung memotret.
- Arsip terstruktur: `storage/app/arsip/{masuk|keluar}/{tahun}/{bulan}/{urut}_{asal}_{perihal}.ext`; berkas asli tidak pernah ditimpa; deteksi berkas duplikat (SHA-256).
- Disposisi: pimpinan memilih penerima, instruksi standar, catatan, dan batas waktu; penerima memperbarui status tindak lanjut.
- Pencarian pada nomor, asal/tujuan, perihal, ringkasan, nama berkas (dan hasil OCR pada Fase 2).
- Role: `admin`, `operator` (petugas persuratan), `pimpinan`, `staf` (hanya melihat surat yang didisposisikan kepadanya).
- Audit log atas login, pembuatan, lihat, unduh, disposisi. Berkas hanya dapat diunduh setelah login dan lolos pemeriksaan hak akses.

## Menjalankan (lokal)
```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```
Akun contoh (hanya lokal, **ganti sebelum dipakai sungguhan**; kata sandi `password`):
`admin@contoh.test`, `operator@contoh.test`, `pimpinan@contoh.test`, `staf@contoh.test`.

Pengujian: `php artisan test`

## Untuk produksi
- Ganti ke PostgreSQL (`DB_CONNECTION=pgsql`), `APP_ENV=production`, `APP_DEBUG=false`, HTTPS.
- Atur `ARSIP_PATH` ke penyimpanan permanen di luar folder aplikasi dan jadwalkan backup.
- Aktifkan 2FA dan kebijakan kata sandi sebelum dipakai untuk data nyata.

## Rencana berikutnya
- **Fase 2:** OCR/ekstraksi data dari foto surat dengan Claude API (draf, diverifikasi operator; surat *rahasia* diinput manual, tidak dikirim keluar).
- **Fase 3:** cetak lembar disposisi PDF, pengingat batas waktu, notifikasi.
- **Fase 4:** penerimaan foto lewat WhatsApp Cloud API (nomor khusus) atau bot Telegram, pencarian bahasa natural, dashboard.
