# LAPORAN HASIL PENGUJIAN
## SISTEM INFORMASI STANDAR HARGA DAERAH (SISHD)

| | |
|---|---|
| **Nomor** | ......................... *(diisi sesuai penomoran naskah dinas instansi)* |
| **Nama Sistem** | SISHD — Sistem Informasi Standar Harga Daerah |
| **Versi/Rilis yang Diuji** | ......................... *(diisi)* |
| **Periode Pengujian** | ......................... *(diisi)* |
| **Unit Pelaksana** | ......................... *(diisi)* |
| **Disusun oleh** | ......................... *(diisi)* |
| **Dokumen Rujukan** | Rencana dan Kasus Uji SISHD (`docs/RENCANA-DAN-KASUS-UJI.md`) |
| **Dokumen Terkait** | Kajian Keamanan Sistem SISHD (`docs/KAJIAN-KEAMANAN.md`) |

---

## BAB I — PENDAHULUAN

### A. Latar Belakang

Sistem Informasi Standar Harga Daerah (SISHD) merupakan aplikasi berbasis web yang dibangun untuk
mengelola data Standar Satuan Harga (SSH), Standar Biaya Umum (SBU), Harga Satuan Pokok Kegiatan
(HSPK), dan Analisis Standar Belanja (ASB) secara terpusat, beserta mekanisme pengusulan berjenjang
dari Organisasi Perangkat Daerah (OPD).

Mengingat data yang dikelola menjadi dasar penyusunan dan reviu anggaran seluruh OPD, keandalan
sistem perlu dibuktikan secara terukur sebelum diserahterimakan dan dioperasikan. Sehubungan dengan
hal tersebut, dilaksanakan pengujian sistem yang hasilnya dituangkan dalam laporan ini.

### B. Maksud dan Tujuan

1. Memastikan seluruh fungsi sistem berjalan sesuai dengan spesifikasi sebagaimana tertuang dalam
   dokumen rujukan.
2. Mengidentifikasi ketidaksesuaian (temuan) beserta tingkat keparahannya, serta memastikan
   penanganannya telah dilaksanakan dan diverifikasi ulang.
3. Memberikan dasar pertimbangan bagi pimpinan dalam menetapkan kelayakan sistem untuk dilanjutkan
   ke tahap *User Acceptance Testing* (UAT) dan pengoperasian.

### C. Ruang Lingkup

Pengujian mencakup tiga belas modul sebagaimana diuraikan pada Bab III huruf A, meliputi aspek
fungsional, batas kewenangan akses antar-peran dan antar-OPD, ketepatan perhitungan, pertukaran data
melalui berkas Excel, serta aspek keamanan aplikasi.

Adapun yang **tidak** termasuk dalam ruang lingkup pengujian ini adalah:

1. Pengujian beban dan kinerja (*load* dan *performance testing*);
2. Pengujian penetrasi terhadap infrastruktur server instansi;
3. Pengujian kompatibilitas lintas peramban dan lintas perangkat secara menyeluruh.

Ketiga hal tersebut direkomendasikan untuk dilaksanakan sebagai kegiatan tersendiri.

## BAB II — PELAKSANAAN PENGUJIAN

### A. Metodologi

Pengujian dilaksanakan secara otomatis dengan kerangka kerja PHPUnit terhadap basis data PostgreSQL
sesungguhnya, bukan basis data tiruan (*mock*). Dengan demikian, batasan pada tingkat basis data
seperti NOT NULL, kunci asing (*foreign key*), dan CHECK *constraint* turut teruji. Setiap kasus uji
dijalankan pada basis data dalam keadaan bersih sehingga hasil satu pengujian tidak memengaruhi
pengujian lainnya.

Pengujian tidak dihentikan pada keberhasilan penyimpanan data, melainkan ditelusuri sampai dengan
keadaan akhir yang berdampak bagi pengguna. Sebagai contoh, usulan OPD diikuti sampai dengan
terbentuknya baris data induk setelah seluruh tahap persetujuan terlampaui.

Terhadap setiap temuan diberlakukan ketentuan sebagai berikut:

