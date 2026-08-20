<?php

namespace Tests\Feature;

use App\Models\QcAnswer;
use App\Models\QcAnswerFollowUp;
use App\Models\QcQuestion;
use App\Models\QcSection;
use App\Models\QcTemplate;
use App\Models\QualityEvaluation;
use App\Models\QualityEvaluationPhoto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QualityEvaluationFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createFollowUpTestTables();
    }

    public function test_follow_up_index_only_lists_completed_checklists_with_bad_answers(): void
    {
        $user = User::factory()->create();
        [$branch, $template, $_section, $badQuestion, $commentQuestion] = $this->createChecklistFixtures();

        $eligible = $this->createEvaluation($user, $branch->id, $template->id);
        QcAnswer::create(['quality_evaluation_id' => $eligible->id, 'question_id' => $badQuestion->id, 'answer_value' => '0']);
        QcAnswer::create(['quality_evaluation_id' => $eligible->id, 'question_id' => $commentQuestion->id, 'answer_value' => 'Needs action']);

        $draft = $this->createEvaluation($user, $branch->id, $template->id, ['status' => 'draft']);
        QcAnswer::create(['quality_evaluation_id' => $draft->id, 'question_id' => $badQuestion->id, 'answer_value' => '0']);

        $good = $this->createEvaluation($user, $branch->id, $template->id);
        QcAnswer::create(['quality_evaluation_id' => $good->id, 'question_id' => $badQuestion->id, 'answer_value' => '1']);

        $response = $this->withoutMiddleware()->actingAs($user)->get(route('quality-evaluation-follow-ups.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('QualityEvaluationFollowUp/Index')
            ->has('evaluations.data', 1)
            ->where('evaluations.data.0.id', $eligible->id)
            ->where('evaluations.data.0.bad_answers_count', 1)
        );
    }

    public function test_follow_up_show_only_returns_bad_questions_with_section_context(): void
    {
        $user = User::factory()->create();
        [$branch, $template, $section, $badQuestion, $commentQuestion] = $this->createChecklistFixtures();
        $goodQuestion = QcQuestion::create(['section_id' => $section->id, 'q_type' => QcQuestion::TYPE_POINT_BASED, 'name' => 'Good', 'name_ar' => 'Good', 'sort_order' => 3, 'is_required' => true]);

        $evaluation = $this->createEvaluation($user, $branch->id, $template->id, ['warning_flag' => true]);
        $badAnswer = QcAnswer::create(['quality_evaluation_id' => $evaluation->id, 'question_id' => $badQuestion->id, 'answer_value' => '0.5']);
        QcAnswer::create(['quality_evaluation_id' => $evaluation->id, 'question_id' => $goodQuestion->id, 'answer_value' => '1']);
        QcAnswer::create(['quality_evaluation_id' => $evaluation->id, 'question_id' => $commentQuestion->id, 'answer_value' => 'Section note']);

        $followUp = QcAnswerFollowUp::create([
            'qc_answer_id' => $badAnswer->id,
            'quality_evaluation_id' => $evaluation->id,
            'question_id' => $badQuestion->id,
            'section_id' => $section->id,
            'expected_deadline' => now()->addDays(2)->toDateString(),
            'status' => QcAnswerFollowUp::STATUS_OPEN,
            'created_by' => $user->id,
        ]);
        $followUp->comments()->create([
            'comment_type' => 'qc_comment',
            'comment_date' => now(),
            'comment_text' => 'Reminder sent',
            'created_by' => $user->id,
        ]);
        QualityEvaluationPhoto::create([
            'quality_evaluation_id' => $evaluation->id,
            'section_id' => $section->id,
            'filename' => 'photo.jpg',
            'original_filename' => 'photo.jpg',
            'file_path' => 'quality-evaluations/photo.jpg',
            'file_size' => 2048,
            'mime_type' => 'image/jpeg',
            'uploaded_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->actingAs($user)->get(route('quality-evaluation-follow-ups.show', $evaluation));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('QualityEvaluationFollowUp/Show')
            ->where('evaluation.id', $evaluation->id)
            ->where('followUpData.sections.0.bad_questions.0.answer_id', $badAnswer->id)
            ->where('followUpData.sections.0.bad_questions.0.follow_up.comments.0.comment_text', 'Reminder sent')
            ->where('followUpData.sections.0.comment_questions.0.answer_value', 'Section note')
            ->has('followUpData.sections.0.photos', 1)
        );
    }

    public function test_follow_up_actions_create_records_and_mark_issue_solved(): void
    {
        $user = User::factory()->create();
        [$branch, $template, $_section, $badQuestion] = $this->createChecklistFixtures();
        $evaluation = $this->createEvaluation($user, $branch->id, $template->id);
        $answer = QcAnswer::create(['quality_evaluation_id' => $evaluation->id, 'question_id' => $badQuestion->id, 'answer_value' => '0']);

        $this->withoutMiddleware()->actingAs($user)->post(route('quality-evaluation-follow-ups.answers.deadline', [$evaluation, $answer]), [
            'expected_deadline' => now()->addDays(3)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('qc_answer_follow_ups', [
            'qc_answer_id' => $answer->id,
            'status' => QcAnswerFollowUp::STATUS_OPEN,
        ]);

        $this->withoutMiddleware()->actingAs($user)->post(route('quality-evaluation-follow-ups.answers.comments.store', [$evaluation, $answer]), [
            'comment_type' => 'branch_reply',
            'comment_text' => 'Issue is under review',
        ])->assertRedirect();

        $this->assertDatabaseHas('qc_answer_follow_up_comments', [
            'comment_type' => 'branch_reply',
            'comment_text' => 'Issue is under review',
        ]);

        $this->withoutMiddleware()->actingAs($user)->post(route('quality-evaluation-follow-ups.answers.mark-solved', [$evaluation, $answer]))
            ->assertRedirect();

        $this->assertDatabaseHas('qc_answer_follow_ups', [
            'qc_answer_id' => $answer->id,
            'status' => QcAnswerFollowUp::STATUS_SOLVED,
        ]);
        $this->assertNotNull(QcAnswerFollowUp::where('qc_answer_id', $answer->id)->value('solved_at'));
    }

    public function test_warning_processing_command_flags_and_clears_evaluations(): void
    {
        $user = User::factory()->create();
        [$branch, $template, $section, $badQuestion] = $this->createChecklistFixtures();
        $evaluation = $this->createEvaluation($user, $branch->id, $template->id);
        $answer = QcAnswer::create(['quality_evaluation_id' => $evaluation->id, 'question_id' => $badQuestion->id, 'answer_value' => '0']);

        QcAnswerFollowUp::create([
            'qc_answer_id' => $answer->id,
            'quality_evaluation_id' => $evaluation->id,
            'question_id' => $badQuestion->id,
            'section_id' => $section->id,
            'expected_deadline' => now()->subDay()->toDateString(),
            'status' => QcAnswerFollowUp::STATUS_OPEN,
            'created_by' => $user->id,
        ]);

        $this->artisan('quality-evaluations:process-warnings')->assertExitCode(0);
        $this->assertTrue($evaluation->fresh()->warning_flag);

        QcAnswerFollowUp::query()->update(['status' => QcAnswerFollowUp::STATUS_SOLVED, 'solved_at' => now()]);

        $this->artisan('quality-evaluations:process-warnings')->assertExitCode(0);
        $this->assertFalse($evaluation->fresh()->warning_flag);
    }

    private function createChecklistFixtures(): array
    {
        $brandId = Schema::getConnection()->table('sma_brands')->insertGetId(['name' => 'Brand', 'name_ar' => 'Brand']);
        $countryId = Schema::getConnection()->table('sma_countries')->insertGetId(['name' => 'Country', 'name_ar' => 'Country']);
        $branchId = Schema::getConnection()->table('sma_branches')->insertGetId(['name' => 'Branch', 'name_ar' => 'Branch', 'country_id' => $countryId, 'brand_id' => $brandId, 'is_catering' => 0]);

        $template = QcTemplate::create(['brand_id' => $brandId, 'name_en' => 'Template', 'name_ar' => 'Template', 'is_active' => true, 'answer_type' => 'Points']);
        $section = QcSection::create(['template_id' => $template->id, 'name' => 'Section A', 'name_ar' => 'Section A', 'sort_order' => 1]);
        $badQuestion = QcQuestion::create(['section_id' => $section->id, 'q_type' => QcQuestion::TYPE_POINT_BASED, 'name' => 'Bad Question', 'name_ar' => 'Bad Question', 'sort_order' => 1, 'is_required' => true]);
        $commentQuestion = QcQuestion::create(['section_id' => $section->id, 'q_type' => QcQuestion::TYPE_TEXT, 'name' => 'Comment', 'name_ar' => 'Comment', 'sort_order' => 2, 'is_required' => false]);

        return [(object) ['id' => $branchId], $template, $section, $badQuestion, $commentQuestion];
    }

    private function createEvaluation(User $user, int $branchId, int $templateId, array $overrides = []): QualityEvaluation
    {
        return QualityEvaluation::create(array_merge([
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'title' => 'Checklist Evaluation',
            'type' => 'checklist',
            'status' => 'completed',
            'total_score' => 3.5,
            'max_score' => 5,
            'template_id' => $templateId,
            'completed_at' => now(),
            'comments' => 'Test',
            'warning_flag' => false,
        ], $overrides));
    }

    private function createFollowUpTestTables(): void
    {
        Schema::create('sma_brands', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_ar')->nullable();
        });

        Schema::create('sma_countries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_ar')->nullable();
        });

        Schema::create('sma_branches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->unsignedInteger('country_id')->nullable();
            $table->unsignedInteger('brand_id')->nullable();
            $table->boolean('is_catering')->default(false);
        });

        Schema::create('quality_evaluations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('title');
            $table->string('type')->default('manual');
            $table->string('status')->default('draft');
            $table->decimal('total_score', 8, 2)->nullable();
            $table->integer('max_score')->nullable();
            $table->unsignedInteger('template_id')->nullable();
            $table->text('comments')->nullable();
            $table->decimal('extra_points', 8, 2)->default(0);
            $table->string('pdf_filename')->nullable();
            $table->boolean('warning_flag')->default(false);
            $table->timestamp('warning_flagged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('brand_id')->nullable();
            $table->string('name_en');
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->string('answer_type')->default('Points');
            $table->timestamps();
        });

        Schema::create('qc_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('template_id');
            $table->string('name');
            $table->string('name_ar');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('qc_questions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('section_id');
            $table->unsignedTinyInteger('q_type');
            $table->string('name');
            $table->string('name_ar');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('qc_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('quality_evaluation_id');
            $table->unsignedInteger('question_id');
            $table->text('answer_value')->nullable();
            $table->decimal('achieved_score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('quality_evaluation_photos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('quality_evaluation_id');
            $table->unsignedInteger('section_id')->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_answer_follow_ups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('qc_answer_id');
            $table->unsignedInteger('quality_evaluation_id');
            $table->unsignedInteger('question_id');
            $table->unsignedInteger('section_id');
            $table->date('expected_deadline')->nullable();
            $table->string('status')->default('open');
            $table->timestamp('solved_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('last_commented_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_answer_follow_up_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('follow_up_id');
            $table->string('comment_type');
            $table->timestamp('comment_date');
            $table->text('comment_text');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }
}