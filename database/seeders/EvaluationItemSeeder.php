<?php

namespace Database\Seeders;

use App\Models\EvaluationItem;
use Illuminate\Database\Seeder;

class EvaluationItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $evaluationItems = [
            [
                'name' => 'Cleanliness of floors and walls',
                'name_ar' => 'نظافة الأرضيات والجدران',
                'weight' => 30,
            ],
            [
                'name' => 'Cleanliness of the tools used',
                'name_ar' => 'نظافة الأدوات المستخدمة',
                'weight' => 30,
            ],
            [
                'name' => 'Adherence to approved expiration dates',
                'name_ar' => 'الالتزام بتواريخ انتهاء الصلاحية المعتمدة',
                'weight' => 40,
            ],
        ];

        foreach ($evaluationItems as $item) {
            EvaluationItem::create($item);
        }
    }
}
