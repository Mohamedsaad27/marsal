<?php

namespace App\Modules\Settings\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use App\Modules\Settings\Application\DTOs\UpdateSettingsDTO;

class UpdateSettingsRequest extends BaseFormRequest
{
    protected function translationNamespace(): string
    {
        return 'settings';
    }

    public function rules(): array
    {
        return [
            // Platform Identity
            'platform_name' => ['sometimes', 'string', 'max:100'],
            'logo_url'      => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // Organization Info
            'org_name'       => ['sometimes', 'string', 'max:200'],
            'commercial_reg' => ['sometimes', 'nullable', 'string', 'max:100'],
            'official_email' => ['sometimes', 'email', 'max:255'],
            'contact_phone'  => ['sometimes', 'string', 'max:20'],
            'address'        => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function toDTO(): UpdateSettingsDTO
    {
        // Only pass keys that were actually sent in the request
        return UpdateSettingsDTO::fromArray(
            array_filter($this->validated(), fn ($v) => $v !== null)
        );
    }
}
