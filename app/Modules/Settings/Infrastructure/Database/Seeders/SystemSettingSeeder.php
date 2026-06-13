<?php

namespace App\Modules\Settings\Infrastructure\Database\Seeders;

use App\Modules\Settings\Infrastructure\Database\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'platform_name' => 'مرسال',
            'logo_url'      => null,
            'org_name'      => 'مرسال للخدمات اللوجستية',
            'commercial_reg'=> '1010234567',
            'official_email'=> 'ops@mursal.sa',
            'contact_phone' => '0112345678',
            'address'       => 'الرياض، حي العليا، طريق الملك فهد',
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key'   => $key],
                ['value' => $value],
            );
        }
    }
}
