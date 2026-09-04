<?php

use App\Http\Controllers\Admin\AccountCodeController;
use App\Http\Controllers\Admin\AsbController;
use App\Http\Controllers\Admin\AssetCodeController;
use App\Http\Controllers\Admin\AssetGroupController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\HspkController;
use App\Http\Controllers\Admin\MappingController;
use App\Http\Controllers\Admin\SbuItemController;
use App\Http\Controllers\Admin\SipdCodeController;
use App\Http\Controllers\Admin\SshItemController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\Laporan\LaporanController;
use App\Http\Controllers\Opd\ProposalController;
use App\Http\Controllers\Publik\BerandaController;
use App\Http\Controllers\Sistem\AuditLogController;
use App\Http\Controllers\Sistem\OpdController;
use App\Http\Controllers\Sistem\TahunAnggaranController;
use App\Http\Controllers\Sistem\UserController;
use App\Http\Controllers\SurveiHarga\PriceSurveyController;
use App\Http\Controllers\Verifikasi\ProposalReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website Publik (Bab 3 & 18 kajian) — tanpa login.
|--------------------------------------------------------------------------
*/
Route::get('/', [BerandaController::class, 'index'])->name('publik.beranda');
Route::get('/cari', [BerandaController::class, 'cari'])->name('publik.cari');
Route::get('/ssh/{ssh}', [BerandaController::class, 'detailSsh'])->name('publik.ssh.show');
Route::get('/hspk/{hspk}', [BerandaController::class, 'detailHspk'])->name('publik.hspk.show');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Back Office — wajib login (Bab 4-18 kajian).
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // MASTER DATA & MAPPING — Super Admin + Admin SSH.
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin,admin_ssh')->group(function () {
        Route::resource('ssh', SshItemController::class)->except(['destroy']);
        Route::post('ssh-cek-serupa', [SshItemController::class, 'cekSerupa'])->name('ssh.cek-serupa');
        Route::post('ssh/{ssh}/nonaktifkan', [SshItemController::class, 'nonaktifkan'])->name('ssh.nonaktifkan');
        Route::post('ssh/{ssh}/aktifkan', [SshItemController::class, 'aktifkan'])->name('ssh.aktifkan');

        Route::resource('sbu', SbuItemController::class)->except(['destroy']);
        Route::post('sbu/{sbu}/nonaktifkan', [SbuItemController::class, 'nonaktifkan'])->name('sbu.nonaktifkan');
        Route::post('sbu/{sbu}/aktifkan', [SbuItemController::class, 'aktifkan'])->name('sbu.aktifkan');

        // ->parameters() dipaksa cocok dengan nama variabel di controller (mis. Category $category) —
        // tanpa ini Laravel menebak wildcard dari kata Indonesia (mis. {kategori}) dan route-model
        // binding implisit gagal karena nama parameter tidak pernah sama persis.
        Route::resource('kategori', CategoryController::class)->parameters(['kategori' => 'category'])->only(['index', 'store', 'update', 'destroy']);
        Route::resource('kelompok-barang', AssetGroupController::class)->parameters(['kelompok-barang' => 'assetGroup'])->only(['index', 'store', 'update', 'destroy']);
        Route::resource('kode-aset', AssetCodeController::class)->parameters(['kode-aset' => 'assetCode'])->only(['index', 'store', 'update', 'destroy']);
        Route::resource('kode-rekening', AccountCodeController::class)->parameters(['kode-rekening' => 'accountCode'])->only(['index', 'store', 'update', 'destroy']);
        Route::resource('kode-sipd', SipdCodeController::class)->parameters(['kode-sipd' => 'sipdCode'])->only(['index', 'store', 'update', 'destroy']);
        Route::resource('penyedia', VendorController::class)->parameters(['penyedia' => 'vendor'])->only(['index', 'store', 'update', 'destroy']);

        Route::get('mapping', [MappingController::class, 'index'])->name('mapping.index');
        Route::post('mapping', [MappingController::class, 'store'])->name('mapping.store');
        Route::put('mapping/{mapping}', [MappingController::class, 'update'])->name('mapping.update');
        Route::delete('mapping/{mapping}', [MappingController::class, 'destroy'])->name('mapping.destroy');
        Route::post('mapping/validasi', [MappingController::class, 'validasi'])->name('mapping.validasi');
        Route::get('mapping/cek-kode', [MappingController::class, 'cekKode'])->name('mapping.cek-kode');
    });

    // ANALISIS HSPK & ASB — Super Admin + Admin HSPK/ASB.
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin,admin_hspk_asb')->group(function () {
        Route::resource('hspk', HspkController::class)->except(['destroy']);
        Route::post('hspk/{hspk}/komponen', [HspkController::class, 'tambahKomponen'])->name('hspk.komponen.store');
        Route::delete('hspk/{hspk}/komponen/{component}', [HspkController::class, 'hapusKomponen'])->name('hspk.komponen.destroy');
        Route::post('hspk/{hspk}/hitung-ulang', [HspkController::class, 'hitungUlang'])->name('hspk.hitung-ulang');
        Route::post('hspk/{hspk}/nonaktifkan', [HspkController::class, 'nonaktifkan'])->name('hspk.nonaktifkan');

        Route::resource('asb', AsbController::class)->except(['destroy']);
        Route::post('asb/{asb}/variabel', [AsbController::class, 'simpanVariabel'])->name('asb.variabel.store');
        Route::delete('asb/{asb}/variabel/{variable}', [AsbController::class, 'hapusVariabel'])->name('asb.variabel.destroy');
        Route::post('asb/{asb}/formula', [AsbController::class, 'simpanFormula'])->name('asb.formula.store');
        Route::post('asb/{asb}/hitung-ulang', [AsbController::class, 'hitungUlang'])->name('asb.hitung-ulang');
        Route::post('asb/{asb}/nonaktifkan', [AsbController::class, 'nonaktifkan'])->name('asb.nonaktifkan');
    });

    // USULAN OPD — OPD/Operator (Super Admin bisa lihat semua untuk keperluan support).
    Route::prefix('usulan')->name('opd.usulan.')->middleware('role:super_admin,opd_operator')->group(function () {
        Route::get('/', [ProposalController::class, 'index'])->name('index');
        Route::get('/buat', [ProposalController::class, 'create'])->name('create');
        Route::post('/', [ProposalController::class, 'store'])->name('store');
        Route::post('/cek-serupa', [ProposalController::class, 'cekSerupa'])->name('cek-serupa');
        Route::get('/cari-item', [ProposalController::class, 'cariItem'])->name('cari-item');
        Route::get('/{proposal}', [ProposalController::class, 'show'])->name('show');
        Route::post('/{proposal}/ajukan-ulang', [ProposalController::class, 'ajukanUlang'])->name('ajukan-ulang');
    });

    // VERIFIKASI USULAN — approval berjenjang: Verifikator, Tim Standar Harga, Pejabat Berwenang.
    Route::prefix('verifikasi')->name('verifikasi.')->middleware('role:super_admin,verifikator,tim_standar_harga,pejabat_berwenang')->group(function () {
        Route::get('/', [ProposalReviewController::class, 'index'])->name('index');
        Route::get('/{proposal}', [ProposalReviewController::class, 'show'])->name('show');
        Route::post('/{proposal}/putuskan', [ProposalReviewController::class, 'putuskan'])->name('putuskan');
    });

    // SURVEI HARGA — Super Admin, Admin SSH, OPD/Operator.
    Route::prefix('survei-harga')->name('survei-harga.')->middleware('role:super_admin,admin_ssh,opd_operator')->group(function () {
        Route::get('/', [PriceSurveyController::class, 'index'])->name('index');
        Route::get('/buat', [PriceSurveyController::class, 'create'])->name('create');
        Route::post('/', [PriceSurveyController::class, 'store'])->name('store');
        Route::get('/{priceSurvey}', [PriceSurveyController::class, 'show'])->name('show');
        Route::delete('/{priceSurvey}', [PriceSurveyController::class, 'destroy'])->name('destroy');
    });

    // IMPORT / EXPORT — Super Admin + Admin SSH.
    Route::prefix('import-export')->name('import-export.')->middleware('role:super_admin,admin_ssh')->group(function () {
        Route::get('/', [ImportExportController::class, 'index'])->name('index');
        Route::get('/template/{jenis}', [ImportExportController::class, 'unduhTemplate'])->name('template');
        Route::post('/import', [ImportExportController::class, 'import'])->name('import');
        Route::post('/export', [ImportExportController::class, 'exportExcel'])->name('export');
        Route::post('/export-sipd', [ImportExportController::class, 'exportSipd'])->name('export-sipd');
        Route::get('/download/{export}', [ImportExportController::class, 'download'])->name('download');
    });

    // LAPORAN — Super Admin, Admin SSH, Admin HSPK/ASB, dan seluruh tahap approval berjenjang.
    Route::prefix('laporan')->name('laporan.')->middleware('role:super_admin,admin_ssh,admin_hspk_asb,verifikator,tim_standar_harga,pejabat_berwenang')->group(function () {
        Route::get('/rekap/{jenis}', [LaporanController::class, 'rekap'])->name('rekap');
        Route::get('/perubahan-harga', [LaporanController::class, 'perubahanHarga'])->name('perubahan-harga');
        Route::get('/riwayat-data', [LaporanController::class, 'riwayatData'])->name('riwayat-data');
    });

    // SISTEM — Super Admin saja.
    Route::prefix('sistem')->name('sistem.')->middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('opd', OpdController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tahun-anggaran', TahunAnggaranController::class)->only(['index', 'store', 'update']);
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    });
});