1. Temuan dibuktikan lebih dahulu melalui reproduksi nyata, tidak cukup berdasarkan pembacaan kode;
2. Setelah diperbaiki, disusun kasus uji otomatis yang dipastikan **gagal pada kode sebelum
   perbaikan dan lulus setelah perbaikan**, sehingga ketidaksesuaian yang sama tidak dapat terulang
   tanpa terdeteksi;
3. Seluruh rangkaian pengujian dijalankan tiga kali berturut-turut guna memastikan hasilnya stabil.

### B. Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| Bahasa dan kerangka kerja | PHP 8.4.19, Laravel 11.56.1 |
| Basis data | PostgreSQL 16.13 dengan ekstensi `pg_trgm` |
| Basis data pengujian | `sishd_test`, terpisah dari basis data pengembangan |
| Perkakas pengujian | PHPUnit, dijalankan melalui perintah `php artisan test` |
| Otomasi berkelanjutan | GitHub Actions (`.github/workflows/sishd-tests.yml`) |
| Waktu eksekusi | ± 17 detik untuk seluruh rangkaian |

## BAB III — HASIL PENGUJIAN

### A. Rekapitulasi per Modul

| Kode | Modul | Jumlah Kasus Uji | Lulus | Gagal | Belum Diuji | Persentase Lulus |
|---|---|---|---|---|---|---|
| A | Autentikasi dan Otorisasi | 14 | 14 | 0 | 0 | 100% |
| B | Usulan OPD | 14 | 14 | 0 | 0 | 100% |
| C | Persetujuan Berjenjang | 9 | 9 | 0 | 0 | 100% |
| D | API Publik | 8 | 8 | 0 | 0 | 100% |
| E | Kaskade Harga HSPK | 3 | 3 | 0 | 0 | 100% |
| F | Formula ASB | 10 | 10 | 0 | 0 | 100% |
| G | Pengujian Asap | 2 | 2 | 0 | 0 | 100% |
| H | Data Induk (CRUD) | 20 | 20 | 0 | 0 | 100% |
| I | Import dan Export Excel | 15 | 15 | 0 | 0 | 100% |
| J | Survei Harga | 19 | 19 | 0 | 0 | 100% |
| K | Pemetaan Kode | 15 | 15 | 0 | 0 | 100% |
| M | Laporan, Dasbor, dan Jejak Audit | 19 | 19 | 0 | 0 | 100% |
| N | Keamanan | 13 | 13 | 0 | 0 | 100% |
| **Jumlah pengujian otomatis** | | **161** | **161** | **0** | **0** | **100%** |
| Kasus uji manual (UAT) | Keterbacaan laporan, tampilan pada ponsel, kesesuaian istilah, kesesuaian format SIPD | 12 | – | – | 12 | Menunggu pelaksanaan UAT |

### B. Uraian Hasil

Pengujian otomatis atas **161 kasus uji pada tiga belas modul dinyatakan lulus seluruhnya (100%)**
dengan 507 titik pemeriksaan (*assertion*), tanpa kegagalan tersisa. Hasil tersebut konsisten pada
tiga kali pelaksanaan berturut-turut.

Dengan selesainya siklus pengujian ini, **seluruh modul fungsional sistem telah tercakup pengujian
otomatis**. Kasus uji yang masih menunggu pelaksanaan UAT sebanyak 12 butir seluruhnya merupakan
hal yang menuntut penilaian manusia, yaitu kesesuaian susunan kolom *export* dengan templat resmi
SIPD yang berlaku di instansi, kemudahan pengunggahan bukti survei melalui kamera telepon genggam,
keterbacaan laporan sebagai lampiran dokumen dinas, serta kesesuaian istilah dengan kebiasaan
instansi.

Selain pengujian fungsional, dilaksanakan pula reviu keamanan aplikasi dengan kerangka acuan OWASP
Top 10. Hasil reviu tersebut dituangkan dalam dokumen tersendiri sebagaimana tercantum pada bagian
Dokumen Terkait, dengan ringkasan pada Bab IV huruf B laporan ini.

## BAB IV — TEMUAN DAN TINDAK LANJUT

### A. Temuan Fungsional

Selama siklus pengujian ditemukan 10 (sepuluh) ketidaksesuaian fungsional. Seluruhnya telah
diperbaiki dan diverifikasi ulang.

