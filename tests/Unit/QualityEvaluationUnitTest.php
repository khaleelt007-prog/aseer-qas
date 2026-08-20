<?php

namespace Tests\Unit;

use App\Models\QualityEvaluation;
use App\Models\QcAnswer;
use App\Models\QcQuestion;
use PHPUnit\Framework\TestCase;

class QualityEvaluationUnitTest extends TestCase
{
    /**
     * Test that the total score calculation is correct.
     */
    public function test_calculate_total_score(): void
    {
        $evaluation = new QualityEvaluation([
            'cleanliness_floors_walls_achieved' => 25,
            'cleanliness_floors_walls_max' => 30,
            'cleanliness_floors_walls_weight' => 30.00,
            'cleanliness_tools_achieved' => 28,
            'cleanliness_tools_max' => 30,
            'cleanliness_tools_weight' => 30.00,
            'expiration_dates_achieved' => 35,
            'expiration_dates_max' => 40,
            'expiration_dates_weight' => 40.00,
        ]);

        // Expected calculation:
        // Floors/walls: (25/30) * 100 * 30/100 = 25.00
        // Tools: (28/30) * 100 * 30/100 = 28.00
        // Expiration: (35/40) * 100 * 40/100 = 35.00
        // Total: 88.00

        $this->assertEquals(88.00, $evaluation->calculateTotalScore());
    }

    /**
     * Test that the evaluation items are returned correctly.
     */
    public function test_get_evaluation_items(): void
    {
        $evaluation = new QualityEvaluation([
            'cleanliness_floors_walls_achieved' => 25,
            'cleanliness_floors_walls_max' => 30,
            'cleanliness_floors_walls_weight' => 30.00,
            'cleanliness_tools_achieved' => 28,
            'cleanliness_tools_max' => 30,
            'cleanliness_tools_weight' => 30.00,
            'expiration_dates_achieved' => 35,
            'expiration_dates_max' => 40,
            'expiration_dates_weight' => 40.00,
        ]);

        $items = $evaluation->getEvaluationItems();

        $this->assertCount(3, $items);
        $this->assertEquals('cleanliness_floors_walls', $items[0]['id']);
        $this->assertEquals('Cleanliness of floors and walls', $items[0]['title']);
        $this->assertEquals(25, $items[0]['achieved']);
        $this->assertEquals(30, $items[0]['max']);
        $this->assertEquals(30.00, $items[0]['weight']);
    }

    /**
     * Test that isComplete returns true when all items are scored.
     */
    public function test_is_complete_when_all_items_scored(): void
    {
        $evaluation = new QualityEvaluation([
            'cleanliness_floors_walls_achieved' => 25,
            'cleanliness_tools_achieved' => 28,
            'expiration_dates_achieved' => 35,
        ]);

        $this->assertTrue($evaluation->isComplete());
    }

    /**
     * Test that isComplete returns false when some items are missing.
     */
    public function test_is_complete_when_items_missing(): void
    {
        $evaluation = new QualityEvaluation([
            'cleanliness_floors_walls_achieved' => 25,
            'cleanliness_tools_achieved' => null,
            'expiration_dates_achieved' => 35,
        ]);

        $this->assertFalse($evaluation->isComplete());
    }

    /**
     * Test calculation with zero achieved scores.
     */
    public function test_calculate_total_score_with_zeros(): void
    {
        $evaluation = new QualityEvaluation([
            'cleanliness_floors_walls_achieved' => 0,
            'cleanliness_floors_walls_max' => 30,
            'cleanliness_floors_walls_weight' => 30.00,
            'cleanliness_tools_achieved' => 0,
            'cleanliness_tools_max' => 30,
            'cleanliness_tools_weight' => 30.00,
            'expiration_dates_achieved' => 0,
            'expiration_dates_max' => 40,
            'expiration_dates_weight' => 40.00,
        ]);

        $this->assertEquals(0.00, $evaluation->calculateTotalScore());
    }

