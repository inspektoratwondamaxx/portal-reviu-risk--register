#!/usr/bin/env bash
#
# Pemasangan SISHD sekali jalan (Linux/macOS).
# Jalankan dari dalam folder sishd:   bash setup.sh
#
# Skrip ini TIDAK memasang PHP/Composer/PostgreSQL — itu dipasang lebih dulu
# sesuai docs/CARA-MENJALANKAN.md. Di sini hanya menyiapkan aplikasinya.

set -euo pipefail

biru()  { printf '\n\033[1;34m%s\033[0m\n' "$1"; }
hijau() { printf '\033[0;32m%s\033[0m\n' "$1"; }
merah() { printf '\033[0;31m%s\033[0m\n' "$1"; }

gagal() {
    merah "GAGAL: $1"
    echo
    echo "Silakan salin pesan galat di atas dan tanyakan kembali."
    exit 1
}

cd "$(dirname "$0")"

biru "[1/7] Memeriksa program yang dibutuhkan"
for program in php composer psql; do
    command -v "$program" >/dev/null 2>&1 || gagal "'$program' belum terpasang. Ikuti dulu docs/CARA-MENJALANKAN.md bagian Persiapan."
    hijau "  ada: $program"
done

VERSI_PHP=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
    || gagal "PHP $VERSI_PHP terlalu lama. Dibutuhkan PHP 8.2 atau lebih baru."
hijau "  PHP $VERSI_PHP (cukup)"

php -m | grep -qi '^pdo_pgsql$' \
    || gagal "Ekstensi PHP 'pdo_pgsql' belum aktif. Aktifkan dulu (lihat panduan bagian Masalah Umum)."
hijau "  ekstensi pdo_pgsql aktif"

biru "[2/7] Memasang komponen aplikasi (agak lama, mohon ditunggu)"
composer install --no-interaction --prefer-dist || gagal "composer install tidak berhasil."

biru "[3/7] Menyiapkan berkas pengaturan (.env)"
if [ -f .env ]; then
    hijau "  .env sudah ada — dibiarkan apa adanya"
else
    cp .env.example .env
    hijau "  .env dibuat dari contoh"
fi

grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force || gagal "Gagal membuat kunci aplikasi."
hijau "  kunci aplikasi siap"

biru "[4/7] Memeriksa sambungan ke basis data"
if ! php artisan db:show >/dev/null 2>&1; then
    merah "  Tidak bisa menyambung ke PostgreSQL."
    echo
    echo "  Periksa hal berikut di berkas .env (buka dengan Notepad/editor teks):"
    echo "    DB_DATABASE=sishd       <- nama basis data sudah dibuat?"
    echo "    DB_USERNAME=sishd       <- nama pengguna basis data sudah benar?"
    echo "    DB_PASSWORD=...         <- kata sandinya sudah diisi?"
    echo
    echo "  Cara membuat basis data ada di docs/CARA-MENJALANKAN.md langkah 3."
    exit 1
fi
hijau "  sambungan basis data berhasil"

biru "[5/7] Membuat tabel dan mengisi data contoh"
php artisan migrate:fresh --seed --force || gagal "Gagal menyiapkan tabel basis data."

biru "[6/7] Menyiapkan tampilan (CSS/JavaScript)"
if command -v npm >/dev/null 2>&1; then
    npm install --no-audit --no-fund && npm run build || gagal "Gagal menyiapkan tampilan."
    hijau "  tampilan siap"
else
    merah "  npm tidak ditemukan — tampilan tidak dibangun."
    echo "  Aplikasi tetap jalan, tetapi tampilannya akan polos tanpa warna."
    echo "  Pasang Node.js lalu jalankan ulang skrip ini agar tampilannya rapi."
fi

biru "[7/7] Selesai"
hijau "Pemasangan berhasil."
echo
echo "Langkah terakhir — jalankan aplikasinya dengan perintah:"
echo
echo "    php artisan serve"
echo
echo "Lalu buka alamat ini di peramban:  http://127.0.0.1:8000"
echo
echo "Masuk memakai salah satu akun contoh (kata sandi semua: password):"
echo "    inspektoratwondamaxx@gmail.com  - Super Admin (semua menu)"
echo "    operator@sishd.test             - OPD, mengajukan usulan"
echo "    verifikator@sishd.test          - Verifikator, tahap 1"
echo
echo "PENTING: ganti semua kata sandi tersebut sebelum dipakai sungguhan."
