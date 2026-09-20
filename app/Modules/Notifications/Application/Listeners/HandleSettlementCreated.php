<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\SettlementCreated;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleSettlementCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase $sendNotification,
    ) {}

    public function handle(SettlementCreated $event): void
    {
        $userId = $event->settlementType === SettlementTypeEnum::Agent
            ? $event->agentUserId
            : $event->companyUserId;

        if ($userId === null) {
            return;
        }

        $message = $this->templateService->build(
            NotificationTypeEnum::Settled,
            [
                'settlement_action' => 'تم إنشاء تسوية جديدة',
                'entity_label' => $event->entityLabel,
                'payment_direction' => $this->directionLabel($event->paymentDirection),
                'payable_amount' => $event->payableAmount,
            ],
        );

        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId: $userId,
                type: NotificationTypeEnum::Settled,
                message: $message,
                data: [
                    'settlement_id' => $event->settlementId,
                    'settlement_type' => (string) $event->settlementType->value,
                    'payment_direction' => $event->paymentDirection,
                    'payable_amount' => $event->payableAmount,
                ],
                sendViaFcm: true,
            )
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
