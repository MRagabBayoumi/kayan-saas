<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerApprovalController extends Controller
{
    /**
     * Display field customer registration requests pending manager approval.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status', Customer::APPROVAL_PENDING);

        $query = Customer::with(['salesRep', 'approver'])
            ->whereNotNull('sales_rep_id')
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('approval_status', $status);
            });

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', (int) $request->input('sales_rep_id'));
        }

        $pendingCount = Customer::whereNotNull('sales_rep_id')->where('approval_status', Customer::APPROVAL_PENDING)->count();
        $approvedCount = Customer::whereNotNull('sales_rep_id')->where('approval_status', Customer::APPROVAL_APPROVED)->count();
        $rejectedCount = Customer::whereNotNull('sales_rep_id')->where('approval_status', Customer::APPROVAL_REJECTED)->count();

        $customers = $query->latest('id')->paginate(15)->withQueryString();
        $salesReps = User::salesReps()->active()->orderBy('name')->get();

        return view('sales-reps.customer-requests.index', [
            'customers' => $customers,
            'currentStatus' => $status,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'salesReps' => $salesReps,
            'currentRep' => $request->input('sales_rep_id'),
            'currentSearch' => $request->input('search'),
        ]);
    }

    /**
     * Approve customer request, set credit limit and pricing tier.
     */
    public function approve(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'pricing_tier' => ['required', 'in:wholesale,distributor,retail'],
        ], [
            'credit_limit.required' => 'يرجى تحديد الحد الائتماني للعميل.',
            'credit_limit.numeric' => 'الحد الائتماني يجب أن يكون رقماً صحيحاً أو عشرياً.',
            'pricing_tier.required' => 'يرجى اختيار فئة التسعير المعتمدة.',
        ]);

        $customer->update([
            'approval_status' => Customer::APPROVAL_APPROVED,
            'credit_limit' => (float) $validated['credit_limit'],
            'pricing_tier' => $validated['pricing_tier'],
            'customer_type' => $validated['pricing_tier'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
            'is_active' => true,
        ]);

        return redirect()->back()
            ->with('status', 'success')
            ->with('message', "تم اعتماد وتفعيل حساب العميل «{$customer->name}» بنجاح مع تحديد حد ائتماني {$customer->credit_limit} ج.م وفئة {$customer->pricing_tier}.");
    }

    /**
     * Reject customer request with reason.
     */
    public function reject(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'يرجى كتابة سبب رفض اعتماد العميل لإخطار المندوب.',
        ]);

        $customer->update([
            'approval_status' => Customer::APPROVAL_REJECTED,
            'rejection_reason' => trim($validated['rejection_reason']),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'is_active' => false,
        ]);

        return redirect()->back()
            ->with('status', 'warning')
            ->with('message', "تم رفض طلب تسجيل العميل «{$customer->name}» وتوثيق سبب الرفض.");
    }
}
