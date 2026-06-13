<?php

namespace App\Modules\Settings\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemSetting extends Model
{
    use HasUuid, SoftDeletes;

    protected $table      = 'system_settings';
    protected $primaryKey = 'system_setting_id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = ['key', 'value'];
}
