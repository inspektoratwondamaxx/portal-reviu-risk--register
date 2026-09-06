# BERITA ACARA
# USER ACCEPTANCE TESTING (UAT)

**Nomor**: ................................................ *(diisi sesuai penomoran instansi)*

Pada hari ini, ...................... tanggal ...................... bulan ......................
tahun ......................, bertempat di ................................................,
telah dilaksanakan User Acceptance Testing (UAT) atas sistem:

| | |
|---|---|
| **Nama Sistem** | SISHD — Sistem Informasi Standar Harga Daerah |
| **Versi / Rilis** | ................................................ |
| **Lingkungan Pengujian** | ☐ Pengembangan ☐ Staging ☐ Produksi |
| **Alamat Akses** | ................................................ |

---

## 1. Dasar Pelaksanaan

1. ................................................................................
   *(mis. kontrak/perjanjian kerja/surat tugas — diisi sesuai dokumen instansi)*
2. Jadwal pengembangan dan serah terima sistem.
3. Dokumen `Rencana dan Kasus Uji — SISHD` sebagai acuan skenario pengujian.

## 2. Ruang Lingkup Pengujian

UAT ini mencakup modul-modul berikut:

- ☐ Autentikasi & Otorisasi (hak akses per role dan pemisahan data antar-OPD)
- ☐ Usulan OPD (pengajuan SSH/SBU/HSPK/ASB beserta validasinya)
- ☐ Approval Berjenjang (Verifikator → Tim Standar Harga → Pejabat Berwenang)
- ☐ Master Data (SSH, SBU, HSPK, ASB)
- ☐ Import & Export Excel, termasuk format SIPD
- ☐ Survei Harga
- ☐ Pemetaan Kode & Laporan
- ☐ Situs Publik & API Publik
- ☐ Dashboard

## 3. Pihak yang Terlibat

| No | Nama | Jabatan / Instansi | Peran dalam Pengujian |
|---|---|---|---|
| 1 | .......................... | .......................... | Perwakilan Pengguna |
| 2 | .......................... | .......................... | Perwakilan Pengguna |
| 3 | .......................... | .......................... | Penguji |
| 4 | .......................... | .......................... | Pengembang |
| 5 | .......................... | .......................... | .......................... |

## 4. Hasil Pengujian

Kolom **Hasil** diisi: **L** (Lulus) / **LC** (Lulus dengan Catatan) / **TL** (Tidak Lulus).
Rincian langkah tiap skenario merujuk dokumen `Rencana dan Kasus Uji — SISHD`.

### 4.1 Modul Terverifikasi Otomatis

Modul berikut telah diverifikasi melalui 161 kasus uji otomatis dengan hasil seluruhnya lulus.
Pengujian pada sesi UAT bersifat konfirmasi oleh perwakilan pengguna.

| No | Modul / Fitur | Skenario Diuji | Hasil | Catatan |
|---|---|---|---|---|
| 1 | Autentikasi | Login dengan akun masing-masing role | | |
| 2 | Otorisasi | OPD tidak dapat melihat usulan OPD lain | | |
| 3 | Otorisasi | Role tanpa kewenangan ditolak saat membuka menu teknis | | |
| 4 | Usulan OPD | Pengajuan usulan SSH beserta validasi field wajib | | |
| 5 | Usulan OPD | Pengajuan usulan SBU, HSPK, dan ASB | | |
| 6 | Approval Berjenjang | Persetujuan berurutan tiga tahap sampai data induk terbentuk | | |
| 7 | Approval Berjenjang | Reviewer tahap lain tidak dapat mendahului giliran | | |
| 8 | Approval Berjenjang | Usulan direvisi dan diajukan ulang dari tahap awal | | |
| 9 | Kaskade Harga | Perubahan harga SSH mengubah HSPK terkait secara otomatis | | |
| 10 | Situs Publik / API | Pencarian data harga tanpa login | | |
| 11 | Master Data | Tambah/ubah item SSH, riwayat harga, dan penonaktifan item | | |
| 12 | Import & Export Excel | Import per baris, templat, export biasa, dan export SIPD | | |
| 13 | Survei Harga | Perekaman survei, bukti digital, dan angka rekomendasi | | |
| 14 | Pemetaan Kode | Status pemetaan Kode Aset → Rekening → SIPD | | |
| 15 | Laporan & Dashboard | Rekap per jenis, laporan perubahan harga, angka ringkasan dashboard | | |
| 16 | Jejak Audit | Pencatatan pelaku, nilai sebelum & sesudah; audit log hanya dapat dibaca | | |
| 17 | Keamanan | Pembatasan percobaan login, penolakan unggahan berbahaya, header keamanan | | |

