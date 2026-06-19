<?php

namespace App\Modules\AuditLog\Application\Services;

use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;

class AuditDescriptionBuilder
{
    public function build(
        AuditEventEnum $event,
        string         $auditableType,
        array          $oldValues = [],
        array          $newValues = [],
        array          $metadata  = [],
    ): string {
        if (! empty($metadata['description'])) {
            return (string) $metadata['description'];
        }

        $entity  = $this->entityLabel($auditableType);
        $subject = $this->subjectLabel($oldValues, $newValues, $metadata);

        if (($metadata['action'] ?? null) === 'sync_permissions') {
            $roleName = $metadata['role_name'] ?? $subject;

            return __('audit_logs::descriptions.permissions_sync', ['role' => $roleName]);
        }

        return match ($event) {
            AuditEventEnum::Created => __('audit_logs::descriptions.created', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Deleted => __('audit_logs::descriptions.deleted', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Restored => __('audit_logs::descriptions.restored', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Login => __('audit_logs::descriptions.login', [
                'subject' => $subject,
            ]),
            AuditEventEnum::Logout => __('audit_logs::descriptions.logout', [
                'subject' => $subject,
            ]),
            AuditEventEnum::PasswordChanged => __('audit_logs::descriptions.password_changed', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Activated => __('audit_logs::descriptions.activated', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Deactivated => __('audit_logs::descriptions.deactivated', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::StatusChanged => $this->buildStatusChanged($entity, $subject, $oldValues, $newValues, $metadata),
            AuditEventEnum::Assigned => __('audit_logs::descriptions.assigned', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Approved => __('audit_logs::descriptions.approved', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Rejected => __('audit_logs::descriptions.rejected', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Settled => __('audit_logs::descriptions.settled', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Collected => __('audit_logs::descriptions.collected', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Returned => __('audit_logs::descriptions.returned', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Exported => __('audit_logs::descriptions.exported', [
                'entity'  => $entity,
                'subject' => $subject,
            ]),
            AuditEventEnum::Updated => $this->buildUpdated($entity, $subject, $oldValues, $newValues),
            default => $event->labelAr(),
        };
    }

    private function buildUpdated(string $entity, string $subject, array $oldValues, array $newValues): string
    {
        $changes = $this->diffChanges($oldValues, $newValues);

        if ($changes === []) {
            return __('audit_logs::descriptions.updated', [
                'entity'  => $entity,
                'subject' => $subject,
            ]);
        }

        $header = __('audit_logs::descriptions.updated_with_changes', [
            'entity'  => $entity,
            'subject' => $subject,
        ]);

        return $header.' — '.$changes;
    }

    private function buildStatusChanged(
        string $entity,
        string $subject,
        array $oldValues,
        array $newValues,
        array $metadata,
    ): string {
        $ref = $metadata['order_ref'] ?? $subject;

        if (isset($oldValues['status_id'], $newValues['status_id'])) {
            return __('audit_logs::descriptions.status_changed', [
                'entity' => $entity,
                'subject' => $ref,
                'old'    => $this->formatValue($oldValues['status_id']),
                'new'    => $this->formatValue($newValues['status_id']),
            ]);
        }

        return __('audit_logs::descriptions.status_changed_generic', [
            'entity'  => $entity,
            'subject' => $ref,
        ]);
    }

    private function diffChanges(array $oldValues, array $newValues): string
    {
        $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        $parts = [];

        foreach ($keys as $key) {
            $old = $oldValues[$key] ?? null;
            $new = $newValues[$key] ?? null;

            if ($old == $new) {
                continue;
            }

            if ($key === 'permissions' && is_array($old) && is_array($new)) {
                $parts[] = __('audit_logs::descriptions.permissions_changed', [
                    'old_count' => count($old),
                    'new_count' => count($new),
                ]);

                continue;
            }

            $parts[] = __('audit_logs::descriptions.field_change', [
                'field' => $this->fieldLabel((string) $key),
                'old'   => $this->formatValue($old),
                'new'   => $this->formatValue($new),
            ]);
        }

        return implode('؛ ', $parts);
    }

    private function entityLabel(string $auditableType): string
    {
        $key = "audit_logs::entities.{$auditableType}";

        return __($key) !== $key ? __($key) : $auditableType;
    }

    private function fieldLabel(string $field): string
    {
        $key = "audit_logs::fields.{$field}";

        return __($key) !== $key ? __($key) : $field;
    }

    private function subjectLabel(array $oldValues, array $newValues, array $metadata): string
    {
        foreach (['name_ar', 'name_en', 'name', 'company_name', 'role_name', 'reference_code', 'code', 'email'] as $key) {
            foreach ([$metadata, $newValues, $oldValues] as $source) {
                if (! empty($source[$key])) {
                    return $this->quote((string) $source[$key]);
                }
            }
        }

        return '';
    }

    private function quote(string $value): string
    {
        return '«'.$value.'»';
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value
                ? __('audit_logs::values.active')
                : __('audit_logs::values.inactive');
        }

        if (is_array($value)) {
            if ($value === []) {
                return __('audit_logs::values.empty');
            }

            if (array_is_list($value)) {
                $items = array_map(fn ($item) => $this->formatValue($item), $value);

                return implode('، ', $items);
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        if ($value === null || $value === '') {
            return __('audit_logs::values.empty');
        }

        return (string) $value;
    }
}
