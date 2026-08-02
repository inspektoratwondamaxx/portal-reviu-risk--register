/*************************************************************************
 * PORTAL REVIU RISK REGISTER — Kabupaten Teluk Wondama
 * Google Apps Script — Code.gs  [v5]
 *
 * Fitur baru v5:
 *  - Dropdown OPD di dasbor (navigasi cepat antar OPD)
 *  - Halaman login admin terpisah (?page=admin)
 *  - Auto-update dari GitHub (tanpa copy-paste ulang)
 *
 * Auto-update memerlukan:
 *  - Script Property: GITHUB_RAW_BASE = URL raw GitHub folder kode
 *    mis. https://raw.githubusercontent.com/username/repo/main
 *  - OAuth scope: https://www.googleapis.com/auth/script.projects
 *    (tambahkan di appsscript.json → oauthScopes)
 *************************************************************************/

// ====== KONFIGURASI ======
var PARENT_FOLDER_ID = '1RVCT8Hl0F4qB3z63hrDxxak623--ooWx';
var MAX_UPLOAD_MB    = 100;
var APP_VERSION      = 'v5.0';

function props_() { return PropertiesService.getScriptProperties(); }

// ====== DAFTAR OPD ======
var OPD_LIST = [
  '01 - Sekretariat Daerah',
  '02 - Sekretariat DPRD',
  '03 - Inspektorat Daerah',
  '04 - Badan Perencanaan Pembangunan Daerah (Bappeda)',
  '05 - Badan Pengelolaan Pendapatan, Keuangan dan Aset Daerah (BPPKAD)',
  '06 - Badan Kepegawaian dan Pengembangan SDM (BKPSDM)',
  '07 - Badan Kesatuan Bangsa dan Politik (Kesbangpol)',
  '08 - Badan Penanggulangan Bencana Daerah (BPBD)',
  '09 - Dinas Pendidikan, Pemuda dan Olahraga',
  '10 - Dinas Kesehatan',
  '11 - Dinas Pekerjaan Umum dan Penataan Ruang (PUPR)',
  '12 - Dinas Perumahan Rakyat dan Kawasan Permukiman',
  '13 - Dinas Sosial',
  '14 - Dinas Tenaga Kerja dan Transmigrasi',
  '15 - Dinas Pemberdayaan Perempuan, Perlindungan Anak dan KB',
  '16 - Dinas Ketahanan Pangan, Pertanian dan Perikanan',
  '17 - Dinas Lingkungan Hidup dan Kehutanan',
  '18 - Dinas Kependudukan dan Pencatatan Sipil',
  '19 - Dinas Pemberdayaan Masyarakat dan Kampung',
  '20 - Dinas Perhubungan',
  '21 - Dinas Komunikasi, Informatika, Persandian dan Statistik',
  '22 - Dinas Koperasi, UKM, Perindustrian dan Perdagangan',
  '23 - Dinas Penanaman Modal dan PTSP (DPMPTSP)',
  '24 - Dinas Kebudayaan dan Pariwisata',
  '25 - Satuan Polisi Pamong Praja dan Pemadam Kebakaran'
];

var SUBFOLDERS = [
  'A - Dokumen Risk Register',
  'B - Dokumen Perencanaan dan Kinerja',
  'C - Dokumen Pendukung Lainnya'
];

function opdMeta_() {
  return OPD_LIST.map(function(n, i) {
    return { id: 'opd-'+(i+1), name: n, tim: i<8?'Tim 1':(i<16?'Tim 2':'Tim 3') };
  });
}

// =====================================================================
//  WEB APP ROUTING  — doGet
//  ?page=admin  → langsung form login admin
//  (kosong)     → aplikasi utama
// =====================================================================
function doGet(e) {
  var page = (e && e.parameter && e.parameter.page) || '';
  var tmpl = HtmlService.createTemplateFromFile('Index');
  tmpl.adminPage = (page === 'admin');
  return tmpl.evaluate()
    .setTitle('Portal Reviu Risk Register — Teluk Wondama')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}
