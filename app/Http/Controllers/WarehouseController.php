<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Warehouse;
use App\Services\StocktakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::withCount('stockBalances')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('warehouses.index', [
            'title' => 'إدارة المستودعات',
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.required' => 'كود المستودع مطلوب.',
            'code.unique' => 'كود المستودع مسجل مسبقاً.',
            'name.required' => 'اسم المستودع مطلوب.',
        ]);

        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        // If this is the very first warehouse, make it default automatically
        if (Warehouse::count() === 0) {
            $isDefault = true;
        }

        Warehouse::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'location' => $validated['location'] ?? null,
            'is_default' => $isDefault,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('warehouses.index')->with('success', 'تم إضافة المستودع بنجاح.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'name' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.required' => 'كود المستودع مطلوب.',
            'code.unique' => 'كود المستودع مسجل مسبقاً.',
            'name.required' => 'اسم المستودع مطلوب.',
        ]);

        $isDefault = $request->boolean('is_default');

        if ($isDefault && ! $warehouse->is_default) {
            Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'location' => $validated['location'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $warehouse->is_active,
            'is_default' => $isDefault || $warehouse->is_default,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('warehouses.index')->with('success', 'تم تحديث بيانات المستودع بنجاح.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->is_default) {
            return redirect()->route('warehouses.index')->with('error', 'لا يمكن حذف المستودع الافتراضي للنظام.');
        }

        $hasStock = $warehouse->stockBalances()->where('quantity', '>', 0)->exists();
        if ($hasStock) {
            return redirect()->route('warehouses.index')->with('error', 'لا يمكن حذف المستودع لوجود أرصدة أصناف ومخزون به.');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'تم حذف المستودع بنجاح.');
    }

    /**
     * Display real-time warehouse inventory breakdown.
     */
    public function show(Warehouse $warehouse, Request $request, StocktakeService $stocktakeService): View
    {
        $search = $request->input('search');
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $breakdown = $stocktakeService->getWarehouseStockBreakdown($warehouse, $search, $categoryId);
        $categories = Category::active()->orderBy('name')->get();

        return view('warehouses.show', [
            'title' => 'جرد ورصيد مستودع: '.$warehouse->name,
            'warehouse' => $warehouse,
            'breakdown' => $breakdown,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
        ]);
    }

    /**
     * Dedicated printable warehouse stock report supporting A4, A5, and Thermal 80mm.
     */
    public function printStock(Warehouse $warehouse, Request $request, StocktakeService $stocktakeService): View
    {
        $search = $request->input('search');
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $format = $request->input('format', 'a4');
        if (! in_array($format, ['a4', 'a5', 'thermal_80mm', 'thermal'], true)) {
            $format = 'a4';
        }

        $breakdown = $stocktakeService->getWarehouseStockBreakdown($warehouse, $search, $categoryId);
        $company = CompanySetting::getSettings();

        return view('warehouses.stock-print', [
            'title' => 'تقرير مخزون مستودع: '.$warehouse->name,
            'warehouse' => $warehouse,
            'breakdown' => $breakdown,
            'format' => $format,
            'company' => $company,
            'autoPrint' => $request->boolean('auto_print', false),
        ]);
    }
}
