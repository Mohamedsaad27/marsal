<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Domain\Enums\PermissionEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $enum = PermissionEnum::tryFrom($this->name);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'label_ar' => $enum?->labelAr() ?? $this->name,
            'label_en' => $enum?->labelEn() ?? $this->name,
            'group' => $enum?->group() ?? explode('.', $this->name)[0],
            'group_label_ar' => $enum?->groupLabelAr() ?? null,
        ];
    }
}
