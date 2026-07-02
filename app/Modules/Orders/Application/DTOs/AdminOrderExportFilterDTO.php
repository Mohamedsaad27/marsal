<?php

namespace App\Modules\Orders\Application\DTOs;

readonly class AdminOrderExportFilterDTO
{
    public function __construct(
        public ?string $shippingCompanyId = null,
        public ?string $deliveryAgentId = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            shippingCompanyId: $data['shipping_company_id'] ?? null,
            deliveryAgentId:   $data['delivery_agent_id'] ?? null,
            status:            isset($data['status']) ? trim((string) $data['status']) : null,
            dateFrom:          $data['date_from'] ?? null,
            dateTo:            $data['date_to'] ?? null,
        );
    }
}
