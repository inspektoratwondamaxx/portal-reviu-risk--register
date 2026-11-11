/**
 * ARSIP & SCAN KANTOR — Backend Google Apps Script
 * Penyimpanan data : Google Sheets (dibuat otomatis oleh setup())
 * Penyimpanan file : Google Drive (folder dibuat otomatis oleh setup())
 * OCR              : Drive Advanced Service v2 (ocr:true, bahasa Indonesia)
 */

var SHEET_DOK = 'Dokumen';
var SHEET_USER = 'Pengguna';
var SHEET_LOG = 'Log';
var HEAD_DOK = ['ID', 'Tgl Input', 'Jenis', 'Nomor', 'Tgl Dokumen', 'Pengirim/Tujuan',
                'Perihal', 'Isi (OCR)', 'Berkas', 'Status', 'Petugas', 'Diubah'];
var JENIS = ['Surat Masuk', 'Surat Keluar', 'Nota Dinas', 'SK/Peraturan', 'Kwitansi/Bukti', 'Lainnya'];
var STATUS = ['Baru', 'Diproses', 'Selesai'];
var ROLES = ['Admin', 'Staf'];
var MAX_PAGES = 5;

/* ========== ENTRY POINT ========== */
function doGet() {
  return HtmlService.createTemplateFromFile('Index').evaluate()
    .setTitle('Arsip & Scan Kantor')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1, viewport-fit=cover')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

/* ========== SETUP (jalankan SEKALI oleh pemilik dari editor) ========== */
function setup() {
  var props = PropertiesService.getScriptProperties();
  var ss;
  if (props.getProperty('SS_ID')) {
    ss = SpreadsheetApp.openById(props.getProperty('SS_ID'));
  } else {
    ss = SpreadsheetApp.create('Database Arsip & Scan Kantor');
    props.setProperty('SS_ID', ss.getId());
  }
  ensureSheet_(ss, SHEET_DOK, HEAD_DOK);
  ensureSheet_(ss, SHEET_USER, ['Email', 'Nama', 'Role', 'Aktif']);
  ensureSheet_(ss, SHEET_LOG, ['Waktu', 'Email', 'Aksi', 'Keterangan']);
  var def = ss.getSheetByName('Sheet1');
  if (def && ss.getSheets().length > 1) ss.deleteSheet(def);

  if (!props.getProperty('FOLDER_ID')) {
    var f = DriveApp.createFolder('Arsip Scan Kantor');
    props.setProperty('FOLDER_ID', f.getId());
  }
  // Pemilik otomatis menjadi Admin
  var me = Session.getEffectiveUser().getEmail();
  var us = ss.getSheetByName(SHEET_USER);
  if (me && !findUserRow_(us, me)) us.appendRow([me, 'Administrator', 'Admin', true]);

  Logger.log('Spreadsheet : ' + ss.getUrl());
  Logger.log('Folder Drive: https://drive.google.com/drive/folders/' + props.getProperty('FOLDER_ID'));
  Logger.log('PENTING: bagikan Spreadsheet & Folder (Editor) kepada seluruh staf.');
}

function ensureSheet_(ss, name, head) {
  var sh = ss.getSheetByName(name) || ss.insertSheet(name);
  if (sh.getLastRow() === 0) {
    sh.getRange(1, 1, 1, head.length).setValues([head])
      .setFontWeight('bold').setBackground('#0b5394').setFontColor('#ffffff');
    sh.setFrozenRows(1);
  }
  return sh;
}

/* ========== AKSES & PENGGUNA ========== */
function ss_() {
  var id = PropertiesService.getScriptProperties().getProperty('SS_ID');
  if (!id) throw new Error('Aplikasi belum di-setup. Jalankan fungsi setup() di editor Apps Script.');
  return SpreadsheetApp.openById(id);
}

function findUserRow_(sh, email) {
  var d = sh.getDataRange().getValues();
  for (var i = 1; i < d.length; i++) {
    if (String(d[i][0]).toLowerCase() === String(email).toLowerCase()) return i + 1;
  }
  return 0;
}

function currentUser_() {
  var email = Session.getActiveUser().getEmail();
  if (!email) throw new Error('Akun Google tidak terdeteksi. Login dengan akun Google Anda.');
  var sh = ss_().getSheetByName(SHEET_USER);
  var row = findUserRow_(sh, email);
  if (!row) throw new Error('Akses ditolak. Akun ' + email + ' belum terdaftar. Hubungi Admin.');
  var r = sh.getRange(row, 1, 1, 4).getValues()[0];
  if (r[3] === false || String(r[3]).toUpperCase() === 'FALSE') throw new Error('Akun dinonaktifkan.');
  return { email: email, nama: r[1] || email, role: r[2] || 'Staf' };
}

function requireAdmin_(u) {
  if (u.role !== 'Admin') throw new Error('Hanya Admin yang dapat melakukan aksi ini.');
}

function log_(email, aksi, ket) {
  ss_().getSheetByName(SHEET_LOG).appendRow([new Date(), email, aksi, ket || '']);
}

/* ========== API UNTUK FRONTEND ========== */
function api_bootstrap() {
  var u = currentUser_();
  return { user: u, jenis: JENIS, status: STATUS, stats: stats_() };
}

function stats_() {
  var d = ss_().getSheetByName(SHEET_DOK).getDataRange().getValues();
  var s = { total: 0, bulanIni: 0, perJenis: {}, perStatus: {} };
  var now = new Date();
  for (var i = 1; i < d.length; i++) {
    if (!d[i][0]) continue;
    s.total++;
    var t = new Date(d[i][1]);
    if (t.getMonth() === now.getMonth() && t.getFullYear() === now.getFullYear()) s.bulanIni++;
    s.perJenis[d[i][2]] = (s.perJenis[d[i][2]] || 0) + 1;
    s.perStatus[d[i][9]] = (s.perStatus[d[i][9]] || 0) + 1;
  }
  return s;
}

/**
 * Terima foto (base64 JPEG) hasil kamera HP -> simpan ke Drive -> OCR -> ekstraksi field.
 * pages: [{data:'base64...', name:'..'}]
 */
function api_scan(pages) {
  var u = currentUser_();
  if (!pages || !pages.length) throw new Error('Tidak ada gambar.');
  if (pages.length > MAX_PAGES) throw new Error('Maksimal ' + MAX_PAGES + ' halaman per dokumen.');
  var folder = DriveApp.getFolderById(PropertiesService.getScriptProperties().getProperty('FOLDER_ID'));
  var text = [], urls = [];
  var stamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyyMMdd-HHmmss');

  pages.forEach(function (p, i) {
    var bytes = Utilities.base64Decode(String(p.data).replace(/^data:image\/\w+;base64,/, ''));
    if (bytes.length > 8 * 1024 * 1024) throw new Error('Ukuran gambar terlalu besar.');
    var blob = Utilities.newBlob(bytes, 'image/jpeg', 'scan-' + stamp + '-h' + (i + 1) + '.jpg');
    var file = folder.createFile(blob);
    urls.push(file.getUrl());
    text.push(ocr_(blob));
  });

  var full = text.join('\n\n').trim();
  log_(u.email, 'SCAN', pages.length + ' halaman');
  return { text: full, urls: urls, fields: extractFields_(full) };
}

function ocr_(blob) {
  var tmp = null;
  try {
    tmp = Drive.Files.insert(
      { title: 'tmp-ocr-' + Date.now(), mimeType: blob.getContentType() },
      blob, { ocr: true, ocrLanguage: 'id', convert: true });
    return DocumentApp.openById(tmp.id).getBody().getText();
  } finally {
    if (tmp && tmp.id) { try { DriveApp.getFileById(tmp.id).setTrashed(true); } catch (e) {} }
  }
}

/* ========== EKSTRAKSI OTOMATIS FIELD DOKUMEN ========== */
var BULAN = { januari: 0, februari: 1, maret: 2, april: 3, mei: 4, juni: 5, juli: 6, agustus: 7,
              september: 8, oktober: 9, november: 10, desember: 11 };

function extractFields_(t) {
  var f = { jenis: 'Lainnya', nomor: '', tglDokumen: '', pengirim: '', perihal: '' };
  var low = t.toLowerCase();

  // Jenis
  if (/nota\s+dinas/.test(low)) f.jenis = 'Nota Dinas';
  else if (/keputusan|peraturan|surat\s+edaran/.test(low)) f.jenis = 'SK/Peraturan';
  else if (/kwitansi|bukti\s+pembayaran|telah\s+terima\s+dari/.test(low)) f.jenis = 'Kwitansi/Bukti';
  else if (/(^|\n)\s*(nomor|no\.?)\s*[:.]/i.test(t) && /(hal|perihal)\s*[:.]/i.test(t)) f.jenis = 'Surat Masuk';

  // Nomor
  var m = t.match(/(?:nomor|no\.?)\s*[:.]?\s*([A-Za-z0-9][A-Za-z0-9\/.\-_ ]{3,45}\/[A-Za-z0-9\/.\-_ ]{1,30})/i);
  if (m) f.nomor = m[1].trim().replace(/\s{2,}.*/, '');

  // Tanggal (mis. "Wasior, 12 Maret 2026" / "12-03-2026")
  var d = t.match(/(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})/i);
  if (d) {
    f.tglDokumen = d[3] + '-' + pad_(BULAN[d[2].toLowerCase()] + 1) + '-' + pad_(d[1]);
  } else {
    d = t.match(/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/);
    if (d) f.tglDokumen = d[3] + '-' + pad_(d[2]) + '-' + pad_(d[1]);
  }

  // Perihal
  m = t.match(/(?:perihal|hal)\s*[:.]\s*(.+)/i);
  if (m) f.perihal = m[1].trim().slice(0, 200);

  // Pengirim (baris kop surat)
  var lines = t.split(/\n/).map(function (s) { return s.trim(); }).filter(String);
  for (var i = 0; i < Math.min(lines.length, 8); i++) {
    if (/pemerintah|dinas|badan|inspektorat|sekretariat|kementerian|kantor|kecamatan|kelurahan|kampung|desa|cv\.|pt\./i.test(lines[i])) {
      f.pengirim = lines[i].slice(0, 120); break;
    }
  }
  return f;
}

