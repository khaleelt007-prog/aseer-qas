<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EvaluationItem>
 */
class EvaluationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $evaluationCriteria = [
            ['name' => 'Cleanliness of floors and walls', 'name_ar' => 'نظافة الأرضيات والجدران'],
            ['name' => 'Cleanliness of the tools used', 'name_ar' => 'نظافة الأدوات المستخدمة'],
            ['name' => 'Adherence to approved expiration dates', 'name_ar' => 'الالتزام بتواريخ انتهاء الصلاحية المعتمدة'],
            ['name' => 'Food storage temperature compliance', 'name_ar' => 'الامتثال لدرجة حرارة تخزين الطعام'],
            ['name' => 'Personal hygiene standards', 'name_ar' => 'معايير النظافة الشخصية'],
            ['name' => 'Documentation and record keeping', 'name_ar' => 'التوثيق وحفظ السجلات'],
        ];

        $criteria = $this->faker->randomElement($evaluationCriteria);

        return [
            'name' => $criteria['name'],
            'name_ar' => $criteria['name_ar'],
            'weight' => $this->faker->randomElement([20, 25, 30, 35, 40]),
        ];
    }

    /**
     * Create an evaluation item with a specific weight.
     */
    public function withWeight(int $weight): static
    {
        return $this->state(fn () => [
            'weight' => $weight,
        ]);
    }

    /**
     * Create a high-priority evaluation item (higher weight).
     */
    public function highPriority(): static
    {
        return $this->state(fn () => [
            'weight' => $this->faker->numberBetween(35, 50),
        ]);
    }

    /**
     * Create a low-priority evaluation item (lower weight).
     */
    public function lowPriority(): static
    {
        return $this->state(fn () => [
            'weight' => $this->faker->numberBetween(10, 25),
        ]);
    }
}
