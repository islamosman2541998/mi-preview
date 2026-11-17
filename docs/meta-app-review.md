# طلب Page Public Content Access من Meta

الحالة بتاريخ 16 نوفمبر 2026. التطبيق: **Islam Web Studio CRM Sync** (`1715102103058760`)، Business portfolio: **Islam web studio**.

الهدف: سحب بيانات ومنشورات أي صفحة Facebook عامة لا يديرها حساب Meta الخاص بنا. بدون هذه الميزة يعمل الاستيراد فقط للصفحات التي لحسابنا دور عليها. Instagram لا يحتاج هذا الطلب.

## ما يجب أن يتم قبل التقديم (بالترتيب)

1. **توثيق النشاط التجاري (Business verification).** الحالة الآن: *Eligible for verification* ولم يبدأ. Meta تشترطه لهذه الميزة.
   المكان: Business Settings ← Security Centre ← Business verification ← Start verification، واختر الحالة "App requires access to permissions on Meta for Developers".
   يحتاج مستندات رسمية باسم النشاط (سجل تجاري أو بطاقة ضريبية، وإثبات عنوان أو هاتف).
2. **نشر الصفحات القانونية على السيرفر.** التطبيق يشير إلى `https://app.mius.it.com/privacy-policy` و`/terms` وكلها ترجع 404 حاليًا، لأن ملفات `resources/views/legal/` ومساراتها لم تُرفع بعد.
3. **استكمال App settings ← Basic:**
   - User data deletion: `https://app.mius.it.com/data-deletion` (بعد النشر).
   - App icon: صورة مربعة 1024×1024.
   - Category: الأقرب "Business and Pages".
4. **إضافة الميزة للتطبيق.** لم تظهر *Page Public Content Access* ضمن صلاحيات use case "Manage everything on your Page" ولا ضمن قائمة Add use cases. الأرجح أنها تظهر بعد اكتمال التوثيق، لكن هذا غير مؤكد؛ إن لم تظهر بعده فالحل سؤال دعم المطورين في Meta.
5. **حساب دخول للمراجع** على `https://app.mius.it.com` بصلاحية إنشاء موقع، يُنشأ خصيصًا للمراجعة ويُحذف بعدها.

## نقطة يجب حسمها قبل التقديم

الاستخدام المسموح للميزة حسب توثيق Meta هو: تحليل و/أو عرض منشورات الصفحات والتفاعل عليها. استخدامنا هو نسخ بيانات وصور صفحة نشاط تجاري لبناء نموذج موقع له قبل أن يصبح عميلًا. هذا قريب من "العرض" لكنه ليس مطابقًا تمامًا، والرفض احتمال حقيقي. الوصف أدناه يذكر الاستخدام كما هو؛ لا تغيّره ليبدو أقرب للمسموح، لأن المراجع يطابق الوصف بالفيديو.

## نص الوصف المقترح (يُلصق في نموذج الطلب)

> MI Web Development builds websites for small businesses. Our internal tool creates a private preview website for a business so the owner can see a proposed design before ordering it.
>
> An authorised staff member pastes the public URL of the business's Facebook Page into our admin panel. The app uses Page Public Content Access to read that Page's public business information (name, about, phone, email, website, address, profile picture) and its 12 most recent public posts (text, image, permalink, date). This content pre-fills the preview site: contact details, an About section and a photo gallery, each imported image stored with a link back to the original post.
>
> We read only public Page content. We do not read comments, reactions, or any data about people who interact with the Page, and we do not post, message or modify anything. Data is fetched once on request, not crawled or refreshed on a schedule. Preview sites are not indexed by search engines (noindex) and are shared privately with the business owner. Imported data is deleted on request through our data deletion page.

## خطوات الفيديو (Screencast)

1. افتح `https://app.mius.it.com` وسجّل الدخول بحساب المراجع.
2. Sites ← إنشاء موقع جديد.
3. الصق رابط صفحة Facebook عامة لا نديرها في حقل رابط المصدر واضغط استيراد.
4. أظهر الحقول التي امتلأت: الاسم، الهاتف، البريد، روابط التواصل.
5. أكمل المعالج واحفظ، ثم افتح مكتبة الوسائط وأظهر صور المنشورات المستوردة.
6. افتح الموقع التجريبي وأظهر أين يظهر المحتوى المستورد.
7. أظهر صفحتي سياسة الخصوصية وحذف البيانات.

ملاحظة: الخطوة 3 لن تعمل على صفحة لا نديرها قبل الموافقة. سجّل الفيديو على صفحة Islam Web Studio واذكر ذلك في ملاحظات المراجع.

## ملاحظات المراجع (Test instructions)

> Log in at https://app.mius.it.com with the reviewer credentials provided in this submission. Go to Sites → Create, paste a public Facebook Page URL into the "source URL" field and press Import. The form fields are filled from the Page. Finish the wizard to see the imported post images in the site's media library and on the generated preview site.
