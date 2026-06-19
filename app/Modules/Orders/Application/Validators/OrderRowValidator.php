<?php

namespace App\Modules\Orders\Application\Validators;

use App\Modules\Orders\Application\DTOs\ImportOrderRowDTO;
use App\Modules\Orders\Domain\Enums\ImportStatusHintEnum;

class OrderRowValidator
{
    /** @return string[] list of error messages, empty = valid */
    public function validate(
        ImportOrderRowDTO $dto,
        ?ImportStatusHintEnum $hint = null,
        string $rawStatus = '',
    ): array {
        $errors = [];

        if (empty(trim($dto->customerName))) {
            $errors[] = "Row {$dto->rowNumber}: اسم العميل مطلوب";
        }

        if ($dto->customerPhones === []) {
            $errors[] = "Row {$dto->rowNumber}: رقم العميل مطلوب";
        }

        if (empty(trim($dto->addressLine))) {
            $errors[] = "Row {$dto->rowNumber}: العنوان مطلوب";
        }

        if (empty(trim($dto->governorateName))) {
            $errors[] = "Row {$dto->rowNumber}: المحافظة مطلوبة";
        }

        if (empty(trim($dto->companyName))) {
            $errors[] = "Row {$dto->rowNumber}: اسم الراسل (الشركة) مطلوب";
        }

        if ($dto->codAmount < 0) {
            $errors[] = "Row {$dto->rowNumber}: الإجمالي يجب أن يكون صفر أو أكثر";
        }

        if ($dto->quantity <= 0) {
            $errors[] = "Row {$dto->rowNumber}: العدد يجب أن يكون أكبر من صفر";
        }

        if (trim($rawStatus) !== '' && $hint === null) {
            $errors[] = "Row {$dto->rowNumber}: موقف العميل غير معروف: {$rawStatus}";
        }

        if ($hint?->hasCollection() && $dto->codAmount <= 0) {
            $errors[] = "Row {$dto->rowNumber}: حالة التحصيل تتطلب مبلغاً أكبر من صفر";
        }

        // Excel import has no GPS / postponed-date columns — reject rows that require them.
        if ($hint?->requiresGps()) {
            $errors[] = "Row {$dto->rowNumber}: حالة «{$hint->value}» تتطلب GPS ولا يمكن استيرادها من Excel";
        }

        if ($hint?->requiresPostponedDate()) {
            $errors[] = "Row {$dto->rowNumber}: حالة «{$hint->value}» تتطلب تاريخ تأجيل ولا يمكن استيرادها من Excel";
        }

        if (in_array($hint, [ImportStatusHintEnum::Assigned, ImportStatusHintEnum::OutForDelivery], true)
            && empty(trim($dto->agentName))) {
            $errors[] = "Row {$dto->rowNumber}: حالة «{$hint->value}» تتطلب اسم مندوب في العمود «اسم المندوب»";
        }

        return $errors;
    }
}
