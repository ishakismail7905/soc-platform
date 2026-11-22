<?php

namespace App\Support;

use App\Models\ActivityLog;

/** One readable line for the "Detail" column of the activity log (wireframe 15). */
final class AuditDetail
{
    public static function summarise(ActivityLog $log): string
    {
        $properties = $log->properties ?? [];

        // PERMISSION_CHANGE: "role: soc_analyst → it_manager"
        if (isset($properties['field']) && array_key_exists('from', $properties)) {
            return $properties['field'].': '.self::show($properties['from']).' → '.self::show($properties['to'] ?? null);
        }

        unset($properties['client_id'], $properties['target_role']); // shown in their own columns

        return collect($properties)
            ->map(fn ($value, $key) => str_replace('_', ' ', (string) $key).': '.self::show($value))
            ->join(' · ');
    }

    private static function show(mixed $value): string
    {
        return match (true) {
            is_array($value) => $value === [] ? 'none' : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            is_bool($value) => $value ? 'yes' : 'no',
            $value === null => '—',
            default => (string) $value,
        };
    }
}