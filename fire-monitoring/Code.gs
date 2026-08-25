/*************************************************************************
 * PANTAU TITIK API & KEBAKARAN — REAL-TIME (Satellite Map)
 * Google Apps Script — Code.gs
 *
 * Sumber data:
 *  - Titik panas / hotspot kebakaran : NASA FIRMS (VIIRS SNPP, VIIRS NOAA-20,
 *    VIIRS NOAA-21, MODIS) — near real-time, update tiap ~3 jam mengikuti
 *    siklus lintasan satelit.
 *  - Arah & kecepatan angin          : Open-Meteo Forecast API (gratis, tanpa API key)
 *  - Peta dasar satelit              : Esri World Imagery (gratis, tanpa API key)
 *
 * SETUP WAJIB SEBELUM DEPLOY:
 *  1. Daftar FIRMS MAP_KEY gratis di:
 *     https://firms.modaps.eosdis.nasa.gov/api/map_key/
 *  2. Buka Project Settings (ikon gerigi) di Apps Script Editor →
 *     Script Properties → tambahkan:
 *       FIRMS_MAP_KEY = <map key anda>
 *  3. (Opsional) atur DEFAULT_BBOX untuk membatasi area awal peta,
 *     format: "west,south,east,north" (derajat desimal).
 *     Default: wilayah Papua Barat / Teluk Wondama.
 *  4. Deploy → New deployment → Web app
 *       - Execute as     : Me
 *       - Who has access : Anyone
 *     (supaya bisa diakses siapa saja tanpa login, sesuai permintaan)
 *
 * CATATAN AKURASI:
 *  Data FIRMS adalah data *near real-time* (bukan live per detik) karena
 *  bergantung pada waktu lintas satelit polar (VIIRS/MODIS), umumnya
 *  tersedia 1-3 jam setelah deteksi. ini adalah standar industri untuk
 *  pemantauan titik api berbasis satelit di seluruh dunia (dipakai BNPB,
 *  KLHK/SiPongi, Global Forest Watch, dll).
 *************************************************************************/

// ====== KONFIGURASI ======
var FIRMS_MAP_KEY_PROP = 'FIRMS_MAP_KEY';
var DEFAULT_BBOX_PROP   = 'DEFAULT_BBOX';
var DEFAULT_BBOX        = '132.0,-4.5,136.5,0.0'; // Papua Barat & sekitarnya
var CACHE_TTL_HOTSPOTS  = 900;  // 15 menit
var CACHE_TTL_WIND      = 600;  // 10 menit
var FIRMS_SOURCES       = ['VIIRS_SNPP_NRT', 'VIIRS_NOAA20_NRT', 'VIIRS_NOAA21_NRT', 'MODIS_NRT'];
var APP_VERSION         = 'v1.0';

function props_() { return PropertiesService.getScriptProperties(); }

// Titik referensi kecamatan/wilayah untuk memberi label "area" pada tiap titik api.
// Silakan tambah/ubah sesuai wilayah kerja anda.
var AREA_POINTS = [
  { name: 'Wasior',                 lat: -2.7167, lon: 134.5000 },
  { name: 'Wasior Utara',           lat: -2.5833, lon: 134.4667 },
  { name: 'Wasior Barat',           lat: -2.7333, lon: 134.4000 },
  { name: 'Wasior Selatan',         lat: -2.8500, lon: 134.5500 },
  { name: 'Rasiei (Rumberpon)',     lat: -2.1000, lon: 134.1167 },
  { name: 'Naikere',                lat: -2.7833, lon: 134.6667 },
  { name: 'Windesi',                lat: -2.6333, lon: 134.7500 },
  { name: 'Wamesa (Duplex)',        lat: -2.4667, lon: 134.2333 },
  { name: 'Kuri Wamesa',            lat: -2.3833, lon: 134.2833 },
  { name: 'Teluk Duairi',           lat: -2.6000, lon: 134.6000 },
  { name: 'Roon',                   lat: -1.9833, lon: 134.1833 },
  { name: 'Roswar',                 lat: -2.0833, lon: 134.0500 },
  { name: 'Nikiwar',                lat: -2.2000, lon: 134.0000 },
  { name: 'Batanta / Sekitar',      lat: -2.7500, lon: 134.7500 }
];

