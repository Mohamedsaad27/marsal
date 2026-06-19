<?php

namespace App\Modules\Notifications\Presentation\Http\Resources;

use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $type = $this->resolveType();

        return [
            'id'           => $this->field('notification_id'),
            'type'         => [
                'code'  => $type->value,
                'label' => $type->labelAr(),
            ],
            'title_ar'     => $this->field('title_ar'),
            'body_ar'      => $this->field('body_ar'),
            'data'         => $this->field('data') ?? [],
            'is_read'      => (bool) $this->field('is_read'),
            'sent_via_fcm' => (bool) $this->field('sent_via_fcm'),
            'read_at'      => $this->timestamp('read_at'),
            'created_at'   => $this->timestamp('created_at'),
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Retrieve a field from either a model or an array resource.
     */
    private function field(string $key): mixed
    {
        return is_array($this->resource)
            ? ($this->resource[$key] ?? null)
            : $this->resource->{$key};
    }

    /**
     * Resolve the NotificationTypeEnum regardless of whether the resource
     * is a model (returns enum instance) or an array (returns backing int).
     */
    private function resolveType(): NotificationTypeEnum
    {
        $value = $this->field('notification_type');

        return $value instanceof NotificationTypeEnum
            ? $value
            : NotificationTypeEnum::from((int) $value);
    }

    /**
     * Return a timestamp field as an ISO-8601 string, handling both Carbon
     * instances (from a model) and datetime strings (from ->toArray()).
     */
    private function timestamp(string $key): ?string
    {
        $value = $this->field($key);

        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toISOString();
        }

        return Carbon::parse($value)->toISOString();
    }
}
