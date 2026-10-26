<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>انتهت فترة الاشتراك — Kayan SaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #fffbeb; color: #78350f; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 16px; border: 1px solid #fef3c7; text-align: center; max-width: 500px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .icon { font-size: 54px; margin-bottom: 16px; }
        h1 { font-size: 22px; font-weight: 800; color: #b45309; margin-bottom: 12px; }
        p { color: #92400e; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        .badge { display: inline-block; background: #fef3c7; color: #b45309; padding: 6px 14px; border-radius: 9999px; font-weight: 700; font-size: 13px; margin-bottom: 15px; }
        .btn { display: inline-block; background: #714B67; color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">⏳</div>
        <div class="badge">اشتراك منتهي</div>
        <h1>انتهت صلاحية باقة الاشتراك</h1>
        <p>لقد انتهت فترة اشتراك مؤسسة (<strong>{{ $tenant->name }}</strong>) في <strong>{{ $tenant->expires_at?->format('Y-m-d') }}</strong>. بياناتكم وقواعد بياناتكم محفوظة بالكامل ولم يتم مس أي بيانات.</p>
        <p>يرجى تجديد الاشتراك مع إدارة المنصة للمتابعة الفورية.</p>
        <a href="mailto:support@kayan-erp.com" class="btn">التواصل للتجديد والدعم الفني</a>
    </div>
</body>
</html>
