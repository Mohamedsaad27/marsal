<?php

namespace App\Modules\Notifications\Infrastructure\Providers;

use App\Modules\Notifications\Application\Listeners\HandleApprovalRequested;
use App\Modules\Notifications\Application\Listeners\HandleCollectionCashReceived;
use App\Modules\Notifications\Application\Listeners\HandleNewMessageSent;
use App\Modules\Notifications\Application\Listeners\HandleOrderAssigned;
use App\Modules\Notifications\Application\Listeners\HandleOrderStatusChanged;
use App\Modules\Notifications\Application\Listeners\HandlePhoneUpdated;
use App\Modules\Notifications\Application\Listeners\HandlePostponedReminderDue;
use App\Modules\Notifications\Application\Listeners\HandleRefusalTimerExpired;
use App\Modules\Notifications\Application\Listeners\HandleRefusalTimerStarted;
use App\Modules\Notifications\Application\Listeners\HandleSettlementCreated;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnAgentOrderStatusChanged;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnApprovalRequested;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnCollectionRecorded;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnOrderReassigned;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnRefusalTimerExpired;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnRefusalTimerStarted;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnReturnReceivedByAdmin;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnReturnSentToCompany;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnSettlementCreated;
use App\Modules\Notifications\Application\Listeners\SuperAdmin\NotifySuperAdminsOnSettlementPaid;
use App\Modules\Notifications\Domain\Events\AgentOrderStatusChanged;
use App\Modules\Notifications\Domain\Events\ApprovalRequested;
use App\Modules\Notifications\Domain\Events\CollectionCashReceived;
use App\Modules\Notifications\Domain\Events\CollectionRecorded;
use App\Modules\Notifications\Domain\Events\NewMessageSent;
use App\Modules\Notifications\Domain\Events\OrderAssigned;
use App\Modules\Notifications\Domain\Events\OrderReassigned;
use App\Modules\Notifications\Domain\Events\OrderStatusChanged;
use App\Modules\Notifications\Domain\Events\PhoneUpdated;
use App\Modules\Notifications\Domain\Events\PostponedReminderDue;
use App\Modules\Notifications\Domain\Events\RefusalTimerExpired;
use App\Modules\Notifications\Domain\Events\RefusalTimerStarted;
use App\Modules\Notifications\Domain\Events\ReturnReceivedByAdmin;
use App\Modules\Notifications\Domain\Events\ReturnSentToCompany;
use App\Modules\Notifications\Domain\Events\SettlementCreated;
use App\Modules\Notifications\Domain\Events\SettlementPaid;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'notifications',
        );

        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // ── Lang files ──────────────────────────────────────────────────────
        $this->loadTranslationsFrom(
            __DIR__ . '/../../Presentation/Resources/Lang',
            'notifications'
        );

        // ── Event → Listener Mappings ───────────────────────────────────────
        // 1. طلب توصيل جديد — تعيين مندوب
        Event::listen(OrderAssigned::class, HandleOrderAssigned::class);

        // 2. تحديث حالة الطلب
        Event::listen(OrderStatusChanged::class, HandleOrderStatusChanged::class);

        // 3. طلب موافقة على تغيير السعر
        Event::listen(ApprovalRequested::class, HandleApprovalRequested::class);

        // 4. بدأ توقيت رفض الاستلام
        Event::listen(RefusalTimerStarted::class, HandleRefusalTimerStarted::class);

        // 5. انتهى وقت رفض الاستلام (يُرسل لشركة الشحن والمندوب معاً)
        Event::listen(RefusalTimerExpired::class, HandleRefusalTimerExpired::class);

        // 6. رسالة جديدة في المحادثة
        Event::listen(NewMessageSent::class, HandleNewMessageSent::class);

        // 7. تم تحديث رقم الهاتف (تنبيه أمني)
        Event::listen(PhoneUpdated::class, HandlePhoneUpdated::class);

        // 8. تذكير بموعد تسليم مؤجل
        Event::listen(PostponedReminderDue::class, HandlePostponedReminderDue::class);

        Event::listen(SettlementCreated::class, HandleSettlementCreated::class);
        Event::listen(CollectionCashReceived::class, HandleCollectionCashReceived::class);

        // ── Super-admin notifications ───────────────────────────────────────
        Event::listen(AgentOrderStatusChanged::class, NotifySuperAdminsOnAgentOrderStatusChanged::class);
        Event::listen(ApprovalRequested::class, NotifySuperAdminsOnApprovalRequested::class);
        Event::listen(RefusalTimerStarted::class, NotifySuperAdminsOnRefusalTimerStarted::class);
        Event::listen(RefusalTimerExpired::class, NotifySuperAdminsOnRefusalTimerExpired::class);
        Event::listen(CollectionRecorded::class, NotifySuperAdminsOnCollectionRecorded::class);
        Event::listen(OrderReassigned::class, NotifySuperAdminsOnOrderReassigned::class);
        Event::listen(SettlementCreated::class, NotifySuperAdminsOnSettlementCreated::class);
        Event::listen(SettlementPaid::class, NotifySuperAdminsOnSettlementPaid::class);
        Event::listen(ReturnReceivedByAdmin::class, NotifySuperAdminsOnReturnReceivedByAdmin::class);
        Event::listen(ReturnSentToCompany::class, NotifySuperAdminsOnReturnSentToCompany::class);
    }
}