function include(name) { return HtmlService.createHtmlOutputFromFile(name).getContent(); }

// =====================================================================
//  AUTENTIKASI & PENGGUNA
// =====================================================================
function getUsers_()  { var s=props_().getProperty('USERS_JSON'); return s?JSON.parse(s):{};    }
function saveUsers_(u){ props_().setProperty('USERS_JSON', JSON.stringify(u));                   }

function hashPass_(pw, salt) {
  var raw = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, pw+':'+salt, Utilities.Charset.UTF_8);
  return raw.map(function(b){return ('0'+(b&0xff).toString(16)).slice(-2);}).join('');
}
function makeSalt_() { return Utilities.getUuid().replace(/-/g,'').slice(0,12); }
function makeToken_(){ return Utilities.getUuid().replace(/-/g,''); }
function randPass_() {
  var c='abcdefghjkmnpqrstuvwxyz23456789', s='';
  for(var i=0;i<8;i++) s+=c.charAt(Math.floor(Math.random()*c.length));
  return s;
}

function seedAdmin_() {
  var users = getUsers_();
  if(Object.keys(users).length>0) return null;
  var pass = props_().getProperty('ADMIN_PASSCODE')||'admin123';
  var salt = makeSalt_();
  users['admin'] = {name:'Administrator',role:'admin',opd:'',salt:salt,hash:hashPass_(pass,salt)};
  saveUsers_(users);
  return {username:'admin', password:pass};
}

function login(username, password) {
  username = String(username||'').trim().toLowerCase();
  var users = getUsers_(), u = users[username];
  if(!u || hashPass_(password,u.salt)!==u.hash) return {ok:false,error:'Nama pengguna atau kata sandi salah.'};
  var token = makeToken_();
  var sess  = {username:username, name:u.name, role:u.role, opd:u.opd};
  CacheService.getScriptCache().put('tok_'+token, JSON.stringify(sess), 21600);
  return {ok:true, token:token, user:sess};
}

// Login khusus admin (dipakai di halaman ?page=admin)
function loginAdmin(username, password) {
  var res = login(username, password);
  if(!res.ok) return res;
  if(res.user.role !== 'admin') {
    CacheService.getScriptCache().remove('tok_'+res.token);
    return {ok:false, error:'Akun ini bukan Admin.'};
  }
  return res;
}

function logout(token) {
  if(token) CacheService.getScriptCache().remove('tok_'+token);
  return {ok:true};
}
function session_(token) {
  if(!token) return null;
  var s = CacheService.getScriptCache().get('tok_'+token);
  return s ? JSON.parse(s) : null;
}
function requireRole_(token, roles) {
  var s = session_(token);
  if(!s) throw new Error('Sesi berakhir. Silakan masuk kembali.');
  if(roles && roles.indexOf(s.role)<0) throw new Error('Anda tidak memiliki akses untuk tindakan ini.');
  return s;
}

