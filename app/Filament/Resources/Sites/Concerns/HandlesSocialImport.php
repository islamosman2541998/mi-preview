<?php

namespace App\Filament\Resources\Sites\Concerns;

use App\Services\SocialProfileImporter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

trait HandlesSocialImport
{
    public function importSocialProfile(): void
    {
        $url = $this->data['source_url'] ?? null;
        $validator = Validator::make(['url' => $url], ['url' => ['required', 'url:https', 'max:2048']]);

        if ($validator->fails()) {
            Notification::make()->title('اكتب رابط Facebook أو Instagram صحيحًا يبدأ بـ https://')->warning()->send();

            return;
        }

        try {
            $importer = app(SocialProfileImporter::class);
            $profile = $importer->fetch($url);
            $logo = null;
            try {
                $logo = $importer->importLogo($profile);
            } catch (RuntimeException) {
                // Profile text and posts are still useful when the avatar CDN rejects a download.
            }
        } catch (RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        } catch (Throwable $e) {
            report($e); // the toast is generic on purpose, so the cause has to reach the log

            Notification::make()->title('حدث خطأ غير متوقع أثناء الاستيراد من Meta.')->danger()->send();

            return;
        }

        $name = $profile['name'] ?: $profile['username'];
        $this->data['source_url'] = $profile['url'];
        $this->data['source_data'] = $profile;
        $this->data['company_name_ar'] = $this->data['company_name_ar'] ?: $name;
        $this->data['company_name_en'] = $this->data['company_name_en'] ?: $name;
        $this->data['slug'] = $this->data['slug'] ?: Str::limit(Str::slug($name), 40, '');
        $this->data['short_description'] = $this->data['short_description'] ?: $profile['biography'];
        $this->data['phone'] = $profile['phone'] ?: $this->data['phone'];
        $this->data['whatsapp'] = ($profile['whatsapp'] ?? null) ?: $this->data['whatsapp'];
        $this->data['email'] = $profile['email'] ?: $this->data['email'];
        $this->data['address_ar'] = $profile['address'] ?: $this->data['address_ar'];
        $this->data['address_en'] = $profile['address'] ?: $this->data['address_en'];
        $this->data['map_url'] = ($profile['map_url'] ?? null) ?: $this->data['map_url'];
        $this->data['socials'][$profile['platform']] = $profile['url'];

        if ($logo) {
            $key = (string) Str::uuid();
            $this->data['logo_path'] = [$key => $logo];
            $this->data['logo_nobg_path'] = [$key => $logo];
        }

        $warnings = (array) ($profile['warnings'] ?? []);
        $notification = Notification::make()
            ->title($warnings ? 'تم استيراد البيانات الأساسية' : 'تم استيراد بيانات الحساب')
            ->body(implode(' ', array_filter([
                'راجع البيانات في الخطوات التالية. تم العثور على '.count($profile['posts']).' منشورًا لاستخدام صورها وكابشناتها.',
                ...$warnings,
            ])));

        ($warnings ? $notification->warning() : $notification->success())->send();
    }
}