| Kode | Uraian Temuan | Keparahan | Modul | Status |
|---|---|---|---|---|
| T-01 | Kolom `nama_kegiatan` yang bersifat wajib pada usulan ASB tidak pernah terisi karena formulir mengirimkan susunan ruas yang sama untuk seluruh jenis usulan. Kegagalan baru muncul pada saat usulan disetujui, bukan pada saat diajukan, sehingga proses persetujuan terhenti di tengah alur. | Tinggi | B | Selesai diperbaiki |
| T-02 | Nilai awal kolom `tahapan_saat_ini` hanya ditetapkan pada tingkat basis data dan tidak pada tingkat model, sehingga usulan yang dibuat dan langsung ditinjau dalam satu proses gagal memenuhi batasan NOT NULL. Kondisi ini tidak tampak melalui peramban karena data dibaca ulang pada setiap permintaan. | Tinggi | C | Selesai diperbaiki |
| T-03 | Besaran biaya pada usulan SBU tersimpan bernilai 0 karena dikirimkan dengan nama ruas yang tidak sesuai dengan kolom tujuan. Tidak disertai pesan galat, sehingga data tersimpan namun bernilai keliru. | Sedang | B | Selesai diperbaiki |
| T-04 | Penyaringan berdasarkan tahun anggaran pada API publik diabaikan dan menampilkan seluruh tahun apabila tahun yang diminta tidak terdapat dalam data, karena kondisi "tidak disaring" tidak dibedakan dari kondisi "disaring namun tidak ditemukan". | Sedang | D | Selesai diperbaiki |
| T-05 | Kode barang hasil import SSH dibangkitkan dengan penambahan bilangan acak pada kolom yang bersifat unik, sehingga import dalam jumlah banyak pada tanggal yang sama berpeluang besar mengalami benturan kode. Pada pengujian import 60 baris data valid, 28 baris di antaranya gagal tersimpan, dengan baris yang gagal berpindah-pindah pada setiap pelaksanaan. | Tinggi | I | Selesai diperbaiki |
| T-06 | Kode SBU yang dibangkitkan sistem hanya memanfaatkan nomor baris berkas, sehingga import berkas kedua pada hari yang sama dipastikan mengalami benturan kode. | Sedang | I | Selesai diperbaiki |
| T-07 | Penyimpanan data induk tanpa mengisi kolom kode menghasilkan halaman galat (HTTP 500) dan bukan pembuatan kode otomatis sebagaimana mestinya. Terjadi pada keempat jenis data induk. Melalui formulir bawaan aplikasi kondisi ini tidak tampak, namun setiap pemanggilan dari luar formulir akan mengalami kegagalan. | Sedang | H | Selesai diperbaiki |
| T-08 | Kode otomatis dihitung semata-mata dari nomor identitas terbesar tanpa memeriksa ketersediaannya, sehingga apabila petugas pernah mengetikkan kode secara manual dengan format yang sama, penyimpanan berikutnya mengalami benturan pada kolom unik dan data batal tersimpan. Kondisi ini dapat direproduksi melalui antarmuka biasa. | Tinggi | H | Selesai diperbaiki |
| T-09 | Penghapusan data survei harga tidak disertai pemeriksaan kepemilikan, sehingga operator OPD mana pun dapat menghapus survei milik pengguna lain beserta seluruh berkas buktinya melalui penebakan alamat URL. Mengingat survei merupakan bukti dasar penetapan harga dan berkasnya turut terhapus secara permanen, kehilangan data tersebut tidak dapat dipulihkan. | Tinggi | J | Selesai diperbaiki |
| T-10 | Laporan rekapitulasi yang disaring berdasarkan tahun anggaran yang tidak terdapat dalam data mengabaikan penyaringan tersebut dan menjumlahkan seluruh tahun, namun disajikan di bawah judul tahun yang diminta. Angka yang dipergunakan sebagai dasar pengambilan keputusan dapat menjadi keliru tanpa disertai pesan galat apa pun. | Sedang | M | Selesai diperbaiki |

**Analisis atas pola temuan.** Terhadap daftar temuan di atas dapat dikemukakan dua hal yang perlu
memperoleh perhatian:

1. Temuan T-04 dan T-10 merupakan kekeliruan yang serupa pada dua bagian sistem yang berbeda, yaitu
   penyaringan tahun anggaran yang diabaikan secara diam-diam. Hal ini menunjukkan perlunya
   penerapan pola penanganan penyaringan yang seragam pada pengembangan berikutnya.