function listUsers(token) {
  requireRole_(token, ['admin']);
  var users = getUsers_();
  return Object.keys(users).map(function(k){
    return {username:k, name:users[k].name, role:users[k].role, opd:users[k].opd};
  }).sort(function(a,b){return (a.role+a.username).localeCompare(b.role+b.username);});
}
function addUser(token, data) {
  requireRole_(token, ['admin']);
  var username = String(data.username||'').trim().toLowerCase();
  if(!/^[a-z0-9_.\-]{3,}$/.test(username)) return {ok:false,error:'Nama pengguna minimal 3 karakter.'};
  var users = getUsers_();
  if(users[username]) return {ok:false,error:'Nama pengguna sudah dipakai.'};
  if(['admin','reviewer','opd'].indexOf(data.role)<0) return {ok:false,error:'Peran tidak valid.'};
  var pass = (data.password&&String(data.password).length>=4) ? String(data.password) : randPass_();
  var salt = makeSalt_();
  users[username] = {name:String(data.name||username), role:data.role,
    opd:data.role==='opd'?String(data.opd||''):'', salt:salt, hash:hashPass_(pass,salt)};
  saveUsers_(users);
  return {ok:true, username:username, password:pass};
}
function deleteUser(token, username) {
  var me = requireRole_(token, ['admin']);
  username = String(username||'').toLowerCase();
  if(username===me.username) return {ok:false,error:'Tidak dapat menghapus akun Anda sendiri.'};
  var users = getUsers_();
  if(!users[username]) return {ok:false,error:'Pengguna tidak ditemukan.'};
  delete users[username]; saveUsers_(users);
  return {ok:true};
}
function resetPassword(token, username, newPass) {
  requireRole_(token, ['admin']);
  username = String(username||'').toLowerCase();
  var users = getUsers_();
  if(!users[username]) return {ok:false,error:'Pengguna tidak ditemukan.'};
  var pass = (newPass&&String(newPass).length>=4) ? String(newPass) : randPass_();
  var salt = makeSalt_();
  users[username].salt=salt; users[username].hash=hashPass_(pass,salt); saveUsers_(users);
  return {ok:true, username:username, password:pass};
}
function changeOwnPassword(token, oldPass, newPass) {
  var me = requireRole_(token, null);
  if(!newPass||String(newPass).length<4) return {ok:false,error:'Kata sandi baru minimal 4 karakter.'};
  var users = getUsers_(), u = users[me.username];
  if(hashPass_(oldPass,u.salt)!==u.hash) return {ok:false,error:'Kata sandi lama salah.'};
  var salt=makeSalt_(); u.salt=salt; u.hash=hashPass_(newPass,salt); saveUsers_(users);
  return {ok:true};
}
function createAllOpdUsers(token, prefix) {
  requireRole_(token, ['admin']);
  prefix = String(prefix||'opd').trim().toLowerCase().replace(/[^a-z0-9]/g,'')||'opd';
  var users=getUsers_(), created=[];
  opdMeta_().forEach(function(o,i){
    var uname=prefix+(i+1);
    if(users[uname]) return;
    var pass=randPass_(), salt=makeSalt_();
    users[uname]={name:o.name,role:'opd',opd:o.name,salt:salt,hash:hashPass_(pass,salt)};
    created.push({username:uname,password:pass,opd:o.name});
  });
  saveUsers_(users);
  return {ok:true, created:created, total:created.length};
}

// =====================================================================
//  TAHUN REVIU
// =====================================================================
function getParentFolder_()       { return DriveApp.getFolderById(PARENT_FOLDER_ID); }
function findOrCreateFolder_(p,n) { var it=p.getFoldersByName(n); return it.hasNext()?it.next():p.createFolder(n); }
function isYearName_(n)           { return /^\d{4}$/.test(n); }

function listYears_() {
  var parent=getParentFolder_(), it=parent.getFolders(), years=[];
  while(it.hasNext()){var f=it.next(); if(isYearName_(f.getName())) years.push(f.getName());}
  years.sort(function(a,b){return Number(b)-Number(a);}); return years;
}
function getActiveYear_() {
  var y=props_().getProperty('ACTIVE_YEAR'); if(y) return y;
  var years=listYears_(); return years.length?years[0]:String(new Date().getFullYear());
}
function getYearFolder_(year) {
  if(!isYearName_(String(year))) year=getActiveYear_();
  return findOrCreateFolder_(getParentFolder_(), String(year));
}
function createYear(token, year) {
  requireRole_(token, ['admin']);
  year=String(year||'').trim();
  if(!isYearName_(year)) return {ok:false,error:'Tahun harus 4 digit angka.'};
  var yf=getYearFolder_(year), dibuat=0;
  OPD_LIST.forEach(function(opd){
    var before=yf.getFoldersByName(opd).hasNext();
    var of_=findOrCreateFolder_(yf,opd); if(!before) dibuat++;
    SUBFOLDERS.forEach(function(s){findOrCreateFolder_(of_,s);});
  });
  if(!props_().getProperty('ACTIVE_YEAR')) props_().setProperty('ACTIVE_YEAR',year);
  return {ok:true, year:year, dibuat:dibuat};
}
function setActiveYear(token, year) {
  requireRole_(token, ['admin']);
  year=String(year||'').trim();
  if(!isYearName_(year)) return {ok:false,error:'Tahun tidak valid.'};
  props_().setProperty('ACTIVE_YEAR',year);
  return {ok:true, year:year};
}

