# Cara Menjalankan Aplikasi SISHD

Panduan ini ditulis untuk **pengguna yang tidak berlatar belakang IT**. Ikuti berurutan dari atas.
Setiap langkah menyebutkan **apa yang seharusnya terlihat** kalau berhasil, supaya Anda tahu boleh
lanjut atau tidak.

**Perkiraan waktu**: 30–60 menit untuk pemasangan pertama. Setelah itu, menjalankannya hanya butuh
1 menit (lihat bagian *Menjalankan Lagi di Lain Hari*).

> **Cara tercepat kalau ada staf IT/programmer**: serahkan folder ini kepada mereka dan sebutkan
> "aplikasi Laravel 11, butuh PHP 8.2+, PostgreSQL 16, dan Node.js". Bagi mereka ini pekerjaan
> 10 menit. Panduan di bawah untuk Anda yang mengerjakannya sendiri.

---

## Gambaran: apa saja yang akan dipasang

Aplikasi ini tidak bisa langsung diklik seperti Microsoft Word. Ia perlu tiga "mesin" pendukung:

| Yang dipasang | Gunanya | Sekali pasang? |
|---|---|---|
| **Laragon** | Mesin penjalan aplikasi (PHP), sekaligus pengunduh komponen (Composer) dan penyusun tampilan (Node.js) | Ya |
| **PostgreSQL** | Tempat menyimpan seluruh data harga | Ya |
| **Berkas aplikasi SISHD** | Aplikasinya sendiri | Ya |

Sesudah ketiganya terpasang, cukup jalankan satu berkas dan buka peramban (Chrome/Edge).

---

## Langkah 1 — Pasang Laragon

Laragon menyediakan PHP, Composer, dan Node.js sekaligus, jadi tidak perlu memasang satu per satu.

1. Buka **https://laragon.org/download/**
2. Unduh **Laragon Full** (bukan Lite — versi Full sudah termasuk Composer dan Node.js).
3. Klik dua kali berkas hasil unduhan, lalu tekan **Next → Next → Install** sampai selesai.
   Biarkan semua pilihan pada pengaturan bawaannya.
4. Setelah terpasang, buka **Laragon** dari menu Start.

**Berhasil bila**: muncul jendela kecil Laragon dengan tombol *Start All*, *Web*, *Database*, *Terminal*.

### 1b. Pastikan versi PHP-nya 8.2 atau lebih baru

1. Pada jendela Laragon, klik kanan di area kosongnya → **PHP** → **Version**.
2. Pilih **PHP 8.2** atau yang lebih baru (8.3 / 8.4 juga boleh).

### 1c. Aktifkan sambungan ke PostgreSQL

Bawaan Laragon belum menyalakan penghubung ke PostgreSQL, jadi perlu dinyalakan:

1. Klik kanan di jendela Laragon → **PHP** → **Extensions**.
2. Beri centang pada **pdo_pgsql** dan **pgsql**.
3. Klik **Stop All**, lalu **Start All** (supaya perubahannya berlaku).

> Kalau menu Extensions tidak ada di versi Laragon Anda, lihat bagian *Masalah Umum* nomor 3.

---

## Langkah 2 — Pasang PostgreSQL

1. Buka **https://www.postgresql.org/download/windows/** → klik **Download the installer**.
2. Unduh versi **16** (versi 15 atau 17 juga bisa).
3. Jalankan pemasangnya, tekan **Next** terus. Saat diminta:
   - **Password**: isi kata sandi untuk pengguna `postgres`.
     **Tulis kata sandi ini di tempat aman — akan dipakai di langkah berikutnya.**
   - **Port**: biarkan **5432**.
   - **Stack Builder** di akhir: boleh dilewati (hapus centangnya).

**Berhasil bila**: di menu Start muncul folder **PostgreSQL 16** berisi aplikasi **pgAdmin 4**.

---

## Langkah 3 — Buat basis data untuk SISHD

Kita akan membuat "wadah" datanya. Semua lewat tampilan klik, tanpa mengetik perintah rumit.

1. Buka **pgAdmin 4** dari menu Start (agak lama saat pertama kali dibuka).
2. Kalau diminta *master password*, buat bebas dan catat.
3. Di panel kiri, klik **Servers** → **PostgreSQL 16**. Masukkan kata sandi `postgres` dari Langkah 2.
4. Klik kanan **Databases** → **Create** → **Database…**
   - **Database**: ketik `sishd`
   - Klik **Save**
