<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Base unit: Piece (قطعة)
        $piece = Unit::firstOrCreate(
            ['name' => 'قطعة'],
            [
                'code' => 'PCS',
                'base_unit_id' => null,
                'operator' => '*',
                'conversion_factor' => 1.0000,
                'is_active' => true,
                'is_default' => true,
                'notes' => 'الوحدة الأساسية الصغرى للصنف',
            ]
        );

        // 2. Dozen (دستة = 12 قطعة)
        Unit::firstOrCreate(
            ['name' => 'دستة'],
            [
                'code' => 'DZ',
                'base_unit_id' => $piece->id,
                'operator' => '*',
                'conversion_factor' => 12.0000,
                'is_active' => true,
                'is_default' => false,
                'notes' => 'تحتوي على 12 قطعة أوتوماتيكياً',
            ]
        );

        // 3. Carton (كرتونة = 24 قطعة)
        Unit::firstOrCreate(
            ['name' => 'كرتونة'],
            [
                'code' => 'CTN',
                'base_unit_id' => $piece->id,
                'operator' => '*',
                'conversion_factor' => 24.0000,
                'is_active' => true,
                'is_default' => false,
                'notes' => 'تحتوي على 24 قطعة (أو حسب مواصفة الصنف)',
            ]
        );

        // 4. Pack (باكت = 10 قطع)
        Unit::firstOrCreate(
            ['name' => 'باكت'],
            [
                'code' => 'PKT',
                'base_unit_id' => $piece->id,
                'operator' => '*',
                'conversion_factor' => 10.0000,
                'is_active' => true,
                'is_default' => false,
                'notes' => 'تحتوي على 10 قطع',
            ]
        );

        // 5. Set (طقم)
        Unit::firstOrCreate(
            ['name' => 'طقم'],
            [
                'code' => 'SET',
                'base_unit_id' => $piece->id,
                'operator' => '*',
                'conversion_factor' => 1.0000,
                'is_active' => true,
                'is_default' => false,
                'notes' => 'طقم متكامل كوحدة بيعية واحدة',
            ]
        );
    }
}
