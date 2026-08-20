<?php

namespace Tests\Feature;

use App\Models\QualityEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_quality_evaluation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/quality-evaluations', [
            'title' => 'Test Quality Evaluation',
            'cleanliness_floors_walls_achieved' => 25,
            'cleanliness_floors_walls_max' => 30,
            'cleanliness_tools_achieved' => 28,
            'cleanliness_tools_max' => 30,
            'expiration_dates_achieved' => 35,
            'expiration_dates_max' => 40,
            'comments' => 'Test evaluation comments',
            'status' => 'completed'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quality_evaluations', [
            'user_id' => $user->id,
            'title' => 'Test Quality Evaluation',
            'cleanliness_floors_walls_achieved' => 25,
            'cleanliness_tools_achieved' => 28,
            'expiration_dates_achieved' => 35,
            'status' => 'completed'
        ]);
    }

    public function test_quality_evaluation_calculates_total_score_correctly(): void
    {
        $user = User::factory()->create();

        $evaluation = QualityEvaluation::create([
            'user_id' => $user->id,
            'title' => 'Test Evaluation',
            'cleanliness_floors_walls_achieved' => 25,
            'cleanliness_floors_walls_max' => 30,
            'cleanliness_floors_walls_weight' => 30.00,
            'cleanliness_tools_achieved' => 28,
            'cleanliness_tools_max' => 30,
            'cleanliness_tools_weight' => 30.00,
            'expiration_dates_achieved' => 35,
            'expiration_dates_max' => 40,
            'expiration_dates_weight' => 40.00,
            'status' => 'completed'
        ]);

        // Expected calculation:
        // Floors/walls: (25/30) * 100 * 30/100 = 25.00
        // Tools: (28/30) * 100 * 30/100 = 28.00
        // Expiration: (35/40) * 100 * 40/100 = 35.00
        // Total: 88.00

        $this->assertEquals(88.00, $evaluation->calculateTotalScore());
    }

    public function test_user_can_view_their_evaluations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create evaluation for the user
        QualityEvaluation::factory()->create(['user_id' => $user->id]);

        // Create evaluation for another user
        QualityEvaluation::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get('/quality-evaluations');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('QualityEvaluation/Index')
                 ->has('evaluations.data', 1) // Should only see their own evaluation
        );
    }

    public function test_user_cannot_view_other_users_evaluation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $evaluation = QualityEvaluation::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/quality-evaluations/{$evaluation->id}");

        $response->assertStatus(403);
    }

    public function test_evaluation_validation_prevents_achieved_exceeding_max(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/quality-evaluations', [
            'title' => 'Test Quality Evaluation',
            'cleanliness_floors_walls_achieved' => 35, // Exceeds max of 30
            'cleanliness_floors_walls_max' => 30,
            'cleanliness_tools_achieved' => 28,
            'cleanliness_tools_max' => 30,
            'expiration_dates_achieved' => 35,
            'expiration_dates_max' => 40,
            'status' => 'draft'
        ]);

        $response->assertSessionHasErrors('cleanliness_floors_walls_achieved');
    }
}
