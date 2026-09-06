# Laporan Hasil Pengujian — SISHD

**Sistem**: SISHD (Sistem Informasi Standar Harga Daerah)
**Versi/Rilis yang diuji**: ......................... *(diisi sesuai penomoran rilis instansi)*
**Periode pengujian**: ......................... *(diisi)*
**Disusun oleh**: ......................... *(diisi)*
**Dokumen rujukan**: `docs/RENCANA-DAN-KASUS-UJI.md`

---

## 1. Ringkasan Eksekutif

Pengujian otomatis atas 161 kasus uji pada tiga belas modul SISHD dijalankan dan **seluruhnya lulus
(100%)** dengan 507 titik pemeriksaan (assertion), tanpa kegagalan tersisa. Dengan siklus ini
**seluruh modul fungsional sistem telah tercakup pengujian otomatis** — tidak ada lagi modul yang
sepenuhnya bergantung pada pengujian manual. Cakupannya meliputi batas akses antar-OPD dan
antar-role, rantai persetujuan berjenjang, pemetaan data usulan ke data induk, kaskade perhitungan
harga, evaluasi formula ASB, import/export Excel sebagai satu-satunya jalur yang menerima berkas
dari luar sistem, CRUD master data, survei harga, pemetaan kode, serta laporan, angka dashboard,
dan jejak audit.

Selain itu dilakukan **review keamanan tersendiri** (OWASP Top 10) yang menemukan 5 celah,
seluruhnya sudah diperbaiki dan dipagari kasus uji — rinciannya pada dokumen
`docs/KAJIAN-KEAMANAN.md`.

Selama seluruh siklus pengujian ditemukan **10 cacat fungsional**, seluruhnya telah diperbaiki dan
diverifikasi ulang. Empat di antaranya berkategori Tinggi dan tidak terlihat melalui pemakaian
normal lewat peramban — semuanya baru terungkap justru karena pengujian otomatis dan pengujian
batas dijalankan. Satu di antaranya (T-05) bahkan bersifat **acak**: baris yang gagal
berpindah-pindah setiap kali import dijalankan, sehingga hampir mustahil ditemukan lewat pengujian
manual sekali jalan. Karena itu rangkaian pengujian dijalankan tiga kali berturut-turut untuk
memastikan perbaikannya benar-benar stabil, bukan kebetulan lolos.

Perlu dicatat bahwa dua cacat (T-04 dan T-10) adalah **kekeliruan yang sama persis di dua tempat
berbeda**: filter tahun yang diam-diam diabaikan ketika tahun yang diminta tidak ada dalam data,
sehingga sistem menampilkan seluruh tahun di bawah judul satu tahun tertentu. Pada laporan rekap
(T-10) ini berarti angka yang dipakai sebagai dasar pengambilan keputusan bisa salah tanpa satu
pun pesan galat.

**Rekomendasi**: sistem **layak dilanjutkan ke tahap User Acceptance Testing (UAT)** bersama
perwakilan pengguna. Belum direkomendasikan langsung ke produksi sebelum kasus uji manual bertanda
wajib pada Bagian II dokumen rujukan dinyatakan lulus — kini tersisa hal-hal yang memang menuntut
penilaian manusia (kesesuaian format SIPD instansi, unggah bukti dari kamera ponsel, keterbacaan
laporan dan tampilan pada ponsel), bukan lagi cakupan fungsional yang belum teruji.

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
| Waktu eksekusi | ± 17 detik untuk seluruh rangkaian |

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
| H | Master Data (CRUD) | 20 | 20 | 0 | 0 | 100% |
| I | Import & Export Excel | 15 | 15 | 0 | 0 | 100% |
| J | Survei Harga | 19 | 19 | 0 | 0 | 100% |
| K | Pemetaan Kode | 15 | 15 | 0 | 0 | 100% |
| M | Laporan, Dashboard & Jejak Audit | 19 | 19 | 0 | 0 | 100% |
| N | Keamanan | 13 | 13 | 0 | 0 | 100% |
| **Total otomatis** | | **161** | **161** | **0** | **0** | **100%** |
| Sisa manual (UAT) | Keterbacaan laporan, tampilan ponsel, istilah, format SIPD | 12 | – | – | 12 | Menunggu UAT |

## 4. Daftar Temuan

Seluruh temuan di bawah ini ditemukan **dan** diperbaiki dalam siklus pengembangan-pengujian ini.

