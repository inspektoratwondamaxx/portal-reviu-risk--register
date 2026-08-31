# Laporan Hasil Pengujian — SISHD

**Sistem**: SISHD (Sistem Informasi Standar Harga Daerah)
**Versi/Rilis yang diuji**: ......................... *(diisi sesuai penomoran rilis instansi)*
**Periode pengujian**: ......................... *(diisi)*
**Disusun oleh**: ......................... *(diisi)*
**Dokumen rujukan**: `docs/RENCANA-DAN-KASUS-UJI.md`

---

## 1. Ringkasan Eksekutif

Pengujian otomatis atas 60 kasus uji pada enam modul inti SISHD dijalankan dan **seluruhnya lulus
(100%)** dengan 175 titik pemeriksaan (assertion), tanpa kegagalan tersisa. Modul yang telah
tercakup adalah bagian sistem yang paling berisiko bila keliru: batas akses antar-OPD dan
antar-role, rantai persetujuan berjenjang, pemetaan data usulan ke data induk, kaskade
perhitungan harga, dan evaluasi formula ASB.

Selama siklus pengujian ini ditemukan **4 cacat**, seluruhnya telah diperbaiki dan diverifikasi
ulang. Dua di antaranya berkategori Tinggi dan tidak terlihat melalui pemakaian normal lewat
peramban — keduanya baru terungkap justru karena pengujian otomatis dan pengujian batas dijalankan.

**Rekomendasi**: sistem **layak dilanjutkan ke tahap User Acceptance Testing (UAT)** bersama
perwakilan pengguna. Belum direkomendasikan langsung ke produksi sebelum kasus uji manual bertanda
wajib pada Bagian II dokumen rujukan (khususnya import/export Excel dan survei harga) dinyatakan
lulus, karena modul tersebut belum tercakup pengujian otomatis.

## 2. Ruang Lingkup dan Metodologi

Pengujian dilakukan secara otomatis menggunakan PHPUnit terhadap basis data PostgreSQL sungguhan
(bukan tiruan/mock), sehingga batasan basis data seperti NOT NULL, kunci asing, dan CHECK
constraint ikut teruji. Setiap kasus uji dijalankan pada basis data bersih (`RefreshDatabase`) agar
hasil satu pengujian tidak mempengaruhi pengujian lain.

Pengujian tidak berhenti pada "data tersimpan", melainkan ditelusuri sampai keadaan akhir yang
benar-benar penting bagi pengguna — misalnya usulan diikuti sampai termaterialisasi menjadi baris
data induk setelah seluruh tahap persetujuan terlampaui.

| Komponen | Spesifikasi |
|---|---|
| Bahasa/Framework | PHP 8.4.19, Laravel 11.56.1 |
| Basis data | PostgreSQL 16.13 dengan ekstensi `pg_trgm` |
| Basis data pengujian | `sishd_test` (terpisah dari basis data pengembangan) |
| Alat uji | PHPUnit via `php artisan test` |
| Otomasi berkelanjutan | GitHub Actions (`.github/workflows/sishd-tests.yml`) |
| Waktu eksekusi | ± 6 detik untuk seluruh rangkaian |

## 3. Ringkasan Statistik

| Kode | Modul | Jumlah Kasus Uji | Lulus | Gagal | Belum Diuji | % Lulus |
|---|---|---|---|---|---|---|
| A | Autentikasi & Otorisasi | 14 | 14 | 0 | 0 | 100% |
| B | Usulan OPD | 14 | 14 | 0 | 0 | 100% |
| C | Approval Berjenjang | 9 | 9 | 0 | 0 | 100% |
| D | API Publik | 8 | 8 | 0 | 0 | 100% |
| E | Kaskade Harga HSPK | 3 | 3 | 0 | 0 | 100% |
| F | Formula ASB | 10 | 10 | 0 | 0 | 100% |
| G | Pengujian Asap | 2 | 2 | 0 | 0 | 100% |
| **Total otomatis** | | **60** | **60** | **0** | **0** | **100%** |
| H–L | Modul manual (UAT) | 21 | – | – | 21 | Menunggu UAT |

## 4. Daftar Temuan

Seluruh temuan di bawah ini ditemukan **dan** diperbaiki dalam siklus pengembangan-pengujian ini.

