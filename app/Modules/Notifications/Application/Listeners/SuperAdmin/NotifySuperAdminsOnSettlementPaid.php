<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\SettlementPaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnSettlementPaid implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(SettlementPaid $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::Settled,
            templateVars: [
                'settlement_action' => 'تم دفع تسوية',
                'entity_label' => $event->entityLabel,
                'payment_direction' => $this->directionLabel($event->paymentDirection),
                'payable_amount' => $event->payableAmount,
            ],
            data: [
                'settlement_id' => $event->settlementId,
                'payment_direction' => $event->paymentDirection,
                'payable_amount' => $event->payableAmount,
            ],
        );
    }

    private function directionLabel(string $direction): string
    {
        return match ($direction) {
            'agent_to_system' => 'من المندوب إلى النظام',
            'system_to_agent' => 'من النظام إلى المندوب',
            'system_to_company' => 'من النظام إلى الشركة',
            'company_to_system' => 'من الشركة إلى النظام',
            default => 'لا توجد دفعة',
        };
    }
}
