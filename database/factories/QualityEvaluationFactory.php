<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QualityEvaluation>
 */
class QualityEvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'comments' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement(['draft', 'completed']),
            'completed_at' => function (array $attributes) {
                return $attributes['status'] === 'completed' ? $this->faker->dateTimeBetween('-1 month', 'now') : null;
            },
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (QualityEvaluation $evaluation) {
            // Get evaluation items and create responses
            $evaluationItems = \App\Models\EvaluationItem::all();

            foreach ($evaluationItems as $item) {
                $maxScore = $item->weight;
                $achievedScore = $this->faker->numberBetween(
                    (int)($maxScore * 0.5), // 50% minimum
                    $maxScore
                );

                $evaluation->responses()->create([
                    'evaluation_item_id' => $item->id,
                    'achieved_score' => $evaluation->status === 'draft' && $this->faker->boolean(30) ? null : $achievedScore,
                    'max_score' => $maxScore,
                    'weight' => $item->weight,
                ]);
            }

            // Calculate total score if completed
            if ($evaluation->status === 'completed') {
                $evaluation->total_score = $evaluation->calculateTotalScore();
                $evaluation->save();
            }
        });
    }

    /**
     * Indicate that the evaluation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the evaluation is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'completed_at' => null,
        ]);
    }
}
