<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    /**
     * Display a listing of measurement units.
     */
    public function index(): View
    {
        $units = Unit::with('baseUnit')
            ->withCount(['derivedUnits'])
            ->orderBy('id')
            ->get();

        $baseUnits = Unit::active()->get();

        return view('units.index', [
            'title' => 'إدارة وحدات القياس ومعاملات التحويل',
            'moduleName' => 'الإدارة والنظام',
            'units' => $units,
            'baseUnits' => $baseUnits,
        ]);
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create(): View
    {
        $baseUnits = Unit::active()->get();

        return view('units.create', [
            'title' => 'إضافة وحدة قياس جديدة',
            'moduleName' => 'الإدارة والنظام',
            'baseUnits' => $baseUnits,
        ]);
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:units,name'],
            'code' => ['nullable', 'string', 'max:24'],
            'base_unit_id' => ['nullable', 'exists:units,id'],
            'operator' => ['required', 'in:*,/'],
            'conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'اسم وحدة القياس مطلوب.',
            'name.unique' => 'اسم وحدة القياس مستخدم بالفعل.',
            'conversion_factor.required' => 'معامل التحويل أو عدد القطع مطلوب.',
            'conversion_factor.min' => 'معامل التحويل يجب أن يكون أكبر من الصفر.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Unit::create($validated);

        return redirect()->route('units.index')
            ->with('status', 'success')
            ->with('message', 'تمت إضافة وحدة القياس بنجاح.');
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit): View
    {
        $baseUnits = Unit::where('id', '!=', $unit->id)->active()->get();

        return view('units.edit', [
            'title' => 'تعديل وحدة القياس: '.$unit->name,
            'moduleName' => 'الإدارة والنظام',
            'unit' => $unit,
            'baseUnits' => $baseUnits,
        ]);
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('units', 'name')->ignore($unit->id)],
            'code' => ['nullable', 'string', 'max:24'],
            'base_unit_id' => ['nullable', 'exists:units,id', Rule::notIn([$unit->id])],
            'operator' => ['required', 'in:*,/'],
            'conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'اسم وحدة القياس مطلوب.',
            'name.unique' => 'اسم وحدة القياس مستخدم بالفعل.',
            'conversion_factor.required' => 'معامل التحويل أو عدد القطع مطلوب.',
            'conversion_factor.min' => 'معامل التحويل يجب أن يكون أكبر من الصفر.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $unit->update($validated);

        return redirect()->route('units.index')
            ->with('status', 'success')
            ->with('message', 'تم تحديث بيانات وحدة القياس بنجاح.');
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->is_default) {
            return redirect()->route('units.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف الوحدة الأساسية الافتراضية للنظام.');
        }

        // Check if any products currently use this unit name
        $productCount = Product::where('unit', $unit->name)->count();
        if ($productCount > 0) {
            return redirect()->route('units.index')
                ->with('status', 'error')
                ->with('message', "لا يمكن حذف هذه الوحدة لأنها مرتبطة بـ {$productCount} منتج في النظام.");
        }

        if ($unit->derivedUnits()->count() > 0) {
            return redirect()->route('units.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف هذه الوحدة لأنها وحدة أساسية تعتمد عليها وحدات أخرى.');
        }

        $unit->delete();

        return redirect()->route('units.index')
            ->with('status', 'success')
            ->with('message', 'تم حذف وحدة القياس بنجاح.');
    }
}