| ID | Deskripsi | Keparahan | Modul Terkait | Status |
|---|---|---|---|---|
| T-01 | Kolom `nama_kegiatan` (NOT NULL) pada usulan ASB tidak pernah terisi karena form mengirim susunan field yang sama untuk semua jenis usulan. Kegagalan baru muncul saat usulan **disetujui**, bukan saat diajukan — sehingga data usulan tertahan dan proses persetujuan gagal di tengah jalan. | **Tinggi** | B — Usulan OPD | Selesai diperbaiki |
| T-02 | Nilai default kolom `tahapan_saat_ini` hanya didefinisikan di basis data, tidak di model. Akibatnya usulan yang dibuat lalu langsung direview dalam satu proses (skenario seeder/perintah terjadwal/pengujian) mengirim tahapan kosong dan gagal pada batasan NOT NULL. Tidak terlihat lewat peramban karena data dibaca ulang antar-permintaan. | **Tinggi** | C — Approval Berjenjang | Selesai diperbaiki |
| T-03 | Besaran (harga) usulan SBU hilang secara senyap menjadi 0 karena dikirim sebagai `harga` sementara kolom yang dipakai adalah `besaran`. Tidak ada pesan galat — data tersimpan namun bernilai salah. | **Sedang** | B — Usulan OPD | Selesai diperbaiki |
| T-04 | Filter tahun pada API publik mengabaikan filter dan menampilkan **seluruh** tahun ketika tahun yang diminta tidak ada dalam data, karena kondisi "tidak difilter" dan "difilter tetapi tidak ditemukan" tidak dibedakan. Berisiko menyesatkan sistem lain yang mengonsumsi API secara otomatis. | **Sedang** | D — API Publik | Selesai diperbaiki |
| T-05 | Kode barang hasil import SSH dibangkitkan dari nomor baris ditambah angka acak 0–999 pada kolom yang UNIQUE. Mengimpor puluhan baris pada tanggal yang sama berpeluang besar bentrok sendiri: baris yang datanya benar gagal masuk dengan pesan galat SQL mentah, dan baris yang gagal berpindah-pindah setiap kali dijalankan. Terbukti pada pengujian impor 60 baris valid — 28 baris gagal. | **Tinggi** | I — Import Excel | Selesai diperbaiki |
| T-06 | Kode SBU yang dibangkitkan sistem (saat kolom kode dikosongkan) hanya memakai nomor baris, sehingga mengimpor berkas kedua pada hari yang sama pasti bentrok pada nomor baris yang sama. | **Sedang** | I — Import Excel | Selesai diperbaiki |
| T-07 | Menyimpan master data **tanpa mengisi kolom kode** menyebabkan halaman galat (HTTP 500), bukan pembuatan kode otomatis, karena kolom opsional yang tidak dikirim sama sekali tidak ikut dikembalikan oleh validator lalu tetap dibaca. Terjadi pada keempat jenis master (SSH, SBU, HSPK, ASB). Lewat form bawaan aplikasi kolomnya selalu ikut terkirim sehingga tidak terlihat, namun setiap pemanggilan dari luar form (integrasi, skrip, atau form yang menyembunyikan kolom kode) akan gagal. | **Sedang** | H — Master Data | Selesai diperbaiki |
| T-08 | Kode otomatis (`SSH-yymmdd-NNNN` dan sejenisnya) dihitung hanya dari nomor id terbesar, tanpa memeriksa apakah kode itu sudah terpakai. Bila petugas pernah mengetik kode manual dengan format yang sama — hal yang wajar karena ia menyalin pola dari baris lain — penyimpanan berikutnya bentrok pada kolom UNIQUE dan berakhir sebagai halaman galat dengan data batal tersimpan. Terbukti dapat direproduksi dari antarmuka biasa. Perbaikannya menyatukan pembuatan kode master dan kode import ke satu layanan bersama yang menaikkan nomor sampai benar-benar belum terpakai. | **Tinggi** | H — Master Data | Selesai diperbaiki |
| T-09 | Penghapusan data survei harga tidak memeriksa kepemilikan: operator OPD mana pun dapat menghapus survei milik pengguna lain beserta seluruh berkas buktinya hanya dengan menebak URL-nya. Karena survei adalah bukti dasar penetapan harga dan berkasnya ikut terhapus permanen, kehilangan ini tidak dapat dipulihkan. Kini dibatasi pada surveyor pembuatnya sendiri atau admin harga (Super Admin/Admin SSH). | **Tinggi** | J — Survei Harga | Selesai diperbaiki |
| T-10 | Laporan rekap yang difilter ke tahun anggaran yang tidak ada dalam data **mengabaikan filternya** dan menjumlahkan seluruh tahun, ditampilkan di bawah judul tahun yang diminta. Kekeliruan yang sama persis dengan T-04 pada API publik, kali ini pada laporan yang dipakai sebagai dasar pengambilan keputusan — angkanya salah tanpa satu pun pesan galat, sehingga tidak ada yang memicu kecurigaan pembacanya. | **Sedang** | M — Laporan | Selesai diperbaiki |

