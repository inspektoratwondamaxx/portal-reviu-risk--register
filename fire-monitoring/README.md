# Pantau Titik Api & Kebakaran — Real-Time (Google Apps Script)

Aplikasi web pemantauan titik api/kebakaran berbasis peta satelit, dibangun
100% di atas Google Apps Script (tidak perlu server/hosting terpisah).

## Fitur

- **Peta satelit** resolusi tinggi (Esri World Imagery) dengan opsi peta
  satelit + label dan peta jalan (OpenStreetMap), zoom penuh hingga level 19,
  bebas digeser/di-zoom ke area manapun.
- **Titik api real-time** dari NASA FIRMS (VIIRS SNPP, VIIRS NOAA-20,
  VIIRS NOAA-21, MODIS) — *near real-time*, mengikuti siklus lintasan satelit.
- **Warna & ukuran titik api merah** yang membedakan tingkat keparahan:
  - 🔴 **Terbakar Hebat** (FRP ≥ 15 MW) — merah tua, titik besar, berkedip.
  - 🔴 **Terbakar Sedang** (FRP 5–15 MW) — merah, titik sedang.
  - 🔴 **Terbakar Kecil** (FRP < 5 MW) — merah muda, titik kecil.
- **Label area/wilayah** otomatis untuk tiap titik api (kecamatan terdekat),
  dengan filter per area.
- **Arah & kecepatan angin** real-time (Open-Meteo) — kompas + panah arah
  angin per wilayah langsung di peta.
- **Auto-refresh** (5/10/15/30 menit) + tombol muat ulang manual.
- **Akses publik** — siapa saja bisa membuka tautan tanpa login.
- Daftar/ringkasan titik api, statistik jumlah per tingkat keparahan,
  lokasi saya (geolocation), mode layar penuh.

## Sumber data

| Data | Sumber | Biaya | Update |
|---|---|---|---|
| Titik api (hotspot) | [NASA FIRMS](https://firms.modaps.eosdis.nasa.gov/) | Gratis (perlu MAP_KEY) | ~tiap 1–3 jam mengikuti lintasan satelit |
| Angin | [Open-Meteo](https://open-meteo.com/) | Gratis, tanpa API key | Tiap jam |
| Peta dasar satelit | Esri World Imagery | Gratis, tanpa API key | — |

> **Catatan jujur soal "real-time":** Data titik api satelit (FIRMS) bukan
> video langsung, melainkan deteksi *near real-time* dari satelit polar yang
> melintas beberapa kali sehari — ini adalah standar yang sama dipakai BNPB,
> KLHK/SiPongi, dan Global Forest Watch. Aplikasi ini menampilkan data
> **sesegera tersedia dari NASA**, dengan auto-refresh berkala.

## Cara Deploy

1. **Dapatkan FIRMS MAP_KEY (gratis, wajib)**
   Daftar di https://firms.modaps.eosdis.nasa.gov/api/map_key/ — key dikirim
   ke email Anda.

2. **Buat proyek Apps Script baru**
   Buka https://script.google.com → New Project. Hapus isi default, lalu
   salin isi `Code.gs`, `Index.html`, dan `appsscript.json` dari folder ini
   ke proyek Anda (gunakan nama file yang sama persis).

   Atau, jika sudah punya `clasp` terpasang:
   ```bash
   cd fire-monitoring
   clasp create --type webapp --title "Pantau Titik Api & Kebakaran"
   clasp push
   ```

3. **Atur Script Properties**
   Di editor Apps Script: ikon gerigi (Project Settings) → **Script
   Properties** → Add script property:
   - `FIRMS_MAP_KEY` = *(map key dari langkah 1)*
   - `DEFAULT_BBOX` = *(opsional)* `west,south,east,north` dalam derajat
     desimal untuk membatasi area default peta. Default saat ini mencakup
     wilayah Papua Barat / Teluk Wondama:
     `132.0,-4.5,136.5,0.0`

4. **Sesuaikan daftar wilayah (opsional)**
   Edit array `AREA_POINTS` di `Code.gs` untuk mengganti/menambah titik
   referensi kecamatan/wilayah sesuai daerah rawan kebakaran yang ingin
   dipantau (nama, latitude, longitude).

5. **Deploy sebagai Web App**
   Deploy → New deployment → pilih tipe **Web app**:
   - Execute as: **Me**
   - Who has access: **Anyone**

   Klik Deploy, salin URL yang diberikan. URL ini bisa dibagikan ke siapa
   saja tanpa perlu login Google (sesuai permintaan akses publik).

6. **Uji coba**
   Buka URL deployment. Jika muncul peringatan "FIRMS_MAP_KEY belum
   diatur", ulangi langkah 3. Jika berhasil, titik api (bila ada dalam
   rentang waktu & area terpilih) akan tampil sebagai lingkaran merah di
   atas peta satelit.

## Batasan yang perlu diketahui

- **Kuota UrlFetchApp**: akun Google gratis punya kuota panggilan URL
  eksternal harian (~20.000 untuk akun konsumen, lebih tinggi untuk
  Workspace). Caching 15 menit pada `CacheService` sudah diterapkan untuk
  meminimalkan jumlah panggilan meski banyak pengunjung mengakses
  bersamaan.
- **FIRMS MAP_KEY** punya batas transaksi harian (lihat halaman pendaftaran
  MAP_KEY untuk detail terbaru dari NASA).
- Klasifikasi "kecil/sedang/hebat" memakai ambang batas FRP (Fire Radiative
  Power) yang umum dipakai pada literatur pemantauan kebakaran berbasis
  satelit; ini adalah **estimasi intensitas**, bukan pengukuran luas area
  terbakar secara langsung.
- Label "area" ditentukan dari titik referensi kecamatan terdekat
  (jarak garis lurus), bukan dari batas administrasi presisi — cukup akurat
  untuk konteks pemantauan, namun bisa disempurnakan dengan data poligon
  batas wilayah bila diperlukan.
