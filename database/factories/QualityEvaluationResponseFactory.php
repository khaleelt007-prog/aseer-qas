<?php

namespace Database\Factories;

use App\Models\QualityEvaluation;
use App\Models\EvaluationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QualityEvaluationResponse>
 */
class QualityEvaluationResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $maxScore = $this->faker->numberBetween(20, 50);
        $achievedScore = $this->faker->numberBetween(
            (int)($maxScore * 0.5), // 50% minimum
            $maxScore
        );

        return [
            'quality_evaluation_id' => QualityEvaluation::factory(),
            'evaluation_item_id' => EvaluationItem::factory(),
            'achieved_score' => $achievedScore,
            'max_score' => $maxScore,
            'weight' => $this->faker->numberBetween(20, 40),
        ];
    }

    /**
     * Indicate that the response is incomplete (no achieved score).
     */
    public function incomplete(): static
    {
        return $this->state(fn () => [
            'achieved_score' => null,
        ]);
    }

    /**
     * Set a specific weight for the response.
     */
    public function withWeight(int $weight): static
    {
        return $this->state(fn () => [
            'weight' => $weight,
            'max_score' => $weight, // Default max score to weight
        ]);
    }
}
