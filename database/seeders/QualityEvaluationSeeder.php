<?php

namespace Database\Seeders;

use App\Models\QualityEvaluation;
use App\Models\User;
use Illuminate\Database\Seeder;

class QualityEvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the test user or create one
        $user = User::where('username', 'testuser')->first();

        if (!$user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'username' => 'testuser',
                'email' => 'test@example.com',
            ]);
        }

        // Get evaluation items
        $evaluationItems = EvaluationItem::all();

        // Create sample quality evaluations with dynamic responses
        $evaluations = [
            [
                'title' => 'Kitchen Quality Assessment - Morning Shift',
                'comments' => 'Overall excellent performance. Minor issues with tool cleaning in prep area. All expiration dates properly checked and documented.',
                'status' => 'completed',
                'completed_at' => now()->subDays(1),
                'responses' => [
                    'cleanliness_of_floors_and_walls' => ['achieved' => 28, 'max' => 30],
                    'cleanliness_of_the_tools_used' => ['achieved' => 25, 'max' => 30],
                    'adherence_to_approved_expiration_dates' => ['achieved' => 38, 'max' => 40],
                ],
            ],
            [
                'title' => 'Restaurant Floor Quality Check',
                'comments' => 'Floor cleaning needs improvement, especially around high-traffic areas. Tool maintenance is exemplary.',
                'status' => 'completed',
                'completed_at' => now()->subDays(3),
                'responses' => [
                    'cleanliness_of_floors_and_walls' => ['achieved' => 22, 'max' => 30],
                    'cleanliness_of_the_tools_used' => ['achieved' => 30, 'max' => 30],
                    'adherence_to_approved_expiration_dates' => ['achieved' => 35, 'max' => 40],
                ],
            ],
            [
                'title' => 'Weekly Quality Audit - Storage Areas',
                'comments' => 'Perfect compliance in storage areas. All items properly labeled and within expiration dates.',
                'status' => 'completed',
                'completed_at' => now()->subDays(7),
                'responses' => [
                    'cleanliness_of_floors_and_walls' => ['achieved' => 30, 'max' => 30],
                    'cleanliness_of_the_tools_used' => ['achieved' => 28, 'max' => 30],
                    'adherence_to_approved_expiration_dates' => ['achieved' => 40, 'max' => 40],
                ],
            ],
            [
                'title' => 'Evening Shift Quality Review',
                'comments' => 'Several areas need attention. Recommend additional training for evening staff.',
                'status' => 'completed',
                'completed_at' => now()->subDays(2),
                'responses' => [
                    'cleanliness_of_floors_and_walls' => ['achieved' => 20, 'max' => 30],
                    'cleanliness_of_the_tools_used' => ['achieved' => 22, 'max' => 30],
                    'adherence_to_approved_expiration_dates' => ['achieved' => 30, 'max' => 40],
                ],
            ],
            [
                'title' => 'Monthly Comprehensive Evaluation',
                'comments' => 'Evaluation in progress. Initial floor assessment completed.',
                'status' => 'draft',
                'completed_at' => null,
                'responses' => [
                    'cleanliness_of_floors_and_walls' => ['achieved' => 25, 'max' => 30],
                    'cleanliness_of_the_tools_used' => ['achieved' => null, 'max' => 30],
                    'adherence_to_approved_expiration_dates' => ['achieved' => null, 'max' => 40],
                ],
            ],
        ];

        foreach ($evaluations as $evaluationData) {
            $responses = $evaluationData['responses'];
            unset($evaluationData['responses']);

            $evaluation = QualityEvaluation::create(array_merge($evaluationData, [
                'user_id' => $user->id,
            ]));

            // Create responses for each evaluation item
            foreach ($evaluationItems as $item) {
                $identifier = $item->identifier;
                $responseData = $responses[$identifier] ?? null;

                if ($responseData) {
                    $evaluation->responses()->create([
                        'evaluation_item_id' => $item->id,
                        'achieved_score' => $responseData['achieved'],
                        'max_score' => $responseData['max'],
                        'weight' => $item->weight,
                    ]);
                }
            }

            // Calculate and save total score for completed evaluations
            if ($evaluation->status === 'completed') {
                $evaluation->load('responses');
                $evaluation->total_score = $evaluation->calculateTotalScore();
                $evaluation->save();
            }
        }
    }
}
