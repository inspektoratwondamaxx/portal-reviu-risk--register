# Arsip & Scan Kantor (Google Apps Script + Google Sheets)

Aplikasi mobile perkantoran: foto dokumen dengan kamera HP → dibaca otomatis (OCR) → data terisi ke form → tersimpan di Google Sheets, berkas di Google Drive.

## Fitur
- Scan via kamera/galeri (maks. 5 halaman/dokumen, dikompres otomatis di HP)
- OCR bahasa Indonesia (Drive Advanced Service) + ekstraksi otomatis: jenis, nomor, tanggal, pengirim, perihal
- Verifikasi data sebelum simpan; status Baru/Diproses/Selesai
- Arsip dengan pencarian (nomor, perihal, isi OCR) dan filter
- Role Admin/Staf (daftar pengguna di sheet `Pengguna`), log aktivitas di sheet `Log`
- Dashboard ringkasan; tampilan mobile-first

## Pemasangan
1. script.google.com → **Project baru**. Salin `Code.gs`, `Index.html` (File > HTML), dan isi `appsscript.json` (Project Settings > *Show appsscript.json*).
2. Pastikan **Drive API v2** aktif (Services > Drive, v2) — sudah dideklarasikan di `appsscript.json`.
3. Jalankan fungsi **`setup()`** sekali, setujui izin. Log menampilkan URL Spreadsheet dan folder Drive.
4. **Bagikan Spreadsheet & folder "Arsip Scan Kantor" sebagai Editor** ke seluruh staf (mode deploy berjalan sebagai pengguna yang mengakses).
5. **Deploy > New deployment > Web app**: Execute as *User accessing the web app*, Access *Anyone with Google account*.
6. Buka URL di HP → menu browser → *Tambahkan ke Layar Utama*.
7. Admin menambah staf di menu **Pengguna** (email Google).

## Catatan keamanan
- Akses hanya akun terdaftar di sheet `Pengguna`; Staf hanya mengubah dokumen miliknya, hapus hanya Admin.
- Input dibersihkan dari formula injection; output di-escape (anti-XSS).
- Untuk dokumen rahasia gunakan akun Workspace instansi dan batasi folder Drive.
- Kualitas OCR bergantung foto: cahaya merata, tegak lurus, tidak blur.
