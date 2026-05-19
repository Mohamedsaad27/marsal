<?php 

namespace App\Modules\Core\Infrastructure\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (! empty($model->{$model->getKeyName()})) {
                return;
            }

            $version = config('core.uuid.version', 'v4');

            $model->{$model->getKeyName()} = (string) (
                $version === 'v7' && method_exists(Str::class, 'uuid7')
                    ? Str::uuid7()
                    : Str::uuid()
            );
        });
    }
}