<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ConsultationSettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ConsultationSettingsAdminController extends Controller
{
    private ConsultationSettingsRepository $repo;

    public function __construct(ConsultationSettingsRepository $repo)
    {
        $this->repo = $repo;
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    public function show()
    {
        return $this->ok($this->repo->get());
    }

    public function update(Request $request)
    {
        // Accept flexible payload shapes from frontend
        $input = $request->all();

        // Map camelCase to snake_case
        // Map availability window keys
        if (array_key_exists('availableFrom', $input)) {
            $input['available_from'] = $input['availableFrom'];
        }
        if (array_key_exists('availableUntil', $input)) {
            $input['available_until'] = $input['availableUntil'];
        }
        if (array_key_exists('slotIntervalMinutes', $input)) {
            $input['slot_interval_minutes'] = $input['slotIntervalMinutes'];
        }
        if (array_key_exists('defaultDurationMinutes', $input)) {
            $input['default_duration_minutes'] = $input['defaultDurationMinutes'];
        }

        // Normalize reasons: support 'reason' string, array of strings, or array of objects
        if (!empty($input['reason']) && empty($input['reasons'])) {
            $input['reasons'] = is_array($input['reason']) ? $input['reason'] : [$input['reason']];
        }
        if (isset($input['reasons']) && is_string($input['reasons'])) {
            // Comma-separated string
            $input['reasons'] = array_filter(array_map('trim', explode(',', $input['reasons'])));
        }

        $normalizedReasons = [];
        if (!empty($input['reasons']) && is_array($input['reasons'])) {
            foreach ($input['reasons'] as $reason) {
                if (is_string($reason)) {
                    $name = trim($reason);
                    if ($name === '') continue;
                    $normalizedReasons[] = [
                        'key' => Str::slug($name, '_'),
                        'name' => $name,
                        'allowed_durations' => [],
                        'allowed_datetimes' => [],
                    ];
                } else if (is_array($reason)) {
                    $name = $reason['name'] ?? ($reason['label'] ?? ($reason['title'] ?? null));
                    $key = $reason['key'] ?? ($name ? Str::slug($name, '_') : null);
                    $allowedDurations = $reason['allowed_durations'] ?? ($reason['allowedDurations'] ?? []);
                    $allowedDatetimes = $reason['allowed_datetimes'] ?? ($reason['allowedDatetimes'] ?? []);
                    if ($name && $key) {
                        $normalizedReasons[] = [
                            'key' => $key,
                            'name' => $name,
                            'allowed_durations' => array_values(array_filter(array_map('intval', (array) $allowedDurations))),
                            'allowed_datetimes' => array_values(array_filter(array_map('strval', (array) $allowedDatetimes))),
                        ];
                    }
                }
            }
            $input['reasons'] = $normalizedReasons;
        }

        // Normalize duration
        if (isset($input['default_duration_minutes'])) {
            $input['default_duration_minutes'] = (int) $input['default_duration_minutes'];
            if ($input['default_duration_minutes'] < 15) $input['default_duration_minutes'] = 15;
            if ($input['default_duration_minutes'] > 180) $input['default_duration_minutes'] = 180;
        }

        // Parse flexible datetime formats for availability window
        if (!empty($input['available_from'])) {
            $input['available_from'] = $this->parseFlexibleDateTime($input['available_from']);
        }
        if (!empty($input['available_until'])) {
            $input['available_until'] = $this->parseFlexibleDateTime($input['available_until']);
        }

        // Final relaxed validation
        $validated = $request->validate([
            'reasons' => 'sometimes|array',
            'reasons.*.key' => 'required_with:reasons|string',
            'reasons.*.name' => 'required_with:reasons|string',
            'reasons.*.allowed_durations' => 'sometimes|array',
            'reasons.*.allowed_durations.*' => 'integer|min:0|max:10000',
            'reasons.*.allowed_datetimes' => 'sometimes|array',
            'reasons.*.allowed_datetimes.*' => 'string',
            'default_duration_minutes' => 'sometimes|integer|min:15|max:180',
            'available_from' => 'sometimes|string',
            'available_until' => 'sometimes|string',
            'slot_interval_minutes' => 'sometimes|integer|min:5|max:1440',
        ]);

        // Merge our normalized values into validated
        $payload = array_merge($input, $validated);

        // Generate allowed slots for reasons without explicit allowed_datetimes
        if (!empty($payload['available_from']) && !empty($payload['available_until']) && !empty($payload['slot_interval_minutes'])) {
            $start = Carbon::parse($payload['available_from']);
            $end = Carbon::parse($payload['available_until']);
            $interval = (int) $payload['slot_interval_minutes'];
            if ($start && $end && $start->lt($end) && $interval > 0) {
                $slots = [];
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $slots[] = $cursor->format('Y-m-d H:i:s');
                    $cursor->addMinutes($interval);
                }
                $reasons = $payload['reasons'] ?? [];
                $reasons = array_map(function ($r) use ($slots) {
                    $hasExplicit = !empty($r['allowed_datetimes']);
                    if (!$hasExplicit) {
                        $r['allowed_datetimes'] = $slots;
                    }
                    return $r;
                }, $reasons);
                $payload['reasons'] = $reasons;
            }
        }

        $data = $this->repo->upsert($payload);
        return $this->success(['message' => 'Consultation settings updated', 'data' => $data]);
    }

    private function parseFlexibleDateTime(string $value): ?string
    {
        $candidate = trim($value);
        // Fix common typo like MM/DD:YYYY -> MM/DD/YYYY
        if (preg_match('/^(\d{1,2}\/\d{1,2}):(\d{4})(.*)$/', $candidate, $m)) {
            $candidate = str_replace($m[1] . ':' . $m[2], $m[1] . '/' . $m[2], $candidate);
        }

        $formats = [
            'm/d/Y h:i A',
            'm/d/Y H:i',
            'd/m/Y h:i A',
            'd/m/Y H:i',
            'Y-m-d\TH:i:sP',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
        ];

        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $candidate);
                if ($dt !== false) {
                    return $dt->toIso8601String();
                }
            } catch (\Throwable $e) {
                // try next
            }
        }

        try {
            // Fallback to Carbon::parse (supports many formats)
            return Carbon::parse($candidate)->toIso8601String();
        } catch (\Throwable $e) {
            return null;
        }
    }
}


