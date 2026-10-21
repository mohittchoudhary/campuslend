<?php
/**
 * CampusLend Global Settings Model
 * Manages dynamic runtime toggles (e.g. Exam Season Mode, Banner Alert Text)
 */

namespace App\Models;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, ?string $default = null): ?string
    {
        $record = self::findBy('setting_key', $key);
        return $record ? $record['setting_value'] : $default;
    }

    public static function set(string $key, string $value): bool
    {
        $existing = self::findBy('setting_key', $key);
        if ($existing) {
            return self::update((int)$existing['id'], [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        self::insert([
            'setting_key' => $key,
            'setting_value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }
}