5. Sekarang buat penggunanya. Klik kanan **Login/Group Roles** → **Create** → **Login/Group Role…**
   - Tab **General** → **Name**: `sishd`
   - Tab **Definition** → **Password**: `sishd`
   - Tab **Privileges** → nyalakan **Can login?** dan **Superuser?**
   - Klik **Save**

> **Kenapa Superuser?** Aplikasi ini memakai fitur pencarian kemiripan PostgreSQL (`pg_trgm`) untuk
> mendeteksi data ganda. Menyalakannya butuh hak superuser. Untuk pemakaian sungguhan di server
> instansi, mintalah admin basis data menyiapkan hak yang lebih terbatas.

6. Terakhir, hubungkan basis data `sishd` dengan penggunanya:
   klik kanan basis data **sishd** → **Query Tool**, tempelkan baris berikut lalu tekan tombol ▶ (Execute):

```sql
ALTER DATABASE sishd OWNER TO sishd;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
```

**Berhasil bila**: muncul pesan hijau *Query returned successfully*.

---

## Langkah 4 — Ambil berkas aplikasinya

1. Buka **https://github.com/inspektoratwondamaxx/portal-reviu-risk--register**
2. Klik tombol hijau **Code** → **Download ZIP**.
3. Klik kanan berkas ZIP hasil unduhan → **Extract All…** → simpan misalnya di `C:\SISHD`
4. Buka folder hasil ekstrak, lalu masuk ke folder bernama **`sishd`**.

> Di dalam folder `sishd` inilah ada berkas `setup.bat`, `artisan`, dan folder `app`.
> Kalau tidak menemukannya, berarti Anda masih di folder luarnya — masuk satu tingkat lagi.

---

## Langkah 5 — Isi pengaturan sambungan basis data

1. Di folder `sishd`, cari berkas bernama **`.env.example`**.
2. Salin berkas itu (Ctrl+C lalu Ctrl+V), lalu ganti nama salinannya menjadi **`.env`** (titik di depan, tanpa akhiran lain).
3. Klik kanan `.env` → **Open with** → **Notepad**.
4. Pastikan lima baris ini isinya seperti berikut, lalu simpan (Ctrl+S):

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sishd
DB_USERNAME=sishd
DB_PASSWORD=sishd
```

> Kalau di Langkah 3 Anda memakai kata sandi selain `sishd`, tulis kata sandi Anda di baris
> `DB_PASSWORD`.

> **Windows menyembunyikan akhiran berkas?** Di File Explorer, buka tab **View** lalu centang
> **File name extensions**, supaya nama berkas tidak keliru menjadi `.env.txt`.

---

## Langkah 6 — Jalankan pemasangan otomatis

1. Buka jendela **Laragon** → klik tombol **Terminal**.
2. Ketik perintah berikut (sesuaikan bila folder Anda berbeda), lalu tekan Enter:

```
cd C:\SISHD\portal-reviu-risk--register-main\sishd
```

3. Lalu ketik:

```
setup.bat
```

Proses ini mengunduh komponen dan menyiapkan tabel — **butuh 5–15 menit** tergantung kecepatan
internet. Biarkan berjalan.

**Berhasil bila**: di akhir muncul tulisan

```
==========================================
  PEMASANGAN BERHASIL
