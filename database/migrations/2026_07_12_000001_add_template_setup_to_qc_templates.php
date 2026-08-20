<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('qc_templates')) {
            return;
        }

        if (Schema::hasTable('sma_countries') && !Schema::hasTable('qc_template_country')) {
            Schema::create('qc_template_country', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('template_id');
                $table->unsignedInteger('country_id');
                $table->timestamps();

                $table->foreign('template_id')->references('id')->on('qc_templates')->cascadeOnDelete();
                $table->foreign('country_id')->references('id')->on('sma_countries')->cascadeOnDelete();
                $table->unique(['template_id', 'country_id']);
            });
        }

        if (Schema::hasTable('sma_brands') && !Schema::hasTable('qc_template_brand')) {
            Schema::create('qc_template_brand', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('template_id');
                $table->unsignedInteger('brand_id');
                $table->timestamps();

                $table->foreign('template_id')->references('id')->on('qc_templates')->cascadeOnDelete();
                $table->foreign('brand_id')->references('id')->on('sma_brands')->cascadeOnDelete();
                $table->unique(['template_id', 'brand_id']);
            });
        }

        if (Schema::hasTable('qc_questions')) {
            Schema::table('qc_questions', function (Blueprint $table) {
                if (!Schema::hasColumn('qc_questions', 'question_type')) {
                    $table->string('question_type', 50)->nullable()->after('q_type')
                        ->comment('yes_no, multi_select, score; NULL keeps legacy q_type behavior');
                }

                if (!Schema::hasColumn('qc_questions', 'options')) {
                    $table->json('options')->nullable()->after('name_ar')
                        ->comment('Options for multi-select questions');
                }

                if (!Schema::hasColumn('qc_questions', 'score_value')) {
                    $table->decimal('score_value', 8, 2)->nullable()->after('options')
                        ->comment('Maximum/manual score for score questions; yes/no awards this value for Yes');
                }

                if (!Schema::hasColumn('qc_questions', 'allow_manual_score')) {
                    $table->boolean('allow_manual_score')->default(false)->after('score_value')
                        ->comment('Shows a manual score input when enabled for score questions');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('qc_questions')) {
            Schema::table('qc_questions', function (Blueprint $table) {
                foreach (['allow_manual_score', 'score_value', 'options', 'question_type'] as $column) {
                    if (Schema::hasColumn('qc_questions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('qc_template_brand');
        Schema::dropIfExists('qc_template_country');
    }
};