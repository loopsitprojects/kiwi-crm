<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    const SSCL_RATE = 2.5641;

    protected $fillable = ['key', 'value', 'group'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if ($setting) {
            return is_numeric($setting->value) ? (float)$setting->value : $setting->value;
        }
        if ($key === 'sscl_rate') {
            return self::SSCL_RATE;
        }
        return $default;
    }

    public static function set($key, $value, $group = 'general')
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Check if Halloween seasonal theme is enabled.
     * Defaults to true (ON).
     */
    public static function isHalloweenTheme(): bool
    {
        $val = self::get('halloween_theme_enabled', true);
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}