| ID | Deskripsi | Keparahan | Modul Terkait | Status |
|---|---|---|---|---|
| T-01 | Kolom `nama_kegiatan` (NOT NULL) pada usulan ASB tidak pernah terisi karena form mengirim susunan field yang sama untuk semua jenis usulan. Kegagalan baru muncul saat usulan **disetujui**, bukan saat diajukan — sehingga data usulan tertahan dan proses persetujuan gagal di tengah jalan. | **Tinggi** | B — Usulan OPD | Selesai diperbaiki |
| T-02 | Nilai default kolom `tahapan_saat_ini` hanya didefinisikan di basis data, tidak di model. Akibatnya usulan yang dibuat lalu langsung direview dalam satu proses (skenario seeder/perintah terjadwal/pengujian) mengirim tahapan kosong dan gagal pada batasan NOT NULL. Tidak terlihat lewat peramban karena data dibaca ulang antar-permintaan. | **Tinggi** | C — Approval Berjenjang | Selesai diperbaiki |
| T-03 | Besaran (harga) usulan SBU hilang secara senyap menjadi 0 karena dikirim sebagai `harga` sementara kolom yang dipakai adalah `besaran`. Tidak ada pesan galat — data tersimpan namun bernilai salah. | **Sedang** | B — Usulan OPD | Selesai diperbaiki |
| T-04 | Filter tahun pada API publik mengabaikan filter dan menampilkan **seluruh** tahun ketika tahun yang diminta tidak ada dalam data, karena kondisi "tidak difilter" dan "difilter tetapi tidak ditemukan" tidak dibedakan. Berisiko menyesatkan sistem lain yang mengonsumsi API secara otomatis. | **Sedang** | D — API Publik | Selesai diperbaiki |

### Catatan kualitas kode (bukan cacat fungsional)

| ID | Deskripsi | Keparahan | Status |
|---|---|---|---|
| K-01 | Pada pemeriksaan hak akses usulan OPD terdapat cabang untuk role Verifikator yang tidak pernah tercapai, karena middleware rute sudah lebih dulu menutup akses role tersebut. Tidak menimbulkan celah keamanan (gagal ke arah menolak), namun menyesatkan pembaca kode karena menyiratkan jalur akses yang sebenarnya tidak ada. | Rendah | Selesai dirapikan |

## 5. Risiko dan Rekomendasi

### Risiko yang masih ada

| No | Risiko | Dampak | Saran Penanganan |
|---|---|---|---|
| 1 | Modul import/export Excel, survei harga, pemetaan kode, dan CRUD master data belum tercakup pengujian otomatis | Perubahan kode di masa depan dapat merusak modul ini tanpa terdeteksi | Uji manual pada UAT (Bagian II dokumen rujukan); tambahkan pengujian otomatis pada iterasi berikutnya, diprioritaskan pada import Excel karena melibatkan berkas dari luar sistem |
| 2 | Data yang diuji adalah data contoh, bukan data harga riil daerah | Perilaku pada volume dan variasi data sebenarnya belum terbukti | Lakukan uji coba memakai cuplikan data riil sebelum peluncuran |
| 3 | Belum ada pengujian beban (jumlah pengguna/data besar) | Kinerja saat dipakai serentak banyak OPD belum diketahui | Lakukan pengujian beban terpisah, terutama pada proses import dan export data besar |
| 4 | Integrasi SIPD Level 2 (tulis) belum tersedia | Pertukaran data dengan SIPD masih manual lewat Excel | Menunggu spesifikasi resmi API SIPD; endpoint baca-saja sudah tersedia sebagai fondasi |
| 5 | Akun demo memakai kata sandi seragam | Berisiko bila terbawa ke lingkungan produksi | Wajib mengganti seluruh kata sandi dan menonaktifkan akun demo sebelum produksi |

### Rekomendasi

1. **Lanjutkan ke UAT** bersama perwakilan OPD pengguna, dengan fokus pada kasus uji manual
   bertanda wajib **[W]**.
2. **Prioritaskan pengujian manual pada modul import/export Excel**, karena modul ini menerima
   berkas dari luar sistem dan merupakan jalur pertukaran data utama dengan SIPD saat ini.
3. **Ganti seluruh kata sandi akun demo** dan sesuaikan daftar pengguna dengan pegawai sebenarnya
   sebelum sistem dipakai produksi.
4. **Pertahankan pengujian otomatis pada CI**; setiap perbaikan cacat baru sebaiknya disertai satu
   kasus uji yang menutup cacat tersebut agar tidak terulang.

## 6. Lampiran

Rincian lengkap seluruh kasus uji beserta langkah, data uji, dan hasil yang diharapkan terdapat
pada dokumen `docs/RENCANA-DAN-KASUS-UJI.md`. Implementasi pengujian otomatis berada pada direktori
`tests/` dan dapat dijalankan ulang kapan saja dengan perintah `php artisan test`.