### 4.2 Modul Diuji Manual pada Sesi Ini

Butir berikut menuntut penilaian manusia sehingga tidak dapat digantikan pengujian otomatis.

| No | Modul / Fitur | Skenario Diuji | Hasil | Catatan |
|---|---|---|---|---|
| 18 | Master Data | Item nonaktif tidak lagi muncul di situs publik | | |
| 19 | Master Data | Pencarian & filter daftar master terasa benar bagi petugas | | |
| 20 | Import Excel | Unggah berkas selain Excel ditolak dengan pesan yang jelas | | |
| 21 | Export SIPD | Susunan kolom sesuai templat SIPD yang berlaku di instansi | | |
| 22 | Survei Harga | Unggah bukti langsung dari kamera ponsel | | |
| 23 | Survei Harga | Angka rekomendasi (median) dipahami dan disetujui petugas | | |
| 24 | Pemetaan Kode | Penandaan pemetaan bermasalah mudah dibaca dan ditindaklanjuti | | |
| 25 | Laporan | Susunan & tampilan cetak memadai sebagai lampiran dokumen instansi | | |
| 26 | Dashboard | Informasi yang ditampilkan sesuai kebutuhan pemantauan pimpinan | | |
| 27 | Antarmuka | Tampilan pada perangkat ponsel | | |
| 28 | Antarmuka | Istilah sesuai kebiasaan instansi | | |
| 29 | Antarmuka | Pesan galat mudah dipahami | | |

### 4.3 Rekapitulasi

| Kategori | Jumlah |
|---|---|
| Skenario diuji | .......... |
| Lulus (L) | .......... |
| Lulus dengan Catatan (LC) | .......... |
| Tidak Lulus (TL) | .......... |

## 5. Daftar Perbaikan yang Disepakati

| No | Uraian Temuan | Tingkat Kepentingan | Target Penyelesaian |
|---|---|---|---|
| 1 | .................................................. | ☐ Tinggi ☐ Sedang ☐ Rendah | .................. |
| 2 | .................................................. | ☐ Tinggi ☐ Sedang ☐ Rendah | .................. |
| 3 | .................................................. | ☐ Tinggi ☐ Sedang ☐ Rendah | .................. |

## 6. Kesimpulan

Berdasarkan hasil pengujian sebagaimana tercantum pada angka 4, para pihak menyatakan bahwa
sistem SISHD:

- ☐ **DITERIMA** — seluruh skenario pengujian dinyatakan lulus dan sistem siap dipergunakan.
- ☐ **DITERIMA DENGAN CATATAN** — sistem dapat dipergunakan, dengan perbaikan sebagaimana
  tercantum pada angka 5 diselesaikan paling lambat tanggal ..............................
- ☐ **BELUM DITERIMA** — terdapat temuan yang harus diselesaikan terlebih dahulu, dan pengujian
  ulang dijadwalkan pada tanggal ..............................

Catatan tambahan:

................................................................................................

................................................................................................

Demikian Berita Acara ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.

---

## 7. Lembar Persetujuan

| Perwakilan Pengguna / OPD | Pengembang |
|---|---|
| <br><br><br> | <br><br><br> |
| ( .......................................... ) | ( .......................................... ) |
| NIP/ID: .................................. | NIP/ID: .................................. |

<br>

**Mengetahui,**
**Pimpinan / Pejabat Berwenang**

<br><br><br>

( .......................................... )
NIP: ..........................................

---

> **Catatan pengisian**: seluruh titik-titik, kotak centang, dan kolom tanda tangan pada dokumen
> ini adalah isian kosong yang harus dilengkapi oleh instansi sesuai keadaan sebenarnya. Jangan
> menandatangani atau mengisi nama sebelum pengujian benar-benar dilaksanakan.
