<?php

namespace App\Modules\AuditLog\Infrastructure\Traits;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Throwable;

trait Auditable
{
    protected array $auditExclude = [
        'password',
        'remember_token',
        'fcm_token',
        'email_verified_at',
    ];

    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->recordAudit(AuditEventEnum::Created, [], $model->toAuditArray()));
        static::updated(fn ($model) => $model->recordAudit(AuditEventEnum::Updated, $model->toOldAuditArray(), $model->toNewAuditArray()));
        static::deleted(fn ($model) => $model->recordAudit(AuditEventEnum::Deleted, $model->toAuditArray(), []));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn ($model) => $model->recordAudit(AuditEventEnum::Restored, [], $model->toAuditArray()));
        }
    }

    private function recordAudit(AuditEventEnum $event, array $oldValues, array $newValues): void
    {
        try {
            app(RecordAuditUseCase::class)->execute(
                userId:        Auth::id(),
                event:         $event,
                auditableType: $this->getTable(),
                auditableId:   (string) $this->getKey(),
                oldValues:     $oldValues,
                newValues:     $newValues,
            );
        } catch (Throwable) {
            // Audit must never break the main flow
        }
    }

    private function toAuditArray(): array
    {
        return array_diff_key(
            $this->getAttributes(),
            array_flip($this->auditExclude),
        );
    }

    private function toOldAuditArray(): array
    {
        $old = [];

        foreach ($this->getChanges() as $key => $newVal) {
            if (in_array($key, $this->auditExclude, true)) {
                continue;
            }

            $old[$key] = $this->getOriginal($key);
        }

        return $old;
    }

    private function toNewAuditArray(): array
    {
        $new = [];

        foreach ($this->getChanges() as $key => $newVal) {
            if (in_array($key, $this->auditExclude, true)) {
                continue;
            }

            $new[$key] = $newVal;
        }

        return $new;
    }
}
