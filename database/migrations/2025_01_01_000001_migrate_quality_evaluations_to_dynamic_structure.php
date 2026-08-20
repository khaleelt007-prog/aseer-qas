<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\QualityEvaluation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if we have the old structure
        if (!Schema::hasColumn('quality_evaluations', 'cleanliness_floors_walls_achieved')) {
            // New installation, no migration needed
            return;
        }

        // Get all existing evaluations before we modify the structure
        $existingEvaluations = DB::table('quality_evaluations')->get();

        // Get evaluation items
        $evaluationItems = DB::table('evaluation_items')->get();

        // Create mapping of old field names to evaluation item IDs
        $fieldMapping = [];
        foreach ($evaluationItems as $item) {
            $identifier = str_replace(' ', '_', strtolower($item->name));
            $fieldMapping[$identifier] = $item->id;
        }

        // Migrate existing data to the new structure
        foreach ($existingEvaluations as $evaluation) {
            // Create responses for each evaluation item
            foreach ($fieldMapping as $identifier => $itemId) {
                $achievedField = $identifier . '_achieved';
                $maxField = $identifier . '_max';
                $weightField = $identifier . '_weight';

                if (property_exists($evaluation, $achievedField)) {
                    DB::table('quality_evaluation_responses')->insert([
                        'quality_evaluation_id' => $evaluation->id,
                        'evaluation_item_id' => $itemId,
                        'achieved_score' => $evaluation->$achievedField,
                        'max_score' => $evaluation->$maxField ?? 30,
                        'weight' => $evaluation->$weightField ?? 30.00,
                        'created_at' => $evaluation->created_at,
                        'updated_at' => $evaluation->updated_at,
                    ]);
                }
            }
        }

        // Remove old columns from quality_evaluations table
        Schema::table('quality_evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'cleanliness_floors_walls_achieved',
                'cleanliness_floors_walls_max',
                'cleanliness_floors_walls_weight',
                'cleanliness_tools_achieved',
                'cleanliness_tools_max',
                'cleanliness_tools_weight',
                'expiration_dates_achieved',
                'expiration_dates_max',
                'expiration_dates_weight',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the old columns
        Schema::table('quality_evaluations', function (Blueprint $table) {
            $table->integer('cleanliness_floors_walls_achieved')->nullable();
            $table->integer('cleanliness_floors_walls_max')->default(30);
            $table->decimal('cleanliness_floors_walls_weight', 5, 2)->default(30.00);
            $table->integer('cleanliness_tools_achieved')->nullable();
            $table->integer('cleanliness_tools_max')->default(30);
            $table->decimal('cleanliness_tools_weight', 5, 2)->default(30.00);
            $table->integer('expiration_dates_achieved')->nullable();
            $table->integer('expiration_dates_max')->default(40);
            $table->decimal('expiration_dates_weight', 5, 2)->default(40.00);
        });

        // Migrate data back from responses to old structure
        $evaluations = QualityEvaluation::with('responses.evaluationItem')->get();
        
        foreach ($evaluations as $evaluation) {
            $updateData = [];
            
            foreach ($evaluation->responses as $response) {
                $identifier = $response->evaluationItem->identifier;
                $updateData[$identifier . '_achieved'] = $response->achieved_score;
                $updateData[$identifier . '_max'] = $response->max_score;
                $updateData[$identifier . '_weight'] = $response->weight;
            }
            
            if (!empty($updateData)) {
                DB::table('quality_evaluations')
                    ->where('id', $evaluation->id)
                    ->update($updateData);
            }
        }
    }
};
