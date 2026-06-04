<?php

namespace App\Modules\Users\Domain\Enums;

enum PermissionEnum: string
{
    case OrdersView = 'orders.view';
    case OrdersCreate = 'orders.create';
    case OrdersUpdate = 'orders.update';
    case OrdersDelete = 'orders.delete';
    case OrdersAssign = 'orders.assign';
    case OrdersExport = 'orders.export';
    case OrdersImport = 'orders.import';
    case OrdersViewFinancials = 'orders.view_financials';

    case ShippingCompaniesView = 'shipping_companies.view';
    case ShippingCompaniesCreate = 'shipping_companies.create';
    case ShippingCompaniesUpdate = 'shipping_companies.update';
    case ShippingCompaniesDelete = 'shipping_companies.delete';
    case ShippingCompaniesToggle = 'shipping_companies.toggle';

    case DeliveryAgentsView = 'delivery_agents.view';
    case DeliveryAgentsCreate = 'delivery_agents.create';
    case DeliveryAgentsUpdate = 'delivery_agents.update';
    case DeliveryAgentsDelete = 'delivery_agents.delete';
    case DeliveryAgentsToggle = 'delivery_agents.toggle';
    case DeliveryAgentsViewBalance = 'delivery_agents.view_balance';

    case CollectionsView = 'collections.view';
    case CollectionsCreate = 'collections.create';
    case CollectionsExport = 'collections.export';

    case ReturnsView = 'returns.view';
    case ReturnsReceive = 'returns.receive';
    case ReturnsSendToCompany = 'returns.send_to_company';

    case SettlementsView = 'settlements.view';
    case SettlementsCreate = 'settlements.create';
    case SettlementsApprove = 'settlements.approve';
    case SettlementsMarkPaid = 'settlements.mark_paid';

    case ApprovalRequestsView = 'approval_requests.view';
    case ApprovalRequestsApprove = 'approval_requests.approve';
    case ApprovalRequestsReject = 'approval_requests.reject';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';
    case UsersToggle = 'users.toggle';
    case UsersChangePassword = 'users.change_password';

    case RolesView = 'roles.view';
    case RolesManage = 'roles.manage';

    case NotificationsView = 'notifications.view';
    case NotificationsSend = 'notifications.send';

    case SettingsView = 'settings.view';
    case SettingsUpdate = 'settings.update';

    case ReportsView = 'reports.view';
    case ReportsExport = 'reports.export';

    case ChatView = 'chat.view';
    case ChatSend = 'chat.send';

    case GovernoratesView = 'governorates.view';
    case GovernoratesManage = 'governorates.manage';

    public function label(): string
    {
        return __("users::permissions.{$this->value}");
    }

    public function labelAr(): string
    {
        return trans("users::permissions.{$this->value}", locale: 'ar');
    }

    public function labelEn(): string
    {
        return trans("users::permissions.{$this->value}", locale: 'en');
    }

    public function group(): string
    {
        return explode('.', $this->value)[0];
    }

    public function groupLabelAr(): string
    {
        return match ($this->group()) {
            'orders' => 'الطلبات',
            'shipping_companies' => 'شركات الشحن',
            'delivery_agents' => 'المناديب',
            'collections' => 'التحصيلات',
            'returns' => 'المرتجعات',
            'settlements' => 'التسويات المالية',
            'approval_requests' => 'طلبات الموافقة',
            'users' => 'المستخدمون',
            'roles' => 'الأدوار والصلاحيات',
            'notifications' => 'الإشعارات',
            'settings' => 'الإعدادات',
            'reports' => 'التقارير',
            'chat' => 'الدردشة',
            'governorates' => 'المناطق الجغرافية',
            default => $this->group(),
        };
    }

    /**
     * @return array<string, array<int, self>>
     */
    public static function groupedBySection(): array
    {
        $grouped = [];

        foreach (self::cases() as $case) {
            $grouped[$case->group()][] = $case;
        }

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