==========================================
```

Kalau muncul tulisan **GAGAL**, jangan panik — pesannya sudah menjelaskan apa yang perlu
diperbaiki. Lihat juga bagian *Masalah Umum* di bawah.

---

## Langkah 7 — Jalankan dan buka aplikasinya

1. Masih di Terminal yang sama, ketik:

```
php artisan serve
```

2. Muncul tulisan `Server running on [http://127.0.0.1:8000]`.
   **Biarkan jendela Terminal ini tetap terbuka** — inilah yang membuat aplikasi tetap hidup.
3. Buka Chrome atau Edge, ketik alamat: **http://127.0.0.1:8000**

**Berhasil bila**: muncul halaman biru bertuliskan *Sistem Informasi Standar Harga Daerah* dengan
kotak pencarian.

4. Untuk masuk ke bagian dalam (dashboard), klik **Login** di kanan atas.

---

## Akun untuk mencoba

Kata sandi semuanya sama: **`password`**

| Email | Perannya | Yang bisa dilihat |
|---|---|---|
| `inspektoratwondamaxx@gmail.com` | Super Admin | Semua menu |
| `operator@sishd.test` | Operator OPD | Mengajukan usulan harga |
| `verifikator@sishd.test` | Verifikator (tahap 1) | Memeriksa usulan masuk |
| `timstandarharga@sishd.test` | Tim Standar Harga (tahap 2) | Persetujuan tahap kedua |
| `pejabat@sishd.test` | Pejabat Berwenang (tahap 3) | Persetujuan akhir |
| `pimpinan@sishd.test` | Pimpinan | Hanya dashboard ringkasan |

> ⚠️ **Peringatan keamanan**: akun-akun di atas hanya untuk uji coba dan kata sandinya mudah
> ditebak. **Sebelum dipakai sungguhan, ganti semua kata sandi dan hapus akun yang tidak dipakai**
> (menu **Sistem → User**).

### Alur yang menarik untuk dicoba

1. Masuk sebagai **operator@sishd.test** → menu **Usulan Saya** → **Ajukan Usulan** → isi barang baru.
2. Keluar, masuk sebagai **verifikator@sishd.test** → menu **Verifikasi Usulan** → setujui.
3. Keluar, masuk sebagai **timstandarharga@sishd.test** → setujui lagi.
4. Keluar, masuk sebagai **pejabat@sishd.test** → setujui.
5. Barunya baru muncul di **Master Data → SSH** dan di situs publik.

Inilah inti sistemnya: OPD tidak bisa mengubah data harga langsung — semua harus melewati tiga
tahap persetujuan.

---

## Menjalankan Lagi di Lain Hari

Tidak perlu mengulang pemasangan. Cukup:

1. Buka **Laragon** → **Start All**.
2. Klik **Terminal**, lalu ketik:

```
cd C:\SISHD\portal-reviu-risk--register-main\sishd
php artisan serve
```

3. Buka **http://127.0.0.1:8000**

**Menghentikan aplikasi**: tekan `Ctrl + C` di Terminal, lalu tutup jendelanya.

---

## Masalah Umum

**1. Peramban bilang "situs tidak dapat dijangkau"**
Jendela Terminal dengan `php artisan serve` kemungkinan sudah tertutup. Buka lagi dan jalankan
ulang perintahnya. Pastikan juga alamatnya `http://127.0.0.1:8000` (bukan `https`).

**2. Muncul "GAGAL: Tidak bisa menyambung ke PostgreSQL"**
Berarti isi `.env` belum cocok dengan basis data yang dibuat di Langkah 3. Periksa ejaan nama
basis data, nama pengguna, dan kata sandinya. Pastikan juga PostgreSQL sedang berjalan (cari
"Services" di Start → cari `postgresql` → statusnya harus *Running*).

**3. Muncul "Ekstensi PHP pdo_pgsql belum aktif"**
Ulangi Langkah 1c. Kalau menu Extensions tidak tersedia:
buka folder PHP Laragon (biasanya `C:\laragon\bin\php\php-8.x\`), buka berkas `php.ini` dengan
Notepad, cari dua baris `;extension=pdo_pgsql` dan `;extension=pgsql`, **hapus tanda titik-koma di
depannya**, simpan, lalu **Stop All → Start All** di Laragon.

**4. Tampilannya polos tanpa warna**
Tampilannya belum dibangun. Di Terminal, pada folder `sishd`, jalankan:
```
npm install
npm run build
```

**5. Halaman error berwarna merah saat dibuka**
Salin tulisan barisan pertama pesan galatnya, lalu tanyakan kembali — pesan itu biasanya sudah
menunjuk penyebabnya.

**6. Ingin mengulang data dari awal (data contoh saja)**
Di Terminal, pada folder `sishd`:
```
php artisan migrate:fresh --seed
```
⚠️ Perintah ini **menghapus seluruh data** dan menggantinya dengan data contoh. Jangan dijalankan
kalau sudah berisi data sungguhan.

---

## Catatan untuk Tahap Selanjutnya

Yang dijelaskan di panduan ini adalah cara menjalankan di **komputer sendiri**, untuk dilihat dan
diuji. Supaya bisa dipakai bersama seluruh OPD, aplikasinya perlu dipasang di **server instansi**
dan diberi alamat yang bisa dibuka semua orang. Untuk itu diperlukan bantuan staf IT/Diskominfo,
dan sebelum itu perlu diselesaikan dulu:

- Mengganti seluruh kata sandi akun contoh dan menyesuaikan daftar pengguna dengan pegawai
  sebenarnya.
- Mengganti data contoh dengan data harga daerah yang sungguhan.
- Menjalankan pengujian penerimaan (UAT) — dokumennya sudah tersedia di
  `docs/BERITA-ACARA-UAT.md` dan `docs/RENCANA-DAN-KASUS-UJI.md`.

---

## Untuk Pengguna Linux atau macOS

Persiapan sama (PHP 8.2+, Composer, PostgreSQL 16, Node.js), lalu dari dalam folder `sishd`:

```bash
bash setup.sh
php artisan serve
```
