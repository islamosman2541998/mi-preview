<x-legal.layout title="سياسة الخصوصية">
    <h1>سياسة الخصوصية</h1>
    <p class="updated">آخر تحديث: 16 نوفمبر 2026</p>

    <p>توضح هذه السياسة كيف تجمع منصة {{ config('preview.brand_name') }} البيانات وتستخدمها وتحميها عند ربط حساب Facebook أو Instagram، أو عند إنشاء مسودة موقع من بيانات نشاط تجاري.</p>

    <h2>البيانات التي نعالجها</h2>
    <ul>
        <li>معرّفات الحساب والصفحة وحساب Instagram الاحترافي والصلاحيات التي تمنحها عبر Meta.</li>
        <li>البيانات العامة أو المهنية التي تختار استيرادها، مثل الاسم والوصف والصور والمنشورات والروابط وبيانات التواصل المتاحة.</li>
        <li>بيانات تشغيل وأمان محدودة، مثل عنوان IP ونوع المتصفح وسجلات الأخطاء.</li>
    </ul>

    <h2>كيف نستخدم البيانات</h2>
    <p>نستخدم البيانات لتنفيذ طلب الاستيراد، وإنشاء مسودة الموقع وتعديلها، وتشغيل التكامل مع Meta، ومنع إساءة الاستخدام، وتقديم الدعم. لا نبيع البيانات الشخصية.</p>

    <h2>المشاركة والاحتفاظ</h2>
    <p>قد نعالج البيانات عبر مزودي الاستضافة والخدمات الضروريين لتشغيل المنصة، وبالقدر اللازم فقط. نحتفظ بالبيانات ما دامت مطلوبة لتقديم الخدمة أو للوفاء بالتزامات قانونية وأمنية، ثم نحذفها أو نجعلها مجهولة الهوية.</p>

    <h2>اختياراتك وحقوقك</h2>
    <p>يمكنك إلغاء صلاحيات التطبيق من إعدادات Meta، أو طلب نسخة من بياناتك أو تصحيحها أو حذفها. راجع <a href="{{ route('legal.data-deletion') }}">تعليمات حذف البيانات</a>.</p>

    <h2>التواصل</h2>
    <p>للاستفسارات المتعلقة بالخصوصية، راسلنا على <a href="mailto:{{ config('preview.legal_email') }}">{{ config('preview.legal_email') }}</a>.</p>

    <section class="english" lang="en">
        <h1>Privacy Policy</h1>
        <p class="updated">Last updated: November 16, 2026</p>
        <p>This policy explains how {{ config('preview.brand_name') }} processes data when you connect Facebook or Instagram or create a website draft from business information.</p>
        <h2>Data we process</h2>
        <ul>
            <li>Account, Page, professional Instagram account and granted-permission identifiers supplied through Meta.</li>
            <li>Public or professional information you choose to import, including names, descriptions, media, posts, links and available contact details.</li>
            <li>Limited security and operational data such as IP address, browser type and error logs.</li>
        </ul>
        <h2>How we use data</h2>
        <p>We use data to perform imports, create and edit website drafts, operate the Meta integration, prevent abuse and provide support. We do not sell personal data.</p>
        <h2>Sharing and retention</h2>
        <p>Data may be processed by service providers needed to operate the platform and only for that purpose. We retain it while needed to provide the service or meet legal and security obligations, then delete or anonymize it.</p>
        <h2>Your choices</h2>
        <p>You can revoke the app in Meta settings or request access, correction or deletion. See our <a href="{{ route('legal.data-deletion') }}">Data Deletion Instructions</a>.</p>
        <h2>Contact</h2>
        <p>Email <a href="mailto:{{ config('preview.legal_email') }}">{{ config('preview.legal_email') }}</a> for privacy questions.</p>
    </section>
</x-legal.layout>
