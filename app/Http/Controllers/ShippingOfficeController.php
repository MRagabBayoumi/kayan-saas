<?php

namespace App\Http\Controllers;

use App\Models\ShippingOffice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingOfficeController extends Controller
{
    public function index(): View
    {
        $offices = ShippingOffice::orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('shipping-offices.index', [
            'title' => 'مكاتب وشركات الشحن',
            'offices' => $offices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'shipping_type' => ['required', 'in:international_china,domestic_egypt'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'phone_alt' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'rate_per_cbm_or_kg' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم شركة أو مكتب الشحن مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب للتواصل.',
        ]);

        ShippingOffice::create([
            'name' => trim($validated['name']),
            'shipping_type' => $validated['shipping_type'],
            'contact_person' => filled($validated['contact_person'] ?? null) ? trim($validated['contact_person']) : null,
            'phone' => trim($validated['phone']),
            'phone_alt' => filled($validated['phone_alt'] ?? null) ? trim($validated['phone_alt']) : null,
            'address' => filled($validated['address'] ?? null) ? trim($validated['address']) : null,
            'rate_per_cbm_or_kg' => filled($validated['rate_per_cbm_or_kg'] ?? null) ? (float) $validated['rate_per_cbm_or_kg'] : null,
            'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->route('shipping-offices.index')->with('success', 'تم إضافة مكتب الشحن بنجاح.');
    }

    public function update(Request $request, ShippingOffice $shippingOffice): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'shipping_type' => ['required', 'in:international_china,domestic_egypt'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'phone_alt' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'rate_per_cbm_or_kg' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم شركة أو مكتب الشحن مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب للتواصل.',
        ]);

        $shippingOffice->update([
            'name' => trim($validated['name']),
            'shipping_type' => $validated['shipping_type'],
            'contact_person' => filled($validated['contact_person'] ?? null) ? trim($validated['contact_person']) : null,
            'phone' => trim($validated['phone']),
            'phone_alt' => filled($validated['phone_alt'] ?? null) ? trim($validated['phone_alt']) : null,
            'address' => filled($validated['address'] ?? null) ? trim($validated['address']) : null,
            'rate_per_cbm_or_kg' => filled($validated['rate_per_cbm_or_kg'] ?? null) ? (float) $validated['rate_per_cbm_or_kg'] : null,
            'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $shippingOffice->is_active,
        ]);

        return redirect()->route('shipping-offices.index')->with('success', 'تم تحديث بيانات مكتب الشحن بنجاح.');
    }

    public function destroy(ShippingOffice $shippingOffice): RedirectResponse
    {
        $shippingOffice->delete();

        return redirect()->route('shipping-offices.index')->with('success', 'تم حذف مكتب الشحن بنجاح.');
    }
}