function pad_(n) { n = String(n); return n.length < 2 ? '0' + n : n; }

/* ========== CRUD DOKUMEN ========== */
function api_save(rec) {
  var u = currentUser_();
  if (JENIS.indexOf(rec.jenis) < 0) throw new Error('Jenis dokumen tidak valid.');
  var status = STATUS.indexOf(rec.status) >= 0 ? rec.status : 'Baru';
  var urls = (rec.urls || []).filter(function (x) { return /^https:\/\/drive\.google\.com\//.test(x); });
  var lock = LockService.getScriptLock();
  lock.waitLock(20000);
  try {
    var sh = ss_().getSheetByName(SHEET_DOK);
    var now = new Date();
    if (rec.id) { // ubah
      var row = findDokRow_(sh, rec.id);
      if (!row) throw new Error('Dokumen tidak ditemukan.');
      var old = sh.getRange(row, 1, 1, 12).getValues()[0];
      if (u.role !== 'Admin' && old[10] !== u.email) throw new Error('Anda hanya dapat mengubah dokumen milik sendiri.');
      sh.getRange(row, 3, 1, 7).setValues([[rec.jenis, clean_(rec.nomor), clean_(rec.tglDokumen),
        clean_(rec.pengirim), clean_(rec.perihal), old[7], old[8]]]);
      sh.getRange(row, 10).setValue(status);
      sh.getRange(row, 12).setValue(now);
      log_(u.email, 'UBAH', rec.id);
      return { id: rec.id };
    }
    var id = 'DOK-' + Utilities.formatDate(now, Session.getScriptTimeZone(), 'yyyyMM') + '-' +
             pad4_(Math.max(0, sh.getLastRow() - 1) + 1);
    sh.appendRow([id, now, rec.jenis, clean_(rec.nomor), clean_(rec.tglDokumen), clean_(rec.pengirim),
      clean_(rec.perihal), clean_(rec.isi).slice(0, 45000), urls.join('\n'), status, u.email, now]);
    log_(u.email, 'TAMBAH', id);
    return { id: id };
  } finally { lock.releaseLock(); }
}

// Cegah formula injection di Sheets
function clean_(v) {
  v = v == null ? '' : String(v);
  return /^[=+\-@]/.test(v) ? "'" + v : v;
}
function pad4_(n) { n = String(n); while (n.length < 4) n = '0' + n; return n; }

function findDokRow_(sh, id) {
  var ids = sh.getRange(1, 1, sh.getLastRow(), 1).getValues();
  for (var i = 1; i < ids.length; i++) if (ids[i][0] === id) return i + 1;
  return 0;
}

function api_list(q) {
  var u = currentUser_();
  q = q || {};
  var d = ss_().getSheetByName(SHEET_DOK).getDataRange().getValues();
  var kw = String(q.kata || '').toLowerCase();
  var out = [];
  for (var i = d.length - 1; i >= 1; i--) {
    var r = d[i];
    if (!r[0]) continue;
    if (q.jenis && r[2] !== q.jenis) continue;
    if (q.status && r[9] !== q.status) continue;
    if (kw && (r[3] + ' ' + r[5] + ' ' + r[6] + ' ' + r[7]).toLowerCase().indexOf(kw) < 0) continue;
    out.push(rowObj_(r, false));
    if (out.length >= 200) break;
  }
  return out;
}

function api_get(id) {
  currentUser_();
  var sh = ss_().getSheetByName(SHEET_DOK);
  var row = findDokRow_(sh, id);
  if (!row) throw new Error('Dokumen tidak ditemukan.');
  return rowObj_(sh.getRange(row, 1, 1, 12).getValues()[0], true);
}

function api_delete(id) {
  var u = currentUser_(); requireAdmin_(u);
  var sh = ss_().getSheetByName(SHEET_DOK);
  var row = findDokRow_(sh, id);
  if (!row) throw new Error('Dokumen tidak ditemukan.');
  sh.deleteRow(row);
  log_(u.email, 'HAPUS', id);
  return true;
}

function rowObj_(r, full) {
  var tz = Session.getScriptTimeZone();
  var o = {
    id: r[0], tglInput: Utilities.formatDate(new Date(r[1]), tz, 'dd/MM/yyyy HH:mm'),
    jenis: r[2], nomor: r[3], tglDokumen: fmtDate_(r[4]), pengirim: r[5], perihal: r[6],
    status: r[9], petugas: r[10]
  };
  if (full) { o.isi = r[7]; o.urls = r[8] ? String(r[8]).split('\n') : []; }
  return o;
}
function fmtDate_(v) {
  if (!v) return '';
  if (Object.prototype.toString.call(v) === '[object Date]')
    return Utilities.formatDate(v, Session.getScriptTimeZone(), 'yyyy-MM-dd');
  return String(v);
}

/* ========== MANAJEMEN PENGGUNA (Admin) ========== */
function api_users() {
  requireAdmin_(currentUser_());
  var d = ss_().getSheetByName(SHEET_USER).getDataRange().getValues();
  return d.slice(1).map(function (r) { return { email: r[0], nama: r[1], role: r[2], aktif: r[3] !== false }; });
}
function api_addUser(email, nama, role) {
  var u = currentUser_(); requireAdmin_(u);
  email = String(email || '').trim().toLowerCase();
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) throw new Error('Email tidak valid.');
  if (ROLES.indexOf(role) < 0) role = 'Staf';
  var sh = ss_().getSheetByName(SHEET_USER);
  var row = findUserRow_(sh, email);
  if (row) sh.getRange(row, 1, 1, 4).setValues([[email, clean_(nama), role, true]]);
  else sh.appendRow([email, clean_(nama), role, true]);
  log_(u.email, 'USER', email + ' / ' + role);
  return true;
}
