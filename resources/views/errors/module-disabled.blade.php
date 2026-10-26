<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموديول غير مفعل في باقتك — {{ $tenant->name ?? 'Kayan ERP' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            max-width: 520px;
            width: 100%;
            padding: 40px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .icon-wrap {
            width: 72px;
            height: 72px;
            background-color: #fef3c7;
            color: #d97706;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px auto;
            font-size: 32px;
        }
        h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 12px;
            color: #0f172a;
        }
        p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .plan-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            background-color: #714B67;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn:hover {
            background-color: #583950;
        }
        .btn-back {
            display: block;
            margin-top: 16px;
            color: #94a3b8;
            font-size: 13px;
            text-decoration: none;
        }
        .btn-back:hover {
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">🔒</div>
        <div class="plan-badge">باقتك الحالية: {{ $tenant->plan_name ?? 'الأساسية' }}</div>
        
        @php
            $moduleNames = [
                'accounting' => 'المحاسبة والتقارير المالية والأستاذ العام',
                'sales_reps' => 'إدارة المناديب وتتبع خطوط السير والزيارات',
                'installments' => 'جدولة الأقساط للموردين والعملاء',
                'warehouses' => 'تعدد الفروع والمستودعات',
            ];
            $readableName = $moduleNames[$module] ?? $module;
        @endphp

        <h1>موديول غير مفعل في باقتك</h1>
        <p>
            موديول <strong>({{ $readableName }})</strong> غير مشمول في باقة الاشتراك الحالية لشركتك. يمكنك الترقية إلى الباقة الأعلى للاستفادة الكاملة من كافة مميزات النظام المتقدمة.
        </p>

        <a href="https://wa.me/201099616086?text={{ urlencode('مرحباً، أود ترقية باقتي لتفعيل موديول: ' . $readableName) }}" target="_blank" class="btn">
            طلب ترقية الباقة الآن 🚀
        </a>

        <a href="/" class="btn-back">← العودة للرئيسية</a>
    </div>
</body>
</html>
