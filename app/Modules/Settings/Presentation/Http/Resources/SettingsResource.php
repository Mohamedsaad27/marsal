<?php

namespace App\Modules\Settings\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'identity' => [
                'platform_name' => $this->resource['identity']['platform_name'] ?? null,
                'logo_url'      => $this->resource['identity']['logo_url']      ?? null,
            ],
            'organization' => [
                'org_name'       => $this->resource['organization']['org_name']       ?? null,
                'commercial_reg' => $this->resource['organization']['commercial_reg'] ?? null,
                'official_email' => $this->resource['organization']['official_email'] ?? null,
                'contact_phone'  => $this->resource['organization']['contact_phone']  ?? null,
                'address'        => $this->resource['organization']['address']        ?? null,
            ],
        ];
    }
}