// =====================================================================
//  SETUP AWAL
// =====================================================================
function setupFolders() {
  var year=props_().getProperty('ACTIVE_YEAR')||String(new Date().getFullYear());
  var yf=getYearFolder_(year), dibuat=0;
  OPD_LIST.forEach(function(opd){
    var before=yf.getFoldersByName(opd).hasNext();
    var of_=findOrCreateFolder_(yf,opd); if(!before) dibuat++;
    SUBFOLDERS.forEach(function(s){findOrCreateFolder_(of_,s);});
  });
  if(!props_().getProperty('ACTIVE_YEAR')) props_().setProperty('ACTIVE_YEAR',year);
  var admin=seedAdmin_();
  var msg='Tahun aktif: '+year+'. Folder OPD dibuat: '+dibuat+' (dari '+OPD_LIST.length+').';
  if(admin) msg+='\n\n>>> AKUN ADMIN AWAL <<<\n   Pengguna : '+admin.username+'\n   Sandi    : '+admin.password+'\n   (Segera ganti sandi setelah masuk.)';
  else msg+='\nAkun admin sudah ada sebelumnya.';
  Logger.log(msg); return {ok:true, message:msg};
}

// =====================================================================
//  KONFIG PUBLIK
// =====================================================================
function getConfig() {
  var users=getUsers_();
  return {
    opdList   : opdMeta_(),
    subfolders: SUBFOLDERS,
    maxUploadMb: MAX_UPLOAD_MB,
    hasUsers  : Object.keys(users).length>0,
    years     : listYears_(),
    activeYear: getActiveYear_(),
    version   : APP_VERSION,
    githubSet : !!props_().getProperty('GITHUB_RAW_BASE')
  };
}

// =====================================================================
//  FOLDER OPD
// =====================================================================
function getOpdFolder_(year,opdName){ return findOrCreateFolder_(getYearFolder_(year),opdName); }

// =====================================================================
//  UNGGAH BERKAS (ber-chunk, year-aware)
// =====================================================================
function uploadChunk(token, payload) {
  var s=requireRole_(token, ['admin','reviewer','opd']);
  if(s.role==='opd') payload.opdName=s.opd;
  var year=payload.year||getActiveYear_();
  var cache=CacheService.getScriptCache();
  var maxBytes=MAX_UPLOAD_MB*1024*1024;
  if(payload.totalBytes&&payload.totalBytes>maxBytes) return {ok:false,error:'Ukuran berkas melebihi '+MAX_UPLOAD_MB+' MB.'};
  cache.put('up_'+payload.sessionId+'_'+payload.index, payload.dataB64, 3600);
  if(payload.index+1<payload.totalChunks) return {ok:true,partial:true,received:payload.index+1};
  var b64='';
  for(var i=0;i<payload.totalChunks;i++){
    var part=cache.get('up_'+payload.sessionId+'_'+i);
    if(part===null) return {ok:false,error:'Sebagian data hilang. Ulangi unggah.'};
    b64+=part;
  }
  var blob=Utilities.newBlob(Utilities.base64Decode(b64), payload.mimeType||'application/octet-stream', payload.fileName);
  var opdFolder=getOpdFolder_(year,payload.opdName);
  var target=payload.subfolder?findOrCreateFolder_(opdFolder,payload.subfolder):opdFolder;
  var file=target.createFile(blob);
  for(var j=0;j<payload.totalChunks;j++) cache.remove('up_'+payload.sessionId+'_'+j);
  return {ok:true,done:true,file:{id:file.getId(),name:file.getName(),size:file.getSize(),url:file.getUrl()}};
}

