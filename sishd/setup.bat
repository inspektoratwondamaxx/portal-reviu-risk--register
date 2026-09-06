@echo off
REM ---------------------------------------------------------------
REM  Pemasangan SISHD sekali jalan (Windows).
REM  Klik dua kali berkas ini, atau jalankan dari Terminal Laragon.
REM
REM  Skrip ini TIDAK memasang PHP/Composer/PostgreSQL - itu dipasang
REM  lebih dulu sesuai docs\CARA-MENJALANKAN.md bagian Persiapan.
REM ---------------------------------------------------------------

setlocal
cd /d "%~dp0"
title Pemasangan SISHD

echo.
echo ==========================================
echo   PEMASANGAN APLIKASI SISHD
echo ==========================================

echo.
echo [1/7] Memeriksa program yang dibutuhkan

where php >nul 2>&1
if errorlevel 1 goto :tidak_ada_php
echo   ada: php

where composer >nul 2>&1
if errorlevel 1 goto :tidak_ada_composer
echo   ada: composer

php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"
if errorlevel 1 goto :php_terlalu_lama
echo   versi PHP mencukupi

php -r "exit(extension_loaded('pdo_pgsql') ? 0 : 1);"
if errorlevel 1 goto :tidak_ada_pgsql
echo   ekstensi pdo_pgsql aktif

echo.
echo [2/7] Memasang komponen aplikasi (agak lama, mohon ditunggu)
call composer install --no-interaction --prefer-dist
if errorlevel 1 goto :gagal_composer

echo.
echo [3/7] Menyiapkan berkas pengaturan (.env)
if exist .env (
    echo   .env sudah ada - dibiarkan apa adanya
) else (
    copy .env.example .env >nul
    echo   .env dibuat dari contoh
)
call php artisan key:generate --force
if errorlevel 1 goto :gagal_kunci
echo   kunci aplikasi siap

echo.
echo [4/7] Memeriksa sambungan ke basis data
call php artisan db:show >nul 2>&1
if errorlevel 1 goto :gagal_database
echo   sambungan basis data berhasil

echo.
echo [5/7] Membuat tabel dan mengisi data contoh
call php artisan migrate:fresh --seed --force
if errorlevel 1 goto :gagal_migrasi

echo.
echo [6/7] Menyiapkan tampilan (CSS/JavaScript)
where npm >nul 2>&1
if errorlevel 1 (
    echo   npm tidak ditemukan - tampilan tidak dibangun.
    echo   Aplikasi tetap jalan, tetapi tampilannya akan polos tanpa warna.
    echo   Pasang Node.js lalu jalankan ulang berkas ini agar tampilannya rapi.
) else (
    call npm install --no-audit --no-fund
    call npm run build
    if errorlevel 1 goto :gagal_tampilan
    echo   tampilan siap
)

echo.
echo [7/7] Selesai
echo.
echo ==========================================
echo   PEMASANGAN BERHASIL
echo ==========================================
echo.
echo Langkah terakhir - jalankan aplikasinya dengan perintah:
echo.
echo     php artisan serve
echo.
echo Lalu buka alamat ini di peramban:  http://127.0.0.1:8000
echo.
echo Masuk memakai salah satu akun contoh (kata sandi semua: password):
echo     inspektoratwondamaxx@gmail.com  - Super Admin (semua menu)
echo     operator@sishd.test             - OPD, mengajukan usulan
echo     verifikator@sishd.test          - Verifikator, tahap 1
echo.
echo PENTING: ganti semua kata sandi tersebut sebelum dipakai sungguhan.
echo.
pause
exit /b 0

:tidak_ada_php
echo.
echo GAGAL: PHP belum terpasang atau belum dikenali.
echo Pasang dulu Laragon (sudah termasuk PHP) sesuai docs\CARA-MENJALANKAN.md langkah 1,
echo lalu jalankan berkas ini lewat "Terminal" bawaan Laragon.
goto :akhir_gagal

:tidak_ada_composer
echo.
echo GAGAL: Composer belum terpasang atau belum dikenali.
echo Laragon versi Full sudah menyertakan Composer - pastikan memakai Terminal Laragon.
goto :akhir_gagal

:php_terlalu_lama
echo.
echo GAGAL: Versi PHP terlalu lama. Dibutuhkan PHP 8.2 atau lebih baru.
echo Di Laragon: menu Laragon - PHP - Version - pilih PHP 8.2 atau lebih baru.
goto :akhir_gagal

:tidak_ada_pgsql
echo.
echo GAGAL: Ekstensi PHP "pdo_pgsql" belum aktif.
echo Di Laragon: klik kanan ikon Laragon - PHP - Extensions - centang "pdo_pgsql"
echo dan "pgsql", lalu hentikan dan jalankan ulang Laragon.
goto :akhir_gagal

:gagal_composer
echo.
echo GAGAL saat memasang komponen aplikasi (composer install).
echo Penyebab tersering: komputer tidak terhubung internet.
goto :akhir_gagal

:gagal_kunci
echo.
echo GAGAL saat membuat kunci aplikasi.
goto :akhir_gagal

:gagal_database
echo.
echo GAGAL: Tidak bisa menyambung ke PostgreSQL.
echo.
echo Periksa hal berikut di berkas .env (buka dengan Notepad):
echo     DB_DATABASE=sishd       ^<- nama basis data sudah dibuat?
echo     DB_USERNAME=sishd       ^<- nama pengguna basis data sudah benar?
echo     DB_PASSWORD=...         ^<- kata sandinya sudah diisi?
echo.
echo Cara membuat basis data ada di docs\CARA-MENJALANKAN.md langkah 3.
goto :akhir_gagal

:gagal_migrasi
echo.
echo GAGAL saat menyiapkan tabel basis data.
echo Pastikan ekstensi pg_trgm sudah dibuat - lihat docs\CARA-MENJALANKAN.md langkah 3.
goto :akhir_gagal

:gagal_tampilan
echo.
echo GAGAL saat menyiapkan tampilan (npm).
goto :akhir_gagal

:akhir_gagal
echo.
echo Silakan salin pesan galat di atas dan tanyakan kembali.
echo.
pause
exit /b 1