// =====================================================================
//  WEB APP ROUTING
// =====================================================================
function doGet(e) {
  var tmpl = HtmlService.createTemplateFromFile('Index');
  return tmpl.evaluate()
    .setTitle('Pantau Titik Api & Kebakaran — Real-Time')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

function include(name) { return HtmlService.createHtmlOutputFromFile(name).getContent(); }

// =====================================================================
//  KONFIGURASI UNTUK CLIENT
// =====================================================================
function getConfig() {
  return {
    version: APP_VERSION,
    defaultBbox: props_().getProperty(DEFAULT_BBOX_PROP) || DEFAULT_BBOX,
    hasMapKey: !!props_().getProperty(FIRMS_MAP_KEY_PROP),
    areaPoints: AREA_POINTS
  };
}

// =====================================================================
//  HOTSPOT (TITIK API) — NASA FIRMS
// =====================================================================
/**
 * params: { bbox: "west,south,east,north", dayRange: 1..10 }
 */
function getHotspots(params) {
  var key = props_().getProperty(FIRMS_MAP_KEY_PROP);
  if (!key) {
    return { error: 'FIRMS_MAP_KEY belum diatur. Buka Project Settings > Script Properties dan tambahkan FIRMS_MAP_KEY (dapatkan gratis di firms.modaps.eosdis.nasa.gov/api/map_key).' };
  }
  var bbox = (params && params.bbox) || props_().getProperty(DEFAULT_BBOX_PROP) || DEFAULT_BBOX;
  var dayRange = Math.min(Math.max(parseInt((params && params.dayRange) || 1, 10), 1), 10);

  var cache = CacheService.getScriptCache();
  var cacheKey = 'fh_' + bbox + '_' + dayRange;
  var cached = cache.get(cacheKey);
  if (cached) return JSON.parse(cached);

  var allRows = [];
  var errors = [];
  FIRMS_SOURCES.forEach(function (src) {
    try {
      var url = 'https://firms.modaps.eosdis.nasa.gov/api/area/csv/' + key + '/' + src + '/' + bbox + '/' + dayRange;
      var resp = UrlFetchApp.fetch(url, { muteHttpExceptions: true });
      if (resp.getResponseCode() === 200) {
        var txt = resp.getContentText();
        if (txt.indexOf('Invalid MAP_KEY') !== -1 || txt.indexOf('<html') !== -1) {
          errors.push(src + ': MAP_KEY tidak valid');
          return;
        }
        var rows = parseCsv_(txt);
        rows.forEach(function (r) { r._source = src; allRows.push(r); });
      } else {
        errors.push(src + ': HTTP ' + resp.getResponseCode());
      }
    } catch (err) {
      errors.push(src + ': ' + err.message);
    }
  });

  var points = allRows.map(function (r) {
    var lat = parseFloat(r.latitude);
    var lon = parseFloat(r.longitude);
    var frp = parseFloat(r.frp) || 0;
    var conf = normalizeConfidence_(r.confidence, r._source);
    var sev = classifySeverity_(frp, conf);
    var area = nearestArea_(lat, lon);
    return {
      lat: lat,
      lon: lon,
      frp: frp,
      confidencePct: conf,
      acqDate: r.acq_date || '',
      acqTime: formatTime_(r.acq_time),
      satellite: r.satellite || r._source,
      instrument: r.instrument || (r._source.indexOf('MODIS') !== -1 ? 'MODIS' : 'VIIRS'),
      dayNight: r.daynight === 'D' ? 'Siang' : (r.daynight === 'N' ? 'Malam' : '-'),
      severity: sev.level,
      severityLabel: sev.label,
      area: area.name,
      areaDistanceKm: area.distanceKm
    };
  });

  // Buang duplikat deteksi yang sangat berdekatan (radius sensor MODIS lebih besar dari VIIRS)
  points = dedupePoints_(points);

  var result = {
    generatedAt: new Date().toISOString(),
    bbox: bbox,
    dayRange: dayRange,
    count: points.length,
    points: points,
    warnings: errors
  };
  cache.put(cacheKey, JSON.stringify(result), CACHE_TTL_HOTSPOTS);
  return result;
}

function dedupePoints_(points) {
  // Grid sederhana ~375m untuk menghindari titik VIIRS & MODIS yang sama dihitung dobel
  var seen = {};
  var out = [];
  points.sort(function (a, b) { return b.frp - a.frp; });
  points.forEach(function (p) {
    var gx = Math.round(p.lat * 300);
    var gy = Math.round(p.lon * 300);
    var k = gx + '_' + gy;
    if (!seen[k]) {
      seen[k] = true;
      out.push(p);
    }
  });
  return out;
}

function normalizeConfidence_(raw, source) {
  if (raw === undefined || raw === null || raw === '') return null;
  if (source.indexOf('MODIS') !== -1) {
    var n = parseFloat(raw);
    return isNaN(n) ? null : Math.round(n);
  }
  // VIIRS: low/nominal/high -> perkiraan persentase
  var map = { l: 25, n: 65, h: 90 };
  var c = String(raw).trim().toLowerCase().charAt(0);
  return map[c] !== undefined ? map[c] : null;
}

function formatTime_(t) {
  if (!t) return '';
  var s = ('0000' + t).slice(-4);
  return s.substring(0, 2) + ':' + s.substring(2, 4) + ' UTC';
}

/**
 * Klasifikasi tingkat keparahan berdasarkan FRP (Fire Radiative Power, MW).
 * Ambang batas mengacu pada literatur umum FIRMS/GFW untuk intensitas kebakaran:
 *   < 5 MW    : kecil
 *   5-15 MW   : sedang
 *   >= 15 MW  : besar / hebat
 */
function classifySeverity_(frp, confidencePct) {
  var level, label;
  if (frp >= 15) { level = 'besar'; label = 'Terbakar Hebat'; }
  else if (frp >= 5) { level = 'sedang'; label = 'Terbakar Sedang'; }
  else { level = 'kecil'; label = 'Terbakar Kecil'; }
  return { level: level, label: label };
}

function nearestArea_(lat, lon) {
  var best = null, bestDist = Infinity;
  AREA_POINTS.forEach(function (p) {
    var d = haversineKm_(lat, lon, p.lat, p.lon);
    if (d < bestDist) { bestDist = d; best = p; }
  });
  return best
    ? { name: best.name, distanceKm: Math.round(bestDist * 10) / 10 }
    : { name: 'Belum diketahui', distanceKm: null };
}

function haversineKm_(lat1, lon1, lat2, lon2) {
  var R = 6371;
  var dLat = (lat2 - lat1) * Math.PI / 180;
  var dLon = (lon2 - lon1) * Math.PI / 180;
  var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function parseCsv_(text) {
  var lines = text.split(/\r?\n/).filter(function (l) { return l.trim().length > 0; });
  if (lines.length < 2) return [];
  var headers = lines[0].split(',').map(function (h) { return h.trim(); });
  var out = [];
  for (var i = 1; i < lines.length; i++) {
    var cols = lines[i].split(',');
    if (cols.length < headers.length) continue;
    var obj = {};
    headers.forEach(function (h, idx) { obj[h] = cols[idx]; });
    out.push(obj);
  }
  return out;
}

// =====================================================================
//  ANGIN — Open-Meteo (gratis, tanpa API key)
// =====================================================================
/**
 * params: { lat, lon }
 */
function getWind(params) {
  var lat = (params && params.lat) || AREA_POINTS[0].lat;
  var lon = (params && params.lon) || AREA_POINTS[0].lon;

  var cache = CacheService.getScriptCache();
  var cacheKey = 'wind_' + lat.toFixed(2) + '_' + lon.toFixed(2);
  var cached = cache.get(cacheKey);
  if (cached) return JSON.parse(cached);

  var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon +
    '&current=wind_speed_10m,wind_direction_10m,wind_gusts_10m,temperature_2m,relative_humidity_2m' +
    '&wind_speed_unit=kmh&timezone=auto';
  try {
    var resp = UrlFetchApp.fetch(url, { muteHttpExceptions: true });
    if (resp.getResponseCode() === 200) {
      var data = JSON.parse(resp.getContentText());
      var cur = data.current || {};
      var result = {
        lat: lat,
        lon: lon,
        speedKmh: cur.wind_speed_10m,
        gustKmh: cur.wind_gusts_10m,
        directionDeg: cur.wind_direction_10m,
        directionLabel: degToCompass_(cur.wind_direction_10m),
        temperatureC: cur.temperature_2m,
        humidityPct: cur.relative_humidity_2m,
        time: cur.time
      };
      cache.put(cacheKey, JSON.stringify(result), CACHE_TTL_WIND);
      return result;
    }
    return { error: 'Gagal mengambil data angin (HTTP ' + resp.getResponseCode() + ')' };
  } catch (err) {
    return { error: 'Gagal mengambil data angin: ' + err.message };
  }
}

/**
 * Ambil data angin untuk beberapa titik area sekaligus (dipakai untuk
 * menampilkan panah angin per wilayah di peta).
 * points: [{name, lat, lon}, ...]
 */
function getWindForAreas(points) {
  var list = points && points.length ? points : AREA_POINTS;
  return list.map(function (p) {
    var w = getWind({ lat: p.lat, lon: p.lon });
    w.name = p.name;
    return w;
  });
}

function degToCompass_(deg) {
  if (deg === undefined || deg === null) return '-';
  var dirs = ['Utara', 'Timur Laut', 'Timur', 'Tenggara', 'Selatan', 'Barat Daya', 'Barat', 'Barat Laut'];
  return dirs[Math.round(deg / 45) % 8];
}