// =====================================================================
//  DAFTAR ISI (year-aware)
// =====================================================================
function listOpdFiles(token, year, opdName) {
  var s=requireRole_(token, ['admin','reviewer','opd']);
  if(s.role==='opd') opdName=s.opd;
  year=year||getActiveYear_();
  var opdFolder=getOpdFolder_(year,opdName), groups=[];
  SUBFOLDERS.forEach(function(sub){groups.push({subfolder:sub,files:filesIn_(findOrCreateFolder_(opdFolder,sub))});});
  groups.push({subfolder:'(Tanpa subfolder)',files:filesIn_(opdFolder)});
  return groups;
}
function filesIn_(folder) {
  var out=[],it=folder.getFiles();
  while(it.hasNext()){var f=it.next(); out.push({id:f.getId(),name:f.getName(),size:f.getSize(),mimeType:f.getMimeType(),url:f.getUrl(),updated:f.getLastUpdated().toISOString()});}
  return out;
}
function dashboardSummary(token, year) {
  requireRole_(token, ['admin','reviewer']);
  year=year||getActiveYear_();
  var yf=getYearFolder_(year);
  return opdMeta_().map(function(o){
    var it=yf.getFoldersByName(o.name);
    var count=it.hasNext()?countFilesRecursive_(it.next()):0;
    return {id:o.id,name:o.name,tim:o.tim,fileCount:count};
  });
}
function countFilesRecursive_(folder) {
  var n=0,fi=folder.getFiles();
  while(fi.hasNext()){fi.next();n++;}
  var sf=folder.getFolders();
  while(sf.hasNext()) n+=countFilesRecursive_(sf.next());
  return n;
}

// =====================================================================
//  AUTO-UPDATE DARI GITHUB
//
//  Script Property yang dibutuhkan:
//    GITHUB_RAW_BASE  = https://raw.githubusercontent.com/USERNAME/REPO/BRANCH
//
//  Di GitHub, simpan dua file:
//    /Code.gs     (isi Code.gs)
//    /Index.html  (isi Index.html)
//    /version.json  {"version":"v5.1","notes":"Deskripsi pembaruan"}
//
//  Cara kerja:
//  1. checkUpdate() → ambil version.json → bandingkan dengan APP_VERSION
//  2. applyUpdate() → ambil Code.gs + Index.html dari GitHub →
//     tulis langsung ke file proyek via Apps Script API → deploy versi baru
// =====================================================================

/** Cek ketersediaan pembaruan. Dipanggil klien (admin). */
function checkUpdate(token) {
  requireRole_(token, ['admin']);
  var base=props_().getProperty('GITHUB_RAW_BASE');
  if(!base) return {ok:false, error:'GITHUB_RAW_BASE belum diatur di Script Properties.'};
  base=base.replace(/\/$/,'');
  var resp;
  try {
    resp=UrlFetchApp.fetch(base+'/version.json', {muteHttpExceptions:true});
  } catch(e) { return {ok:false,error:'Gagal menghubungi GitHub: '+e.message}; }
  if(resp.getResponseCode()!==200) return {ok:false,error:'File version.json tidak ditemukan di GitHub (HTTP '+resp.getResponseCode()+').'};
  var info;
  try { info=JSON.parse(resp.getContentText()); } catch(e) { return {ok:false,error:'Format version.json tidak valid.'}; }
  return {
    ok:true,
    currentVersion: APP_VERSION,
    latestVersion : info.version||'?',
    notes         : info.notes||'',
    hasUpdate     : (info.version||'')!==APP_VERSION
  };
}

/**
 * Terapkan pembaruan: ambil Code.gs + Index.html dari GitHub,
 * tulis langsung ke proyek via Apps Script API (scope: script.projects).
 * Setelah selesai, Admin cukup klik Deploy → Manage → New version
 * (satu klik, URL tidak berubah).
 */
