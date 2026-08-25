<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Audit trail global (Bab IV.4 / KNF-05 kajian teknis): setiap create/update/delete pada model
 * yang memakai trait ini otomatis dicatat ke audit_logs beserta pelaku, waktu, dan nilai
 * sebelum/sesudah. Dipasang di seluruh model transaksional kecuali AuditLog sendiri.
 */
trait LogsAudit
{
    public static function bootLogsAudit(): void
    {
        static::created(fn ($model) => $model->writeAuditLog('created', null, $model->getAttributes()));

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            if (empty($changes)) {
                return;
            }
            $model->writeAuditLog('updated', array_intersect_key($model->getOriginal(), $changes), $changes);
        });

        static::deleted(fn ($model) => $model->writeAuditLog(
            method_exists($model, 'isForceDeleting') && $model->isForceDeleting() ? 'force_deleted' : 'deleted',
            $model->getOriginal(),
            null
        ));
    }

    protected function writeAuditLog(string $action, ?array $before, ?array $after): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'kampung_id' => $this->getAttribute('kampung_id'),
            'model_type' => static::class,
            'model_id' => $this->getKey(),
            'action' => $action,
            'data_sebelum' => $before,
            'data_sesudah' => $after,
            'ip_address' => app()->runningInConsole() ? null : request()?->ip(),
        ]);
    }
}
