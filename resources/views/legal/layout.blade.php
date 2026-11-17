<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} — {{ config('preview.brand_name') }}</title>
    <style>
        :root { color-scheme: light; --ink:#17202a; --muted:#5d6874; --line:#dfe4e8; --brand:#155eef; --paper:#fff; --wash:#f5f7fa; }
        * { box-sizing: border-box; }
        body { margin:0; color:var(--ink); background:var(--wash); font-family:system-ui,-apple-system,"Segoe UI",Tahoma,Arial,sans-serif; line-height:1.8; }
        header, main, footer { width:min(920px, calc(100% - 32px)); margin-inline:auto; }
        header { padding:32px 0 20px; display:flex; gap:20px; align-items:center; justify-content:space-between; }
        header a { color:var(--ink); text-decoration:none; font-weight:800; }
        nav { display:flex; gap:14px; flex-wrap:wrap; }
        nav a { color:var(--muted); font-size:.92rem; font-weight:600; }
        main { background:var(--paper); border:1px solid var(--line); border-radius:16px; padding:clamp(24px,5vw,56px); box-shadow:0 14px 40px rgba(20,32,50,.06); }
        h1 { margin:0 0 8px; font-size:clamp(2rem,5vw,3.25rem); line-height:1.2; }
        h2 { margin:36px 0 8px; font-size:1.3rem; line-height:1.4; }
        p, li { color:var(--muted); }
        .updated { margin:0 0 32px; font-size:.92rem; }
        .english { direction:ltr; text-align:left; border-top:1px solid var(--line); margin-top:48px; padding-top:32px; }
        a { color:var(--brand); }
        footer { padding:24px 0 40px; color:var(--muted); font-size:.9rem; text-align:center; }
        @media (max-width:680px) { header { align-items:flex-start; flex-direction:column; } }
    </style>
</head>
<body>
<header>
    <a href="{{ route('legal.privacy') }}">{{ config('preview.brand_name') }}</a>
    <nav aria-label="Legal pages">
        <a href="{{ route('legal.privacy') }}">الخصوصية</a>
        <a href="{{ route('legal.terms') }}">الشروط</a>
        <a href="{{ route('legal.data-deletion') }}">حذف البيانات</a>
    </nav>
</header>
<main>
    {{ $slot }}
</main>
<footer>© {{ date('Y') }} {{ config('preview.brand_name') }}</footer>
</body>
</html>