function applyUpdate(token) {
  requireRole_(token, ['admin']);
  var base = props_().getProperty('GITHUB_RAW_BASE');
  if (!base) return {ok:false, error:'GITHUB_RAW_BASE belum diatur di Script Properties.'};
  base = base.replace(/\/$/, '');

  // 1. Unduh kode terbaru dari GitHub
  var codeResp, htmlResp;
  try {
    codeResp = UrlFetchApp.fetch(base + '/Code.gs',    {muteHttpExceptions:true});
    htmlResp = UrlFetchApp.fetch(base + '/Index.html', {muteHttpExceptions:true});
  } catch(e) { return {ok:false, error:'Gagal mengunduh dari GitHub: ' + e.message}; }
  if (codeResp.getResponseCode() !== 200) return {ok:false, error:'Code.gs tidak ditemukan di GitHub (HTTP ' + codeResp.getResponseCode() + ').'};
  if (htmlResp.getResponseCode() !== 200) return {ok:false, error:'Index.html tidak ditemukan di GitHub (HTTP ' + htmlResp.getResponseCode() + ').'};

  var newCode = codeResp.getContentText();
  var newHtml = htmlResp.getContentText();

  // 2. Ambil isi proyek via Apps Script API
  var scriptId = ScriptApp.getScriptId();
  var oauthTok = ScriptApp.getOAuthToken();

  var getResp = UrlFetchApp.fetch(
    'https://script.googleapis.com/v1/projects/' + scriptId + '/content',
    {headers: {'Authorization': 'Bearer ' + oauthTok}, muteHttpExceptions: true}
  );
  if (getResp.getResponseCode() !== 200) {
    return {ok:false, error:'Gagal membaca proyek via API (' + getResp.getResponseCode() + '). Pastikan scope script.projects sudah ada di appsscript.json.'};
  }

  // 3. Ganti source Code.gs dan Index.html
  var proj  = JSON.parse(getResp.getContentText());
  var files = (proj.files || []).map(function(f) {
    if (f.name === 'Code'  && f.type === 'SERVER_JS') return Object.assign({}, f, {source: newCode});
    if (f.name === 'Index' && f.type === 'HTML')      return Object.assign({}, f, {source: newHtml});
    return f;
  });

  // 4. Tulis kembali ke proyek
  var putResp = UrlFetchApp.fetch(
    'https://script.googleapis.com/v1/projects/' + scriptId + '/content',
    {
      method: 'put', muteHttpExceptions: true,
      headers: {'Authorization': 'Bearer ' + oauthTok, 'Content-Type': 'application/json'},
      payload: JSON.stringify({files: files})
    }
  );
  if (putResp.getResponseCode() !== 200) {
    return {ok:false, error:'Gagal menyimpan kode ke proyek (' + putResp.getResponseCode() + '): ' + putResp.getContentText().slice(0, 200)};
  }

  // 5. Buat versi baru (tanpa memperbarui deployment — tidak butuh scope deployments)
  var verResp = UrlFetchApp.fetch(
    'https://script.googleapis.com/v1/projects/' + scriptId + '/versions',
    {
      method: 'post', muteHttpExceptions: true,
      headers: {'Authorization': 'Bearer ' + oauthTok, 'Content-Type': 'application/json'},
      payload: JSON.stringify({description: 'Auto-update dari GitHub'})
    }
  );
  var verNum = verResp.getResponseCode() === 200
    ? JSON.parse(verResp.getContentText()).versionNumber
    : '?';

  return {
    ok: true,
    message: '✔ Kode berhasil diperbarui dari GitHub. Versi baru #' + verNum + ' telah dibuat. '
           + 'Untuk mengaktifkan: buka editor Apps Script → Deploy → Manage deployments → '
           + 'klik Edit (pensil) → Version: pilih versi terbaru → Update. URL tidak akan berubah.'
  };
}

/** Catat versi terbaru ke Script Properties (dipanggil dari versi baru setelah update). */
function getAppVersion() { return {version:APP_VERSION}; }