    /**
     * Test calculation with perfect scores.
     */
    public function test_calculate_total_score_perfect(): void
    {
        $evaluation = new QualityEvaluation([
            'cleanliness_floors_walls_achieved' => 30,
            'cleanliness_floors_walls_max' => 30,
            'cleanliness_floors_walls_weight' => 30.00,
            'cleanliness_tools_achieved' => 30,
            'cleanliness_tools_max' => 30,
            'cleanliness_tools_weight' => 30.00,
            'expiration_dates_achieved' => 40,
            'expiration_dates_max' => 40,
            'expiration_dates_weight' => 40.00,
        ]);

        $this->assertEquals(100.00, $evaluation->calculateTotalScore());
    }

    /**
     * Test overwrite_total_score_if_zero behavior: when an item with this flag scores 0,
     * the total should be calculated excluding that item and then 60 points subtracted.
     */
    public function test_calculate_total_score_with_zero_override(): void
    {
        // Create a mock evaluation with responses
        $evaluation = new QualityEvaluation();
        $evaluation->id = 1;

        // Create mock evaluation items
        $item1 = new \App\Models\EvaluationItem([
            'id' => 1,
            'name' => 'Item 1',
            'weight' => 30,
            'overwrite_total_score_if_zero' => false,
            'exclude_from_score' => false,
        ]);

        $item2 = new \App\Models\EvaluationItem([
            'id' => 2,
            'name' => 'Item 2',
            'weight' => 30,
            'overwrite_total_score_if_zero' => true, // This item has zero override
            'exclude_from_score' => false,
        ]);

        $item3 = new \App\Models\EvaluationItem([
            'id' => 3,
            'name' => 'Item 3',
            'weight' => 40,
            'overwrite_total_score_if_zero' => false,
            'exclude_from_score' => false,
        ]);

        // Create responses
        $response1 = new \App\Models\QualityEvaluationResponse([
            'id' => 1,
            'quality_evaluation_id' => 1,
            'evaluation_item_id' => 1,
            'achieved_score' => 25,
            'max_score' => 30,
            'weight' => 30,
        ]);
        $response1->setRelation('evaluationItem', $item1);

        $response2 = new \App\Models\QualityEvaluationResponse([
            'id' => 2,
            'quality_evaluation_id' => 1,
            'evaluation_item_id' => 2,
            'achieved_score' => 0, // Zero score with override flag
            'max_score' => 30,
            'weight' => 30,
        ]);
        $response2->setRelation('evaluationItem', $item2);

        $response3 = new \App\Models\QualityEvaluationResponse([
            'id' => 3,
            'quality_evaluation_id' => 1,
            'evaluation_item_id' => 3,
            'achieved_score' => 35,
            'max_score' => 40,
            'weight' => 40,
        ]);
        $response3->setRelation('evaluationItem', $item3);

        $evaluation->setRelation('responses', collect([$response1, $response2, $response3]));

        // Expected calculation:
        // Item 1: (25/30) * 100 * 30/100 = 25.00
        // Item 2: EXCLUDED (has zero override and score is 0)
        // Item 3: (35/40) * 100 * 40/100 = 35.00
        // Subtotal: 60.00
        // After -60 penalty: 0.00

        $this->assertEquals(0.00, $evaluation->calculateTotalScore());
    }

