<?php

namespace App\Models\Config;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Config extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'value'];

    const ASSET_TYPES = [
        'logo',
        'logo_light',
        'icon',
        'favicon',
        'icon_maskable',
        "icon_512",
        "icon_192",
        "icon_180",
        "icon_32",
        "icon_16",
        'screenshot_web_1',
        'screenshot_web_2',
        'screenshot_web_3',
        'screenshot_mobile_1',
        'screenshot_mobile_2',
        'screenshot_mobile_3',
        'guest_layout_bg',
    ];

    protected $casts = [
        'value' => 'json',
        'meta'  => 'json'
    ];

    public function getValue(string $option)
    {
        return Arr::get($this->value, $option);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('config')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
