<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of product categories.
     */
    public function index(): View
    {
        $categories = Category::with('parent')
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('categories.index', [
            'title' => 'تصنيفات المنتجات',
            'moduleName' => 'المخزون والمنتجات',
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.max' => 'اسم التصنيف يجب ألا يتجاوز 150 حرفاً.',
            'slug.unique' => 'الاسم اللطيف (Slug) مستخدم بالفعل.',
            'parent_id.exists' => 'التصنيف الرئيسي المحدد غير صالح.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('status', 'success')
            ->with('message', 'تمت إضافة التصنيف بنجاح.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', Rule::unique('categories')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->whereNot('id', $category->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم التصنيف مطلوب.',
            'slug.unique' => 'الاسم اللطيف مستخدم بالفعل.',
            'parent_id.exists' => 'التصنيف الرئيسي غير صالح أو لا يمكن ربط التصنيف بنفسه.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('status', 'success')
            ->with('message', 'تم تحديث بيانات التصنيف بنجاح.');
    }

    /**
     * Remove the specified category safely.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()->route('categories.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف التصنيف لأنه مرتبط بمنتجات حالية. يرجى نقل المنتجات لتصنيف آخر أولاً.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('status', 'success')
            ->with('message', 'تم حذف التصنيف بنجاح.');
    }
}