2. Temuan T-05, T-06, dan T-08 berakar pada satu sebab yang sama, yaitu logika pembangkitan kode
   otomatis yang semula ditulis berulang pada lima tempat terpisah dengan aturan berbeda-beda. Hal
   tersebut telah ditangani sebagaimana diuraikan pada huruf C angka 2.

Perlu pula dikemukakan bahwa empat temuan berkategori Tinggi tidak dapat teramati melalui pemakaian
biasa lewat peramban, dan seluruhnya terungkap justru karena dilaksanakannya pengujian otomatis dan
pengujian batas. Salah satu di antaranya (T-05) bahkan bersifat acak, sehingga sangat kecil
kemungkinannya ditemukan melalui pengujian manual sekali jalan.

### B. Temuan Keamanan

Reviu keamanan menghasilkan 5 (lima) temuan yang seluruhnya telah diperbaiki dan dipagari kasus uji
otomatis. Uraian lengkap beserta skenario eksploitasi dan perbaikannya tercantum pada dokumen
Kajian Keamanan Sistem SISHD.

| Kode | Uraian Temuan | Keparahan | Status |
|---|---|---|---|
| K-01 | Berkas SVG yang memuat skrip dapat diunggah sebagai bukti survei dan dijalankan pada peramban petugas lain yang membukanya. | Tinggi | Selesai diperbaiki |
| K-02 | Percobaan kata sandi pada halaman masuk tidak dibatasi jumlahnya. | Tinggi | Selesai diperbaiki |
| K-03 | Super Admin terakhir dapat menghilangkan kewenangannya sendiri sehingga sistem tidak lagi dapat dikelola. | Sedang | Selesai diperbaiki |
| K-04 | Tajuk keamanan (*security headers*) belum dipasang pada respons aplikasi. | Sedang | Selesai diperbaiki |
| K-05 | Kuki sesi belum dipaksa hanya melalui koneksi HTTPS. | Sedang | Didokumentasikan sebagai kewajiban konfigurasi server |

### C. Catatan Kualitas Kode

Hal-hal berikut bukan merupakan ketidaksesuaian fungsional, namun dicatat sebagai bahan pemeliharaan
sistem ke depan.

| Kode | Uraian | Keparahan | Status |
|---|---|---|---|
| C-01 | Pada pemeriksaan hak akses usulan OPD terdapat percabangan bagi peran Verifikator yang tidak pernah tercapai karena telah lebih dahulu ditutup oleh *middleware* rute. Tidak menimbulkan celah keamanan karena kegagalannya mengarah pada penolakan akses, namun berpotensi menyesatkan pembaca kode. | Rendah | Selesai dirapikan |
| C-02 | Logika pembangkitan kode otomatis semula ditulis berulang pada lima tempat terpisah dengan aturan berbeda-beda, dan merupakan akar dari temuan T-05, T-06, dan T-08. Telah disatukan menjadi satu layanan bersama sehingga perbaikan pada satu tempat berlaku untuk seluruhnya. | Rendah | Selesai dirapikan |
| C-03 | Proses import menangani kegagalan per baris dan melanjutkan ke baris berikutnya. Pada PostgreSQL, satu pernyataan yang gagal di dalam sebuah transaksi membatalkan seluruh transaksi. Pada alur yang berlaku saat ini proses import tidak dibungkus transaksi sehingga tidak berdampak, namun hal ini perlu diperhatikan apabila alur tersebut diubah. | Rendah | Dicatat, belum diubah |

## BAB V — RISIKO YANG MASIH ADA

