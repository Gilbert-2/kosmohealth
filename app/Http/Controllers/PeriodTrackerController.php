<?php

namespace App\Http\Controllers;

use App\Models\PeriodCycle;
use App\Models\PeriodSymptom;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PeriodTrackerController extends Controller
{
    /**
     * Get all period cycles
     */
    public function getCycles()
    {
        $cycles = PeriodCycle::where('user_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json(['cycles' => $cycles]);
    }

    /**
     * Save a new period cycle
     */
    public function saveCycle(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $cycle = PeriodCycle::create([
            'user_id' => auth()->id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json(['cycle' => $cycle], 201);
    }

    /**
     * Delete a period cycle
     */
    public function deleteCycle($id)
    {
        $cycle = PeriodCycle::where('user_id', auth()->id())
            ->findOrFail($id);
            
        $cycle->delete();

        return response()->json(['message' => 'Cycle deleted successfully']);
    }

    /**
     * Get predictions for next period and fertile window
     */
    public function getPredictions()
    {
        $lastCycle = PeriodCycle::where('user_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$lastCycle) {
            return response()->json(['message' => 'Not enough data for predictions']);
        }

        // Calculate average cycle length from last 3 cycles
        $cycles = PeriodCycle::where('user_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        $avgCycleLength = $cycles->avg(function($cycle) {
            return Carbon::parse($cycle->start_date)->diffInDays(Carbon::parse($cycle->end_date));
        });

        // Predict next period
        $nextPeriod = Carbon::parse($lastCycle->end_date)->addDays(round($avgCycleLength));

        // Calculate fertile window (typically 12-16 days before next period)
        $fertileStart = $nextPeriod->copy()->subDays(16);
        $fertileEnd = $nextPeriod->copy()->subDays(12);

        return response()->json([
            'next_period' => $nextPeriod->format('Y-m-d'),
            'fertile_window' => [
                'start' => $fertileStart->format('Y-m-d'),
                'end' => $fertileEnd->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Log period symptoms
     */
    public function logSymptoms(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'symptoms' => 'required|array',
            'symptoms.*' => 'string',
        ]);

        $symptoms = collect($request->symptoms)->map(function($symptom) use ($request) {
            return PeriodSymptom::create([
                'user_id' => auth()->id(),
                'date' => $request->date,
                'symptom' => $symptom,
            ]);
        });

        return response()->json(['symptoms' => $symptoms], 201);
    }

    /**
     * Get logged symptoms for a date range
     */
    public function getSymptoms(Request $request)
    {
        $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        $query = PeriodSymptom::where('user_id', auth()->id());

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $symptoms = $query->orderBy('date', 'desc')->get();

        return response()->json(['symptoms' => $symptoms]);
    }
}