### Catatan kualitas kode (bukan cacat fungsional)

| ID | Deskripsi | Keparahan | Status |
|---|---|---|---|
| K-01 | Pada pemeriksaan hak akses usulan OPD terdapat cabang untuk role Verifikator yang tidak pernah tercapai, karena middleware rute sudah lebih dulu menutup akses role tersebut. Tidak menimbulkan celah keamanan (gagal ke arah menolak), namun menyesatkan pembaca kode karena menyiratkan jalur akses yang sebenarnya tidak ada. | Rendah | Selesai dirapikan |
| K-02 | Logika pembuatan kode otomatis semula ditulis ulang di lima tempat terpisah (empat controller master data dan satu layanan import) dengan aturan yang berbeda-beda — inilah akar T-05, T-06, dan T-08. Kini disatukan menjadi satu layanan `App\Services\KodeGenerator` yang dipakai kelimanya, sehingga perbaikan di satu tempat berlaku untuk semuanya. | Rendah | Selesai dirapikan |
| K-03 | Proses import menangani galat per baris dan melanjutkan ke baris berikutnya. Pada PostgreSQL, satu pernyataan yang gagal **di dalam sebuah transaksi** membatalkan seluruh transaksi, sehingga bila proses import dijalankan di dalam transaksi (mis. dibungkus `DB::transaction`), satu baris bermasalah akan menggagalkan seluruh sisa berkas. Pada alur produksi saat ini import tidak dibungkus transaksi sehingga tidak berdampak, namun perlu diperhatikan bila alur ini diubah. | Rendah | Dicatat, belum diubah |

## 5. Risiko dan Rekomendasi

### Risiko yang masih ada

| No | Risiko | Dampak | Saran Penanganan |
|---|---|---|---|
| 1 | Yang tersisa tanpa pengujian otomatis hanyalah hal yang memang menuntut penilaian manusia: keterbacaan laporan, tampilan pada ponsel, kesesuaian istilah, dan kecocokan format export dengan templat SIPD instansi | Ketidakcocokan baru diketahui saat dipakai pengguna sebenarnya | Uji manual pada UAT (Bagian II dokumen rujukan). Seluruh modul fungsional — termasuk laporan, dashboard, dan jejak audit — kini sudah tercakup pengujian otomatis (Modul A–M) |
| 2 | Data yang diuji adalah data contoh, bukan data harga riil daerah | Perilaku pada volume dan variasi data sebenarnya belum terbukti | Lakukan uji coba memakai cuplikan data riil sebelum peluncuran |
| 3 | Belum ada pengujian beban (jumlah pengguna/data besar) | Kinerja saat dipakai serentak banyak OPD belum diketahui | Lakukan pengujian beban terpisah, terutama pada proses import dan export data besar |
| 4 | Integrasi SIPD Level 2 (tulis) belum tersedia | Pertukaran data dengan SIPD masih manual lewat Excel | Menunggu spesifikasi resmi API SIPD; endpoint baca-saja sudah tersedia sebagai fondasi |
| 5 | Akun demo memakai kata sandi seragam | Berisiko bila terbawa ke lingkungan produksi | Wajib mengganti seluruh kata sandi dan menonaktifkan akun demo sebelum produksi |

### Rekomendasi

1. **Lanjutkan ke UAT** bersama perwakilan OPD pengguna, dengan fokus pada kasus uji manual
   bertanda wajib **[W]**.
2. **Prioritaskan pengujian manual pada kesesuaian format export SIPD**, karena susunan kolomnya
   harus dicocokkan dengan templat resmi SIPD yang berlaku di daerah masing-masing — satu-satunya
   hal pada jalur pertukaran data dengan SIPD yang tidak dapat dipastikan lewat pengujian otomatis.
3. **Ganti seluruh kata sandi akun demo** dan sesuaikan daftar pengguna dengan pegawai sebenarnya
   sebelum sistem dipakai produksi.
4. **Pertahankan pengujian otomatis pada CI**; setiap perbaikan cacat baru sebaiknya disertai satu
   kasus uji yang menutup cacat tersebut agar tidak terulang.

## 6. Lampiran

Rincian lengkap seluruh kasus uji beserta langkah, data uji, dan hasil yang diharapkan terdapat
pada dokumen `docs/RENCANA-DAN-KASUS-UJI.md`. Implementasi pengujian otomatis berada pada direktori
`tests/` dan dapat dijalankan ulang kapan saja dengan perintah `php artisan test`.