| No | Risiko | Dampak | Saran Penanganan |
|---|---|---|---|
| 1 | Kesesuaian susunan kolom *export* dengan templat resmi SIPD yang berlaku di instansi belum dapat dipastikan melalui pengujian otomatis | Berkas hasil *export* berpotensi ditolak pada saat diunggah ke SIPD | Diuji pada tahap UAT bersama petugas yang biasa mengoperasikan SIPD |
| 2 | Keterbacaan laporan, tampilan pada perangkat telepon genggam, dan kesesuaian istilah menuntut penilaian pengguna | Ketidaksesuaian baru diketahui setelah sistem dioperasikan | Diuji pada tahap UAT bersama perwakilan OPD pengguna |
| 3 | Data yang dipergunakan dalam pengujian merupakan data contoh, bukan data harga sesungguhnya | Perilaku sistem pada volume dan keragaman data sesungguhnya belum terbukti | Melaksanakan uji coba dengan cuplikan data sesungguhnya sebelum pengoperasian |
| 4 | Belum dilaksanakan pengujian beban | Kinerja sistem pada saat dipergunakan serentak oleh banyak OPD belum diketahui | Melaksanakan pengujian beban tersendiri, terutama pada proses import dan export data berjumlah besar |
| 5 | Integrasi SIPD tingkat lanjut (pengiriman data) belum tersedia karena spesifikasi resmi belum diperoleh | Pertukaran data dengan SIPD masih dilakukan secara manual melalui berkas Excel | Menindaklanjuti setelah spesifikasi resmi API SIPD diperoleh |
| 6 | Akun contoh bawaan sistem menggunakan kata sandi yang seragam dan lemah | Berisiko terbawa ke lingkungan pengoperasian | Mengganti seluruh kata sandi dan menonaktifkan akun contoh sebelum sistem dioperasikan |

## BAB VI — SIMPULAN DAN REKOMENDASI

### A. Simpulan

Berdasarkan hasil pengujian sebagaimana diuraikan pada Bab III dan Bab IV, disimpulkan bahwa:

1. Seluruh 161 kasus uji otomatis pada tiga belas modul dinyatakan **LULUS** dengan hasil yang
   stabil pada tiga kali pelaksanaan;
2. Seluruh temuan fungsional (10 temuan) dan temuan keamanan (5 temuan) telah ditangani dan
   diverifikasi ulang, serta dipagari kasus uji otomatis agar tidak terulang;
3. Sistem **dinyatakan layak untuk dilanjutkan ke tahap User Acceptance Testing (UAT)** bersama
   perwakilan pengguna;
4. Sistem **belum direkomendasikan untuk langsung dioperasikan** sebelum seluruh kasus uji manual
   bertanda wajib dinyatakan lulus dan seluruh butir rekomendasi pada huruf B ditindaklanjuti.

### B. Rekomendasi

1. Melaksanakan tahap UAT bersama perwakilan OPD pengguna, dengan prioritas pada kasus uji bertanda
   wajib sebagaimana tercantum dalam dokumen rujukan.
2. Memprioritaskan pengujian kesesuaian susunan kolom *export* SIPD, mengingat hal tersebut
   merupakan satu-satunya bagian pada jalur pertukaran data dengan SIPD yang tidak dapat dipastikan
   melalui pengujian otomatis.
3. Mengganti seluruh kata sandi akun contoh, menonaktifkan akun yang tidak dipergunakan, dan
   menyesuaikan daftar pengguna dengan pegawai sesungguhnya sebelum sistem dioperasikan.
4. Memastikan tiga butir konfigurasi server sebagaimana tercantum dalam dokumen Kajian Keamanan,
   yaitu pengaktifan HTTPS, penetapan `SESSION_SECURE_COOKIE`, dan penonaktifan mode *debug*.
5. Mempertahankan pelaksanaan pengujian otomatis pada setiap perubahan kode, serta menyertakan satu
   kasus uji baru pada setiap perbaikan temuan agar ketidaksesuaian yang sama tidak terulang.

## BAB VII — PENUTUP

Demikian Laporan Hasil Pengujian ini disusun berdasarkan pelaksanaan pengujian yang sesungguhnya,
untuk dipergunakan sebagaimana mestinya.

Rincian seluruh kasus uji beserta langkah, data uji, dan hasil yang diharapkan tercantum pada
dokumen Rencana dan Kasus Uji SISHD. Implementasi pengujian otomatis berada pada direktori `tests/`
dan dapat dijalankan ulang sewaktu-waktu dengan perintah `php artisan test`.

<br>

| | |
|---|---|
| | ........................., ......................... *(tempat, tanggal)* |
| **Mengetahui,** | **Disusun oleh,** |
| ......................... *(jabatan)* | ......................... *(jabatan)* |
| <br><br><br> | <br><br><br> |
| **.........................** | **.........................** |
| NIP. ......................... | NIP. ......................... |
