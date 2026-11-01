<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the Arabic RTL login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->isSalesRep()) {
                return redirect()->route('portal.index');
            }

            return redirect()->intended(route('dashboard'));
        }

        return view('auth.login', [
            'title' => 'تسجيل الدخول — BayoSoft ERP',
        ]);
    }

    /**
     * Handle incoming authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['sometimes', 'string'],
            'email' => ['sometimes', 'string'],
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'يرجى إدخال كلمة المرور.',
        ]);

        $loginInput = trim((string) ($request->input('login') ?: $request->input('email')));

        if ($loginInput === '') {
            throw ValidationException::withMessages([
                'email' => 'يرجى إدخال اسم المستخدم أو البريد الإلكتروني.',
                'login' => 'يرجى إدخال اسم المستخدم أو البريد الإلكتروني.',
            ]);
        }

        $throttleKey = Str::transliterate(Str::lower($loginInput).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "تم تجاوز عدد محاولات الدخول المسموح بها. يرجى الانتظار {$seconds} ثانية قبل إعادة المحاولة.",
                'login' => "تم تجاوز عدد محاولات الدخول المسموح بها. يرجى الانتظار {$seconds} ثانية قبل إعادة المحاولة.",
            ]);
        }

        $remember = $request->boolean('remember');
        $password = (string) $request->input('password');

        $user = User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('phone', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
                'login' => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
            ]);
        }

        if ($user->is_active === false || $user->is_active === 0) {
            throw ValidationException::withMessages([
                'email' => 'تم تعطيل هذا الحساب. يرجى مراجعة إدارة النظام.',
                'login' => 'تم تعطيل هذا الحساب. يرجى مراجعة إدارة النظام.',
            ]);
        }

        Auth::login($user, $remember);

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if (Auth::user()->isSalesRep()) {
            $request->session()->forget('url.intended');

            return redirect()->route('portal.index')
                ->with('status', 'success')
                ->with('message', 'مرحباً بك مجدداً، '.Auth::user()->name);
        }

        $defaultRoute = $request->attributes->get('is_central') ? route('saas.index') : route('dashboard');

        return redirect()->intended($defaultRoute)
            ->with('status', 'success')
            ->with('message', 'مرحباً بك مجدداً، '.Auth::user()->name);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'success')
            ->with('message', 'تم تسجيل الخروج بنجاح.');
    }

    /**
     * Handle Single Sign-On (SSO) login for SaaS Super Admin into the tenant instance.
     */
    public function ssoLogin(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token');
        if (empty($token)) {
            return redirect()->route('login')->with('error', 'رمز الدخول السريع غير صالح أو مفقود.');
        }

        // Atomically pull (retrieve and delete) the token to prevent replay attacks
        $payload = cache()->pull("sso_impersonate_{$token}");
        if (! $payload || empty($payload['tenant_id'])) {
            return redirect()->route('login')->with('error', 'انتهت صلاحية رابط الدخول السريع (SSO). يرجى المحاولة مجدداً من لوحة SaaS.');
        }

        $currentTenant = app()->bound('tenant') ? app('tenant') : null;
        if (! $currentTenant || (int) $currentTenant->id !== (int) $payload['tenant_id']) {
            return redirect()->route('login')->with('error', 'رمز الدخول السريع لا يتطابق مع هذا المشترك.');
        }

        // Find the tenant admin user, or create one if none exists
        $user = User::where('role', 'admin')->first()
            ?? User::where('username', 'admin')->first()
            ?? User::first();

        if (! $user) {
            $user = User::create([
                'name' => $payload['admin_name'] ?? 'مدير النظام (SuperUser)',
                'username' => 'superuser',
                'email' => $payload['admin_email'] ?? 'admin@kayan-saas.com',
                'password' => Hash::make(Str::password(16)),
                'role' => 'admin',
                'is_active' => true,
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('is_impersonating', true);
        $request->session()->put('impersonated_by', $payload['admin_name'] ?? 'Super Admin');

        return redirect()->route('dashboard')
            ->with('status', 'success')
            ->with('message', 'مرحباً بك! تم تسجيل الدخول بنجاح بصلاحيات المدير العام (SuperUser) للمشترك: '.$currentTenant->name);
    }
}
