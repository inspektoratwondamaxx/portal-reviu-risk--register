<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Lapisan keamanan multi-tenant tambahan di level basis data (Bab V.4 kajian teknis).
     * Diterapkan pada tabel yang memiliki kolom kampung_id langsung: users, kegiatan,
     * transaksis, periode_spj. Variabel sesi app.current_kampung_id / app.current_role
     * di-set oleh App\Http\Middleware\SetTenantSessionContext pada setiap request
     * (lihat Bab VI.7). Postgres-only — no-op pada driver lain (mis. sqlite saat testing).
     */
    private array $tables = ['users', 'kegiatan', 'transaksis', 'periode_spj'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            // FORCE agar berlaku juga untuk role pemilik tabel (koneksi aplikasi), bukan hanya non-owner.
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("
                CREATE POLICY {$table}_tenant_isolation ON {$table}
                USING (
                    current_setting('app.current_role', true) IN ('inspektorat', 'admin')
                    OR kampung_id IS NULL
                    OR kampung_id = NULLIF(current_setting('app.current_kampung_id', true), '')::bigint
                )
                WITH CHECK (
                    current_setting('app.current_role', true) IN ('inspektorat', 'admin')
                    OR kampung_id = NULLIF(current_setting('app.current_kampung_id', true), '')::bigint
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
