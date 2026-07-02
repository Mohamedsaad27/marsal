<?php

namespace App\Modules\Orders\Domain\Services;

/**
 * Single source of truth for the Arabic Excel column layout shared by import & export.
 *
 * Import layout (12 columns, 0-based):
 *  [0]  الكود … [11] موقف العميل
 *
 * Export extends import by inserting:
 *  - مرتجعات       after عدد القطع
 *  - المبلغ المحصل after الإجمالي
 *  - موقف العميل   after المبلغ المحصل (replaces the trailing import status column)
 */
final class OrdersExcelSchema
{
  /** @var list<string> */
    public const IMPORT_HEADINGS = [
        'الكود',
        'اسم العميل',
        'العنوان',
        'المحافظة',
        'رقم التليفون',
        'وصف الشحنة',
        'عدد القطع',
        'الإجمالي',
        'اسم الشركة',
        'اسم الراسل',
        'اسم المندوب',
        'موقف العميل',
    ];

  /** @var list<string> */
    public const EXPORT_HEADINGS = [
        'الكود',
        'اسم العميل',
        'العنوان',
        'المحافظة',
        'رقم التليفون',
        'وصف الشحنة',
        'عدد القطع',
        'مرتجعات',
        'الإجمالي',
        'المبلغ المحصل',
        'موقف العميل',
        'اسم الشركة',
        'اسم الراسل',
        'اسم المندوب',
    ];

    public const IMPORT_COL_REFERENCE_NO = 0;
    public const IMPORT_COL_CUSTOMER_NAME = 1;
    public const IMPORT_COL_ADDRESS = 2;
    public const IMPORT_COL_GOVERNORATE = 3;
    public const IMPORT_COL_PHONES = 4;
    public const IMPORT_COL_DESCRIPTION = 5;
    public const IMPORT_COL_QUANTITY = 6;
    public const IMPORT_COL_TOTAL = 7;
    public const IMPORT_COL_DISPLAY_COMPANY = 8;
    public const IMPORT_COL_SHIPPING_COMPANY = 9;
    public const IMPORT_COL_AGENT = 10;
    public const IMPORT_COL_STATUS = 11;

    private function __construct() {}
}
