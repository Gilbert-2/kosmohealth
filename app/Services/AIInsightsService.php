<?php

namespace App\Services;

use App\Models\User;
use App\Models\PeriodCycle;
use App\Models\PeriodSymptom;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AI Insights Service
 * 
 * Provides AI-powered health insights with privacy-first approach,
 * ethical AI practices, and evidence-based recommendations.
 */
class AIInsightsService
{
    private const MODEL_VERSION = '2024.1.0';
    private const CONFIDENCE_THRESHOLD = 0.7;

    /**
     * Generate personalized AI recommendations
     * 
     * @param User $user
     * @param array $options
     * @return array
     */
    public function generatePersonalizedRecommendations(User $user, array $options = []): array
    {
        try {
            $cacheKey = "ai_recommendations_{$user->id}";
            
            return Cache::remember($cacheKey, 3600, function () use ($user, $options) { // 1 hour cache
                $cycles = PeriodCycle::where('user_id', $user->id)
                    ->orderBy('start_date', 'desc')
                    ->limit(6)
                    ->get();

                $symptoms = PeriodSymptom::where('user_id', $user->id)
                    ->where('logged_at', '>=', Carbon::now()->subMonths(3))
                    ->get();

                $recommendations = [];
                $confidenceScores = [];
                $personalizationFactors = [];

                // Generate cycle-based recommendations
                if ($cycles->count() >= 3) {
                    $cycleRecommendations = $this->generateCycleRecommendations($cycles);
                    $recommendations = array_merge($recommendations, $cycleRecommendations['recommendations']);
                    $confidenceScores = array_merge($confidenceScores, $cycleRecommendations['confidence']);
                    $personalizationFactors[] = 'cycle_history';
                }

                // Generate symptom-based recommendations
                if ($symptoms->count() > 0) {
                    $symptomRecommendations = $this->generateSymptomRecommendations($symptoms);
                    $recommendations = array_merge($recommendations, $symptomRecommendations['recommendations']);
                    $confidenceScores = array_merge($confidenceScores, $symptomRecommendations['confidence']);
                    $personalizationFactors[] = 'symptom_patterns';
                }

                // Generate lifestyle recommendations
                if ($options['include_lifestyle_tips'] ?? true) {
                    $lifestyleRecommendations = $this->generateLifestyleRecommendations($user, $cycles, $symptoms);
                    $recommendations = array_merge($recommendations, $lifestyleRecommendations['recommendations']);
                    $confidenceScores = array_merge($confidenceScores, $lifestyleRecommendations['confidence']);
                    $personalizationFactors[] = 'lifestyle_analysis';
                }

                // Filter by confidence threshold
                $filteredRecommendations = [];
                foreach ($recommendations as $index => $recommendation) {
                    if (($confidenceScores[$index] ?? 0) >= self::CONFIDENCE_THRESHOLD) {
                        $filteredRecommendations[] = $recommendation;
                    }
                }

                return [
                    'recommendations' => array_slice($filteredRecommendations, 0, 5), // Top 5 recommendations
                    'confidence' => array_slice($confidenceScores, 0, 5),
                    'factors' => $personalizationFactors,
                    'model_version' => self::MODEL_VERSION,
                    'generated_at' => now()->toISOString(),
                    'privacy_level' => $options['privacy_level'] ?? 'standard'
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error generating AI recommendations', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'recommendations' => $this->getFallbackRecommendations(),
                'confidence' => [0.5, 0.5, 0.5],
                'factors' => ['general_guidelines'],
                'model_version' => self::MODEL_VERSION,
                'generated_at' => now()->toISOString(),
                'error' => 'Unable to generate personalized recommendations'
            ];
        }
    }

    /**
     * Generate cycle-based recommendations
     * 
     * @param \Illuminate\Database\Eloquent\Collection $cycles
     * @return array
     */
    private function generateCycleRecommendations($cycles): array
    {
        $recommendations = [];
        $confidence = [];

        // Analyze cycle regularity
        $cycleLengths = [];
        for ($i = 0; $i < $cycles->count() - 1; $i++) {
            $length = Carbon::parse($cycles[$i]->start_date)
                ->diffInDays(Carbon::parse($cycles[$i + 1]->start_date));
            $cycleLengths[] = $length;
        }

        $averageLength = array_sum($cycleLengths) / count($cycleLengths);
        $variance = array_sum(array_map(function($length) use ($averageLength) {
            return pow($length - $averageLength, 2);
        }, $cycleLengths)) / count($cycleLengths);
        $standardDeviation = sqrt($variance);

        // Regularity recommendations
        if ($standardDeviation > 7) {
            $recommendations[] = [
                'title' => 'Improve Cycle Regularity',
                'description' => 'Your cycles show some irregularity. Consider stress management, regular sleep, and balanced nutrition.',
                'category' => 'cycle_health',
                'priority' => 'medium',
                'emoji' => '⚖️',
                'actionable_steps' => [
                    'Maintain consistent sleep schedule',
                    'Practice stress reduction techniques',
                    'Consider tracking additional factors like stress and exercise'
                ]
            ];
            $confidence[] = 0.8;
        }

        // Cycle length recommendations
        if ($averageLength < 21) {
            $recommendations[] = [
                'title' => 'Short Cycle Consideration',
                'description' => 'Your cycles are shorter than average. This might be normal for you, but consider discussing with a healthcare provider.',
                'category' => 'medical_attention',
                'priority' => 'high',
                'emoji' => '🩺',
                'actionable_steps' => [
                    'Schedule a consultation with a gynecologist',
                    'Track symptoms more detailed',
                    'Note any changes in lifestyle or medications'
                ]
            ];
            $confidence[] = 0.9;
        } elseif ($averageLength > 35) {
            $recommendations[] = [
                'title' => 'Long Cycle Management',
                'description' => 'Your cycles are longer than average. Monitor for consistency and consider medical consultation if concerning.',
                'category' => 'monitoring',
                'priority' => 'medium',
                'emoji' => '📊',
                'actionable_steps' => [
                    'Continue tracking consistently',
                    'Monitor for other symptoms',
                    'Consider lifestyle factors affecting hormones'
                ]
            ];
            $confidence[] = 0.85;
        }

        return [
            'recommendations' => $recommendations,
            'confidence' => $confidence
        ];
    }

    /**
     * Generate symptom-based recommendations
     * 
     * @param \Illuminate\Database\Eloquent\Collection $symptoms
     * @return array
     */
    private function generateSymptomRecommendations($symptoms): array
    {
        $recommendations = [];
        $confidence = [];

        $symptomTypes = $symptoms->groupBy('symptom_type');

        foreach ($symptomTypes as $type => $typeSymptoms) {
            $averageSeverity = $typeSymptoms->avg('severity');
            $frequency = $typeSymptoms->count();

            // High severity symptom recommendations
            if ($averageSeverity >= 4) {
                $recommendations[] = [
                    'title' => "Manage {$this->getSymptomDisplayName($type)}",
                    'description' => "You've reported severe {$type}. Consider symptom management strategies and medical consultation.",
                    'category' => 'symptom_management',
                    'priority' => 'high',
                    'emoji' => $this->getSymptomEmoji($type),
                    'actionable_steps' => $this->getSymptomManagementSteps($type)
                ];
                $confidence[] = 0.9;
            }

            // Frequent symptom recommendations
            if ($frequency >= 5) {
                $recommendations[] = [
                    'title' => "Address Recurring {$this->getSymptomDisplayName($type)}",
                    'description' => "You frequently experience {$type}. Consider preventive measures and lifestyle adjustments.",
                    'category' => 'prevention',
                    'priority' => 'medium',
                    'emoji' => '🔄',
                    'actionable_steps' => $this->getPreventiveSteps($type)
                ];
                $confidence[] = 0.8;
            }
        }

        return [
            'recommendations' => $recommendations,
            'confidence' => $confidence
        ];
    }

    /**
     * Generate lifestyle recommendations
     * 
     * @param User $user
     * @param \Illuminate\Database\Eloquent\Collection $cycles
     * @param \Illuminate\Database\Eloquent\Collection $symptoms
     * @return array
     */
    private function generateLifestyleRecommendations($user, $cycles, $symptoms): array
    {
        $recommendations = [];
        $confidence = [];

        // General wellness recommendations
        $recommendations[] = [
            'title' => 'Optimize Your Cycle Health',
            'description' => 'Maintain overall reproductive health through balanced nutrition and regular exercise.',
            'category' => 'wellness',
            'priority' => 'low',
            'emoji' => '🌱',
            'actionable_steps' => [
                'Include iron-rich foods in your diet',
                'Stay hydrated throughout your cycle',
                'Consider gentle exercise during menstruation',
                'Practice stress management techniques'
            ]
        ];
        $confidence[] = 0.75;

        // Nutrition recommendations
        if ($symptoms->where('symptom_type', 'cramps')->count() > 0) {
            $recommendations[] = [
                'title' => 'Anti-Inflammatory Nutrition',
                'description' => 'Incorporate anti-inflammatory foods to help reduce menstrual discomfort.',
                'category' => 'nutrition',
                'priority' => 'medium',
                'emoji' => '🥗',
                'actionable_steps' => [
                    'Include omega-3 rich foods (fish, walnuts, flaxseeds)',
                    'Add turmeric and ginger to your diet',
                    'Reduce processed foods and sugar',
                    'Consider magnesium-rich foods'
                ]
            ];
            $confidence[] = 0.85;
        }

        return [
            'recommendations' => $recommendations,
            'confidence' => $confidence
        ];
    }

    /**
     * Get fallback recommendations when AI generation fails
     * 
     * @return array
     */
    private function getFallbackRecommendations(): array
    {
        return [
            [
                'title' => 'Track Consistently',
                'description' => 'Regular tracking helps identify patterns and improves health insights.',
                'category' => 'tracking',
                'priority' => 'low',
                'emoji' => '📝',
                'actionable_steps' => [
                    'Log your period start and end dates',
                    'Track symptoms with severity levels',
                    'Note any lifestyle factors'
                ]
            ],
            [
                'title' => 'Maintain Healthy Habits',
                'description' => 'Support your reproductive health with balanced lifestyle choices.',
                'category' => 'wellness',
                'priority' => 'low',
                'emoji' => '💪',
                'actionable_steps' => [
                    'Eat a balanced diet rich in nutrients',
                    'Exercise regularly but listen to your body',
                    'Get adequate sleep'
                ]
            ],
            [
                'title' => 'Know When to Seek Help',
                'description' => 'Consult healthcare providers for concerning symptoms or changes.',
                'category' => 'medical_attention',
                'priority' => 'medium',
                'emoji' => '🩺',
                'actionable_steps' => [
                    'Track severe or unusual symptoms',
                    'Schedule regular gynecological checkups',
                    'Don\'t hesitate to ask questions'
                ]
            ]
        ];
    }

    /**
     * Get display name for symptom type
     * 
     * @param string $type
     * @return string
     */
    private function getSymptomDisplayName(string $type): string
    {
        $displayNames = [
            'cramps' => 'Menstrual Cramps',
            'headache' => 'Headaches',
            'bloating' => 'Bloating',
            'mood_changes' => 'Mood Changes',
            'fatigue' => 'Fatigue',
            'breast_tenderness' => 'Breast Tenderness',
            'nausea' => 'Nausea',
            'back_pain' => 'Back Pain'
        ];

        return $displayNames[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get emoji for symptom type
     * 
     * @param string $type
     * @return string
     */
    private function getSymptomEmoji(string $type): string
    {
        $emojis = [
            'cramps' => '😖',
            'headache' => '🤕',
            'bloating' => '😔',
            'mood_changes' => '😢',
            'fatigue' => '😴',
            'breast_tenderness' => '💢',
            'nausea' => '🤢',
            'back_pain' => '🤲'
        ];

        return $emojis[$type] ?? '💊';
    }

    /**
     * Get symptom management steps
     * 
     * @param string $type
     * @return array
     */
    private function getSymptomManagementSteps(string $type): array
    {
        $steps = [
            'cramps' => [
                'Apply heat therapy (heating pad, warm bath)',
                'Consider over-the-counter pain relievers',
                'Practice gentle stretching or yoga',
                'Consult healthcare provider for severe pain'
            ],
            'headache' => [
                'Stay hydrated',
                'Manage stress levels',
                'Consider magnesium supplements (consult doctor)',
                'Track triggers in relation to your cycle'
            ],
            'bloating' => [
                'Reduce sodium intake',
                'Stay hydrated',
                'Eat smaller, frequent meals',
                'Consider probiotics'
            ],
            'mood_changes' => [
                'Practice stress management techniques',
                'Maintain regular exercise routine',
                'Consider mood tracking',
                'Seek support from healthcare provider if severe'
            ]
        ];

        return $steps[$type] ?? [
            'Track symptom patterns',
            'Practice self-care',
            'Consult healthcare provider if severe'
        ];
    }

    /**
     * Get preventive steps for symptoms
     * 
     * @param string $type
     * @return array
     */
    private function getPreventiveSteps(string $type): array
    {
        $steps = [
            'cramps' => [
                'Regular exercise throughout the month',
                'Maintain adequate calcium and magnesium intake',
                'Reduce inflammatory foods',
                'Practice relaxation techniques'
            ],
            'headache' => [
                'Maintain regular sleep schedule',
                'Stay consistently hydrated',
                'Manage stress proactively',
                'Track dietary triggers'
            ],
            'bloating' => [
                'Maintain consistent eating schedule',
                'Include fiber gradually in diet',
                'Stay active with regular movement',
                'Monitor sodium intake'
            ]
        ];

        return $steps[$type] ?? [
            'Maintain healthy lifestyle habits',
            'Track patterns to identify triggers',
            'Practice preventive self-care'
        ];
    }
}