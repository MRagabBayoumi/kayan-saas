<?php

use App\Models\Category;
use App\Models\User;
use App\Services\CategoryImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

test('department template download generates valid utf-8 bom csv', function () {
    $response = $this->get(route('settings.departments.template'));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $response->getContent();

    expect($content)->toContain('كود القسم')
        ->and($content)->toContain('اسم القسم')
        ->and($content)->toContain('DEP-ACC');
});

test('department import preview catches duplicates and validates required fields', function () {
    // Existing category in database
    Category::create([
        'name' => 'إكسسوارات حريمي',
        'code' => 'DEP-EXISTING',
        'slug' => 'dep-existing',
        'is_active' => true,
    ]);

    $csvRows = [
        ['كود القسم', 'اسم القسم', 'القسم الرئيسي', 'الحالة', 'الوصف'],
        ['DEP-NEW-1', 'توك شعر قطن', '', 'نشط', 'توك شعر بجودة عالية'],
        ['DEP-EXISTING', 'قسم بكود مكرر', '', 'نشط', 'هذا الكود موجود مسبقاً'],
        ['', '', '', 'نشط', 'سطر بدون اسم'],
        ['DEP-NEW-2', 'توك شعر قطن', '', 'نشط', 'اسم مكرر داخل نفس الملف'],
    ];

    $csvContent = "\xEF\xBB\xBF";
    $handle = fopen('php://temp', 'r+');
    foreach ($csvRows as $r) {
        fputcsv($handle, $r);
    }
    rewind($handle);
    $csvContent .= stream_get_contents($handle);
    fclose($handle);

    $file = UploadedFile::fake()->createWithContent('departments.csv', $csvContent);

    $response = $this->post(route('settings.departments.preview'), [
        'import_file' => $file,
    ]);

    $response->assertRedirect(route('settings.index', ['tab' => 'departments']));
    $response->assertSessionHas('department_import_preview');

    $preview = session('department_import_preview');
    expect($preview['success'])->toBeTrue()
        ->and($preview['summary']['valid'])->toBe(1)
        ->and($preview['summary']['invalid'])->toBe(3);
});

test('department import commits valid rows atomically and links parent categories', function () {
    $service = app(CategoryImportService::class);

    $validRows = [
        [
            'row_number' => 2,
            'name' => 'قسم المكياج والعناية',
            'code' => 'DEP-MAKEUP',
            'parent_ref' => '',
            'description' => 'مستحضرات تجميل',
            'is_active' => true,
        ],
        [
            'row_number' => 3,
            'name' => 'أحمر شفاه ومحددات',
            'code' => 'DEP-LIPSTICK',
            'parent_ref' => 'DEP-MAKEUP',
            'description' => 'أرواج ومحددات',
            'is_active' => true,
        ],
    ];

    $response = $this->post(route('settings.departments.import'), [
        'valid_rows' => json_encode($validRows),
    ]);

    $response->assertRedirect(route('settings.index', ['tab' => 'departments']));
    $response->assertSessionHas('success');

    $parent = Category::where('code', 'DEP-MAKEUP')->first();
    expect($parent)->not->toBeNull();
    expect($parent->name)->toBe('قسم المكياج والعناية');

    $child = Category::where('code', 'DEP-LIPSTICK')->first();
    expect($child)->not->toBeNull();
    expect($child->parent_id)->toBe($parent->id);
});