    /**
     * Test overwrite_total_score_if_zero with higher scores that result in positive total after penalty.
     */
    public function test_calculate_total_score_with_zero_override_positive_result(): void
    {
        $evaluation = new QualityEvaluation();
        $evaluation->id = 1;

        $item1 = new \App\Models\EvaluationItem([
            'id' => 1,
            'name' => 'Item 1',
            'weight' => 30,
            'overwrite_total_score_if_zero' => false,
            'exclude_from_score' => false,
        ]);

        $item2 = new \App\Models\EvaluationItem([
            'id' => 2,
            'name' => 'Item 2',
            'weight' => 30,
            'overwrite_total_score_if_zero' => true,
            'exclude_from_score' => false,
        ]);

        $item3 = new \App\Models\EvaluationItem([
            'id' => 3,
            'name' => 'Item 3',
            'weight' => 40,
            'overwrite_total_score_if_zero' => false,
            'exclude_from_score' => false,
        ]);

        $response1 = new \App\Models\QualityEvaluationResponse([
            'id' => 1,
            'quality_evaluation_id' => 1,
            'evaluation_item_id' => 1,
            'achieved_score' => 30,
            'max_score' => 30,
            'weight' => 30,
        ]);
        $response1->setRelation('evaluationItem', $item1);

        $response2 = new \App\Models\QualityEvaluationResponse([
            'id' => 2,
            'quality_evaluation_id' => 1,
            'evaluation_item_id' => 2,
            'achieved_score' => 0,
            'max_score' => 30,
            'weight' => 30,
        ]);
        $response2->setRelation('evaluationItem', $item2);

        $response3 = new \App\Models\QualityEvaluationResponse([
            'id' => 3,
            'quality_evaluation_id' => 1,
            'evaluation_item_id' => 3,
            'achieved_score' => 40,
            'max_score' => 40,
            'weight' => 40,
        ]);
        $response3->setRelation('evaluationItem', $item3);

        $evaluation->setRelation('responses', collect([$response1, $response2, $response3]));

        // Expected calculation:
        // Item 1: (30/30) * 100 * 30/100 = 30.00
        // Item 2: EXCLUDED (has zero override and score is 0)
        // Item 3: (40/40) * 100 * 40/100 = 40.00
        // Subtotal: 70.00
        // After -60 penalty: 10.00

        $this->assertEquals(10.00, $evaluation->calculateTotalScore());
    }

    public function test_calculate_checklist_score_with_template_setup_question_types(): void
    {
        $pointsQuestion = new QcQuestion([
            'question_type' => QcQuestion::QUESTION_TYPE_POINTS,
            'q_type' => QcQuestion::TYPE_POINT_BASED,
        ]);

        $yesNoQuestion = new QcQuestion([
            'question_type' => QcQuestion::QUESTION_TYPE_YES_NO,
            'q_type' => QcQuestion::TYPE_POINT_BASED,
            'score_value' => 2,
        ]);

        $scoreQuestion = new QcQuestion([
            'question_type' => QcQuestion::QUESTION_TYPE_SCORE,
            'q_type' => QcQuestion::TYPE_POINT_BASED,
            'score_value' => 5,
        ]);

        $multiSelectQuestion = new QcQuestion([
            'question_type' => QcQuestion::QUESTION_TYPE_MULTI_SELECT,
            'q_type' => QcQuestion::TYPE_TEXT,
        ]);

        $commentQuestion = new QcQuestion([
            'question_type' => QcQuestion::QUESTION_TYPE_COMMENT,
            'q_type' => QcQuestion::TYPE_TEXT,
        ]);

        $pointsAnswer = new QcAnswer(['answer_value' => '0.5']);
        $pointsAnswer->setRelation('question', $pointsQuestion);

        $yesAnswer = new QcAnswer(['answer_value' => '1']);
        $yesAnswer->setRelation('question', $yesNoQuestion);

        $scoreAnswer = new QcAnswer(['answer_value' => '3.5']);
        $scoreAnswer->setRelation('question', $scoreQuestion);

        $multiAnswer = new QcAnswer(['answer_value' => '["Option A","Option B"]']);
        $multiAnswer->setRelation('question', $multiSelectQuestion);

        $commentAnswer = new QcAnswer(['answer_value' => 'Section note']);
        $commentAnswer->setRelation('question', $commentQuestion);

        $evaluation = new QualityEvaluation();
        $evaluation->setRelation('answers', collect([$pointsAnswer, $yesAnswer, $scoreAnswer, $multiAnswer, $commentAnswer]));
        $evaluation->setRelation('template', null);

        $this->assertSame([
            'numerator' => 6.0,
            'denominator' => 8.0,
        ], $evaluation->calculateChecklistScore());
    }
}
