<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Branch',
                'name_ar' => 'الفرع الرئيسي',
            ],
            [
                'name' => 'North Branch',
                'name_ar' => 'الفرع الشمالي',
            ],
            [
                'name' => 'South Branch',
                'name_ar' => 'الفرع الجنوبي',
            ],
            [
                'name' => 'East Branch',
                'name_ar' => 'الفرع الشرقي',
            ],
            [
                'name' => 'West Branch',
                'name_ar' => 'الفرع الغربي',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
