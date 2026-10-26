<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الحساب موقوف مؤقتاً — Kayan SaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #fff1f2; color: #881337; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 16px; border: 1px solid #fecdd3; text-align: center; max-width: 480px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 20px; font-weight: 800; color: #9f1239; }
        p { color: #4c0519; font-size: 14px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🔒</div>
        <h1>تم إيقاف هذا الحساب مؤقتاً</h1>
        <p>تم تعليق اشتراك مؤسسة (<strong>{{ $tenant->name }}</strong>).</p>
        <p>يرجى تجديد الاشتراك أو التواصل مع إدارة المنصة لإعادة التفعيل.</p>
    </div>
</body>
</html>
