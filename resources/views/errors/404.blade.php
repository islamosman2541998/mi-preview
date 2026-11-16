<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>الصفحة غير موجودة — {{ config('preview.brand_name') }}</title>
    <style>
        :root { --ink: #15181d; --muted: #6b7280; --bg: #f6f5f2; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 16px; background: var(--bg); color: var(--ink);
            font-family: "IBM Plex Sans Arabic", system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif;
        }
        main { max-width: 440px; }
        .code { font-size: 14px; letter-spacing: .08em; color: var(--muted); margin: 0 0 12px; }
        h1 { font-size: 26px; line-height: 1.35; margin: 0 0 8px; }
        p { margin: 0; color: var(--muted); line-height: 1.7; }
        .en { direction: ltr; text-align: left; margin-top: 28px; padding-top: 20px; border-top: 1px solid #e3e1db; }
        .en h1 { font-size: 20px; }
        footer { margin-top: 32px; font-size: 13px; color: var(--muted); }
    </style>
</head>
<body>
<main>
    <p class="code">404</p>
    <h1>الصفحة دي مش موجودة</h1>
    <p>الرابط ممكن يكون غلط أو الموقع التجريبي اتشال.</p>

    <div class="en" lang="en">
        <h1>Page not found</h1>
        <p>The link may be wrong, or this preview is no longer available.</p>
    </div>

    <footer>{{ config('preview.brand_name') }}</footer>
</main>
</body>
</html>
