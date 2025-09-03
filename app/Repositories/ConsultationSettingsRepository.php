<?php

namespace App\Repositories;

use App\Models\Option;
use Illuminate\Support\Arr;

class ConsultationSettingsRepository
{
    private const SETTINGS_TYPE = 'consultation_settings';
    private const SETTINGS_SLUG = 'consultation_settings';

    public function get(): array
    {
        $option = Option::query()
            ->where('type', self::SETTINGS_TYPE)
            ->where('slug', self::SETTINGS_SLUG)
            ->first();

        $meta = $option?->meta ?? [];

        return [
            'reasons' => Arr::get($meta, 'reasons', []),
            'default_duration_minutes' => Arr::get($meta, 'default_duration_minutes', 30),
            'available_from' => Arr::get($meta, 'available_from'),
            'available_until' => Arr::get($meta, 'available_until'),
            'slot_interval_minutes' => Arr::get($meta, 'slot_interval_minutes'),
        ];
    }

    public function upsert(array $payload): array
    {
        $option = Option::query()
            ->where('type', self::SETTINGS_TYPE)
            ->where('slug', self::SETTINGS_SLUG)
            ->first();

        if (! $option) {
            $option = new Option();
            $option->type = self::SETTINGS_TYPE;
            $option->slug = self::SETTINGS_SLUG;
            $option->name = 'Consultation Settings';
        }

        $meta = $option->meta ?? [];

        if (array_key_exists('reasons', $payload)) {
            $meta['reasons'] = array_values(array_map(function ($reason) {
                return [
                    'key' => Arr::get($reason, 'key'),
                    'name' => Arr::get($reason, 'name'),
                    'allowed_durations' => Arr::get($reason, 'allowed_durations', []),
                    'allowed_datetimes' => Arr::get($reason, 'allowed_datetimes', []),
                ];
            }, $payload['reasons'] ?? []));
        }

        if (array_key_exists('default_duration_minutes', $payload)) {
            $meta['default_duration_minutes'] = (int) $payload['default_duration_minutes'];
        }

        if (array_key_exists('available_from', $payload)) {
            $meta['available_from'] = $payload['available_from'];
        }

        if (array_key_exists('available_until', $payload)) {
            $meta['available_until'] = $payload['available_until'];
        }

        if (array_key_exists('slot_interval_minutes', $payload)) {
            $meta['slot_interval_minutes'] = (int) $payload['slot_interval_minutes'];
        }

        $option->meta = $meta;
        $option->save();

        return $this->get();
    }
}


