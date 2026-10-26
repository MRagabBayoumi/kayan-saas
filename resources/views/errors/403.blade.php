@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تم رفض الوصول (403)" :breadcrumbs="['لوحة التحكم' => url('/'), 'خطأ الصلاحيات' => '#']">
        <x-slot:actions>
            <a href="{{ url('/') }}" class="erp-btn erp-btn-primary">
                <x-icon name="home" size="16" />
                <span>العودة للرئيسية</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
    <div class="max-w-xl mx-auto py-12">
        <div class="erp-card text-center p-8">
            <div class="w-16 h-16 rounded-full bg-[var(--danger-soft)] text-[var(--danger)] flex items-center justify-center mx-auto mb-4">
                <x-icon name="shield" size="32" />
            </div>

            <h2 class="text-xl font-bold text-[var(--text-main)] mb-2">عذراً، لا تملك صلاحية الوصول</h2>
            
            <p class="text-sm text-[var(--text-muted)] mb-6 leading-relaxed">
                {{ $exception->getMessage() ?: 'ليس لديك التصريح الكافي للوصول إلى هذا القسم أو تنفيذ هذا الإجراء في نظام كيان ERP.' }}
            </p>

            <div class="erp-alert erp-alert-danger text-right mb-6">
                <x-icon name="alert-triangle" size="18" />
                <div class="text-xs">
                    إذا كنت تعتقد أن هذا خطأ، يرجى مراجعة مدير النظام لمنحك الصلاحيات المناسبة لدورك الوظيفي.
                </div>
            </div>

            <div class="flex items-center justify-center gap-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="arrow-right" size="16" />
                    <span>الرجوع للصفحة السابقة</span>
                </a>
                <a href="{{ url('/') }}" class="erp-btn erp-btn-primary">
                    <x-icon name="home" size="16" />
                    <span>لوحة التحكم</span>
                </a>
            </div>
        </div>
    </div>
@endsection
