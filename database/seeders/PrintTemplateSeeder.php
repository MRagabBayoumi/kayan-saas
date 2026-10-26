<?php

namespace Database\Seeders;

use App\Services\PrintTemplateService;
use Illuminate\Database\Seeder;

class PrintTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = app(PrintTemplateService::class);
        $service->seedDefaultTemplates();
    }
}
