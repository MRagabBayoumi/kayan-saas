<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم رفض الوصول (403) — Kayan SaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center; max-width: 480px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 20px; font-weight: 800; color: #b91c1c; }
        p { color: #64748b; font-size: 14px; margin-top: 10px; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 20px; background: #714B67; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🛑</div>
        <h1>تم رفض الوصول (403 Forbidden)</h1>
        <p>{{ $message ?? 'غير مصرح لك بالوصول إلى هذا المورد من هذا النطاق.' }}</p>
        <a href="/" class="btn">العودة للرئيسية</a>
    </div>
</body>
</html>
