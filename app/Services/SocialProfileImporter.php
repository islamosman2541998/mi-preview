<?php

namespace App\Services;

use App\Enums\MediaSource;
use App\Enums\SectionType;
use App\Models\Site;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Imports business-owned public profile data through Meta's supported Graph APIs. */
class SocialProfileImporter
{
    public function configured(): bool
    {
        return filled($this->accessToken());
    }

    /** @return array{platform:string,url:string,username:string,name:?string,biography:?string,phone:?string,whatsapp:?string,email:?string,website:?string,address:?string,map_url:?string,profile_picture_url:?string,posts:list<array>,warnings?:list<string>} */
    public function fetch(string $url): array
    {
        ['platform' => $platform, 'username' => $username, 'url' => $canonical] = $this->parseUrl($url);

        if (! $this->configured()) {
            throw new RuntimeException('أضف META_ACCESS_TOKEN في ملف .env أولًا لتفعيل الاستيراد من Meta.');
        }

        return $platform === 'instagram'
            ? $this->fetchInstagram($canonical, $username)
            : $this->fetchFacebook($canonical, $username);
    }

    /** Download the imported profile photo before the site record exists. */
    public function importLogo(array $profile): ?string
    {
        $url = $profile['profile_picture_url'] ?? null;

        return filled($url) ? $this->downloadImage($url, 'sites/logos') : null;
    }

    /** Download post media, register it in the library, and place it into empty section image slots. */
    public function importMediaAndSeed(Site $site): int
    {
        $profile = (array) $site->source_data;
        $platform = $profile['platform'] ?? null;

        if (! in_array($platform, ['facebook', 'instagram'], true)) {
            return 0;
        }

        $imported = [];
        foreach (array_slice((array) ($profile['posts'] ?? []), 0, $this->maxPosts()) as $post) {
            $url = $post['image_url'] ?? null;
            if (! filled($url)) {
                continue;
            }

            try {
                $path = $this->downloadImage($url, "sites/{$site->id}/social");
            } catch (RuntimeException) {
                continue;
            }

            $caption = $this->cleanText($post['caption'] ?? null, 1500);
            $media = $site->media()->create([
                'path' => $path,
                'source' => $platform === 'instagram' ? MediaSource::Instagram : MediaSource::Facebook,
                'credit' => ucfirst($platform),
                // alt text columns are varchar(255); the whole caption stays in meta
                'alt_ar' => $caption !== null ? mb_strimwidth($caption, 0, 255, '…') : null,
                'alt_en' => $caption !== null ? mb_strimwidth($caption, 0, 255, '…') : null,
                'meta' => array_filter([
                    'caption' => $caption,
                    'permalink' => $post['permalink'] ?? null,
                    'published_at' => $post['published_at'] ?? null,
                    'platform' => $platform,
                ]),
            ]);
            $imported[] = ['path' => $media->path, 'caption' => $caption];
        }

        if ($imported) {
            $this->seedSections($site, $imported);
        }

        return count($imported);
    }

    /** Reuse already-downloaded business photos after the AI adds rows or sections. */
    public function seedFromLibrary(Site $site): void
    {
        $media = $site->media()
            ->whereIn('source', [MediaSource::Instagram->value, MediaSource::Facebook->value])
            ->oldest('id')
            ->get()
            ->map(fn ($item) => [
                'path' => $item->path,
                'caption' => $this->cleanText($item->meta['caption'] ?? null, 1500),
            ])
            ->all();

        if ($media) {
            $this->seedSections($site, $media, withCaptions: false);
        }
    }

    /** @return array{platform:string,username:string,url:string} */
    private function parseUrl(string $url): array
    {
        $url = trim($url);
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $username = explode('/', $path)[0] ?? '';

        $platform = match (true) {
            $host === 'instagram.com' || str_ends_with($host, '.instagram.com') => 'instagram',
            $host === 'facebook.com' || str_ends_with($host, '.facebook.com') || $host === 'fb.com' || str_ends_with($host, '.fb.com') => 'facebook',
            default => null,
        };

        $facebookNumericProfile = $platform === 'facebook' && strtolower($path) === 'profile.php';
        if ($facebookNumericProfile) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $username = (string) ($query['id'] ?? '');
        }

        $reserved = ['p', 'reel', 'reels', 'stories', 'explore', 'accounts', 'share', 'watch', 'groups', 'events', 'marketplace'];
        $validIdentifier = $facebookNumericProfile
            ? preg_match('/^\d{5,30}$/', $username)
            : preg_match('/^[A-Za-z0-9._-]{2,100}$/', $username);

        if (! $platform || ! $validIdentifier || in_array(strtolower($username), $reserved, true)) {
            throw new RuntimeException('اكتب رابط صفحة Facebook أو حساب Instagram مباشرة، وليس رابط منشور أو فيديو.');
        }

        return [
            'platform' => $platform,
            'username' => $username,
            'url' => $facebookNumericProfile
                ? "https://www.facebook.com/profile.php?id={$username}"
                : "https://www.{$platform}.com/{$username}",
        ];
    }

    private function fetchFacebook(string $url, string $username): array
    {
        $fields = 'id,name,about,description,phone,emails,website,location,picture.type(large)';
        $pageToken = null;

        try {
            $json = $this->graph($username, ['fields' => $fields]);
        } catch (RuntimeException $exception) {
            // Graph answers either way for a Page the token has no role on.
            $message = strtolower($exception->getMessage());
            if (! str_contains($message, 'unsupported get request') && ! str_contains($message, 'page public content access')) {
                throw $exception;
            }

            $pages = $this->managedFacebookPages();
            $pageToken = $this->managedPageToken($pages, $username);

            if ($pageToken !== null) {
                $json = $this->graph($username, ['fields' => $fields], $pageToken);
            } else {
                $available = collect($pages)
                    ->map(fn (array $page): string => trim((string) ($page['name'] ?? 'صفحة Facebook')).' ('.($page['id'] ?? 'بدون رقم').')')
                    ->filter()
                    ->implode('، ');

                if ($available === '') {
                    $configuredPageId = trim((string) config('services.meta.page_id'));
                    $available = $configuredPageId !== '' ? "صفحة رقم {$configuredPageId}" : 'لا توجد صفحات متاحة';
                }

                throw new RuntimeException(
                    "حساب Meta الحالي لا يملك صلاحية قراءة صفحة Facebook المطلوبة ({$username}). "
                    ."الصفحات المتاحة لهذا الحساب: {$available}. "
                    .'امنح الحساب Facebook Access للصفحة المطلوبة ثم أنشئ Page Access Token جديدًا، أو فعّل Page Public Content Access للتطبيق.'
                );
            }
        }

        // A system-user token reads a managed Page's fields but not its /posts edge;
        // that needs the Page's own token, which /me/accounts hands out.
        $pageToken ??= $this->managedPageToken($this->managedFacebookPages(), $username, (string) ($json['id'] ?? ''));

        $posts = [];
        $warnings = [];
        try {
            $postsJson = $this->graph((string) ($json['id'] ?? $username).'/posts', [
                'fields' => 'message,full_picture,created_time,permalink_url',
                'limit' => $this->maxPosts(),
            ], $pageToken);
            $posts = (array) ($postsJson['data'] ?? []);
        } catch (RuntimeException) {
            $warnings[] = 'تم استيراد بيانات صفحة Facebook الأساسية، لكن المنشورات تحتاج Page Access Token لصفحة يديرها حساب Meta، أو Page Public Content Access لصفحات الآخرين.';
        }

        $location = array_filter([
            $json['location']['street'] ?? null,
            $json['location']['city'] ?? null,
            $json['location']['state'] ?? null,
            $json['location']['country'] ?? null,
        ]);

        return $this->profile([
            'platform' => 'facebook',
            'url' => $url,
            'username' => $username,
            'name' => $json['name'] ?? null,
            'biography' => $json['about'] ?? $json['description'] ?? null,
            'phone' => $json['phone'] ?? null,
            'email' => $json['emails'][0] ?? null,
            'website' => $json['website'] ?? null,
            'address' => $location ? implode('، ', $location) : null,
            'profile_picture_url' => $json['picture']['data']['url'] ?? null,
            'posts' => array_map(fn (array $post) => [
                'caption' => $post['message'] ?? null,
                'image_url' => $post['full_picture'] ?? null,
                'permalink' => $post['permalink_url'] ?? null,
                'published_at' => $post['created_time'] ?? null,
            ], $posts),
            'warnings' => $warnings,
        ]);
    }

    private function fetchInstagram(string $url, string $username): array
    {
        $account = $this->instagramAccountId();
        if ($account === '') {
            throw new RuntimeException('لم نجد حساب Instagram احترافيًا مرتبطًا بالصفحة. اربطه بصفحة Facebook أو أضف META_INSTAGRAM_ACCOUNT_ID.');
        }

        $discovery = "business_discovery.username({$username}){username,name,biography,website,profile_picture_url,media.limit(".$this->maxPosts().'){caption,media_type,media_url,thumbnail_url,permalink,timestamp}}';
        try {
            $json = $this->graph($account, ['fields' => $discovery]);
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), '(#10)')) {
                throw new RuntimeException(
                    'تطبيق Meta لا يملك instagram_basic بعد. أضف Instagram API with Facebook Login واطلب Advanced Access للصلاحية.'
                );
            }

            throw $exception;
        }
        $profile = (array) ($json['business_discovery'] ?? []);

        return $this->profile([
            'platform' => 'instagram',
            'url' => $url,
            'username' => $profile['username'] ?? $username,
            'name' => $profile['name'] ?? null,
            'biography' => $profile['biography'] ?? null,
            'phone' => null,
            'whatsapp' => null,
            'email' => null,
            'website' => $profile['website'] ?? null,
            'address' => null,
            'map_url' => null,
            'profile_picture_url' => $profile['profile_picture_url'] ?? null,
            'posts' => array_map(fn (array $post) => [
                'caption' => $post['caption'] ?? null,
                'image_url' => ($post['media_type'] ?? null) === 'VIDEO' ? ($post['thumbnail_url'] ?? null) : ($post['media_url'] ?? null),
                'permalink' => $post['permalink'] ?? null,
                'published_at' => $post['timestamp'] ?? null,
            ], (array) ($profile['media']['data'] ?? [])),
        ]);
    }

    private function graph(string $node, array $query, ?string $token = null): array
    {
        $base = rtrim((string) config('services.meta.base_url'), '/');
        $version = trim((string) config('services.meta.version'), '/');
        $token = trim((string) ($token ?: $this->accessToken()));
        $secret = trim((string) config('services.meta.app_secret'));
        if ($token === '') {
            throw new RuntimeException('أضف META_PAGE_ACCESS_TOKEN أو META_ACCESS_TOKEN في ملف .env.');
        }
        if ($secret !== '') {
            $query['appsecret_proof'] = hash_hmac('sha256', $token, $secret);
        }

        $path = implode('/', array_map('rawurlencode', explode('/', trim($node, '/'))));
        try {
            $response = Http::withToken($token)
                ->acceptJson()->timeout(25)->get("{$base}/{$version}/{$path}", $query);
        } catch (ConnectionException) {
            throw new RuntimeException('تعذّر الاتصال بخوادم Meta. تحقق من اتصال الإنترنت على السيرفر ثم حاول مرة أخرى.');
        }

        if (! $response->successful()) {
            $this->throwGraphError($response);
        }

        $json = $response->json();
        if (! is_array($json) || isset($json['error'])) {
            $this->throwGraphError($response);
        }

        return $json;
    }

    /** @return list<array{id?:string,name?:string,username?:string,access_token?:string}> */
    private function managedFacebookPages(): array
    {
        try {
            $json = $this->graph('me/accounts', [
                'fields' => 'id,name,username,access_token',
                'limit' => 100,
            ], $this->userAccessToken());
        } catch (RuntimeException) {
            return [];
        }

        return array_values(array_filter(
            (array) ($json['data'] ?? []),
            fn (mixed $page): bool => is_array($page),
        ));
    }

    /** @param list<array{id?:string,username?:string,access_token?:string}> $pages */
    private function managedPageToken(array $pages, string ...$identifiers): ?string
    {
        $identifiers = array_filter(array_map('strtolower', $identifiers));

        $page = collect($pages)->first(fn (array $page): bool => in_array((string) ($page['id'] ?? ''), $identifiers, true)
            || in_array(strtolower((string) ($page['username'] ?? '')), $identifiers, true));

        return filled($page['access_token'] ?? null) ? (string) $page['access_token'] : null;
    }

    private function accessToken(): string
    {
        return trim((string) (config('services.meta.page_access_token') ?: config('services.meta.access_token')));
    }

    private function userAccessToken(): string
    {
        return trim((string) (config('services.meta.access_token') ?: $this->accessToken()));
    }

    private function instagramAccountId(): string
    {
        $configured = trim((string) config('services.meta.instagram_account_id'));
        if ($configured !== '') {
            return $configured;
        }

        $pageId = trim((string) config('services.meta.page_id'));
        if ($pageId === '') {
            return '';
        }

        $page = $this->graph(
            $pageId,
            ['fields' => 'instagram_business_account,connected_instagram_account'],
            $this->userAccessToken(),
        );

        return trim((string) ($page['instagram_business_account']['id']
            ?? $page['connected_instagram_account']['id']
            ?? ''));
    }

    private function throwGraphError(Response $response): never
    {
        $message = $response->json('error.message');
        throw new RuntimeException($message
            ? 'تعذّر الاستيراد من Meta: '.Str::limit((string) $message, 180)
            : 'تعذّر الاستيراد من Meta. راجع صلاحية الـ Access Token والصلاحيات المطلوبة.');
    }

    private function profile(array $profile): array
    {
        $profile = $this->extractContactDetails($profile);
        $profile['name'] = $this->cleanText($profile['name'] ?? null, 120);
        $profile['biography'] = $this->cleanText($profile['biography'] ?? null, 2000);
        $profile['posts'] = array_values(array_filter(array_map(function (array $post) {
            $post['caption'] = $this->cleanText($post['caption'] ?? null, 2000);

            return filled($post['image_url'] ?? null) || filled($post['caption'] ?? null) ? $post : null;
        }, (array) ($profile['posts'] ?? []))));

        return $profile;
    }

    private function downloadImage(string $url, string $directory): string
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowed = collect(config('services.meta.media_hosts', []))->contains(
            fn ($suffix) => $host === strtolower((string) $suffix) || str_ends_with($host, '.'.strtolower((string) $suffix))
        );

        if (($parts['scheme'] ?? null) !== 'https' || ! $allowed) {
            throw new RuntimeException('رابط الصورة المستوردة غير آمن.');
        }

        try {
            $response = Http::timeout(35)->get($url);
        } catch (Throwable) {
            throw new RuntimeException('تعذّر تنزيل صورة من الحساب.');
        }

        $body = $response->body();
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (! $response->successful() || ! isset($extensions[$mime]) || $body === '' || strlen($body) > 8 * 1024 * 1024) {
            throw new RuntimeException('تعذّر تنزيل صورة صالحة من الحساب.');
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extensions[$mime];
        Storage::disk('public')->put($path, $body, 'public');

        return $path;
    }

    /** @param list<array{path:string,caption:?string}> $media */
    private function seedSections(Site $site, array $media, bool $withCaptions = true): void
    {
        $paths = array_column($media, 'path');
        $cursor = 0;

        foreach ($site->sections()->get() as $section) {
            $content = $section->content ?? [];
            $shared = (array) ($content['shared'] ?? []);

            if ($section->type === SectionType::Hero && blank($shared['image'] ?? null)) {
                $shared['image'] = $paths[0] ?? null;
            } elseif ($section->type === SectionType::About && blank($shared['image'] ?? null)) {
                $shared['image'] = $paths[1] ?? $paths[0] ?? null;
                $shared['image2'] ??= $paths[2] ?? null;
            } elseif ($section->type === SectionType::Gallery && empty($shared['images'] ?? [])) {
                $shared['images'] = array_slice($paths, 0, 16);
            } elseif (in_array($section->type, [SectionType::Stats, SectionType::Faq, SectionType::Cta, SectionType::Booking], true)
                && blank($shared['image'] ?? null)) {
                $shared['image'] = $paths[$cursor++ % count($paths)];
            } elseif (in_array($section->type, [SectionType::Services, SectionType::Projects, SectionType::Products, SectionType::Certifications], true)) {
                foreach ((array) ($shared['items'] ?? []) as $i => $row) {
                    $item = $media[$cursor++ % count($media)];
                    $shared['items'][$i]['image'] = $row['image'] ?? $item['path'];
                    if ($withCaptions
                        && in_array($section->type, [SectionType::Services, SectionType::Projects, SectionType::Products], true)
                        && filled($item['caption'])) {
                        $content['ar']['items'][$i]['description'] = $item['caption'];
                        $content['en']['items'][$i]['description'] = $item['caption'];
                    }
                }
            }

            $content['shared'] = $shared;
            $section->forceFill(['content' => $content])->save();
        }
    }

    private function maxPosts(): int
    {
        return max(12, min(60, (int) config('services.meta.max_posts', 36)));
    }

    /** Fill contact fields that Business Discovery omits by reading the public bio/captions. */
    private function extractContactDetails(array $profile): array
    {
        $bio = (string) ($profile['biography'] ?? '');
        $captions = array_map(fn (array $post): string => (string) ($post['caption'] ?? ''), array_slice((array) ($profile['posts'] ?? []), 0, $this->maxPosts()));
        $website = (string) ($profile['website'] ?? '');
        $text = implode("\n", array_filter([$bio, $website, ...$captions]));

        if (blank($profile['email'] ?? null)
            && preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $match)) {
            $profile['email'] = mb_strtolower($match[0]);
        }

        $whatsapp = null;
        if (preg_match('~(?:wa\.me/|api\.whatsapp\.com/send\?phone=)(\+?\d{8,15})~iu', $text, $match)) {
            $whatsapp = '+'.ltrim($match[1], '+');
        }

        $phones = [];
        preg_match_all('/(?<!\d)(\+?\d[\d\s().\-]{7,}\d)(?!\d)/u', $text, $matches);
        foreach ($matches[1] ?? [] as $candidate) {
            $digits = preg_replace('/\D+/', '', $candidate);
            if (strlen($digits) >= 9 && strlen($digits) <= 15) {
                $phones[] = (str_starts_with(trim($candidate), '+') ? '+' : '').$digits;
            }
        }

        $profile['phone'] = $profile['phone'] ?? ($phones[0] ?? null);
        $profile['whatsapp'] = $profile['whatsapp'] ?? $whatsapp;

        if (blank($profile['address'] ?? null)) {
            foreach (preg_split('/\R/u', $bio) ?: [] as $line) {
                if (preg_match('/(?:address|location|العنوان|الموقع|مقرنا|القاهرة|الرياض|جدة|دبي|أبوظبي)/iu', $line)) {
                    $profile['address'] = $this->cleanText(preg_replace('/^(?:address|location|العنوان|الموقع|مقرنا)\s*[:\-]?\s*/iu', '', $line), 255);
                    break;
                }
            }
        }

        if (blank($profile['map_url'] ?? null)
            && preg_match('~https?://(?:maps\.app\.goo\.gl|(?:www\.)?google\.[^/]+/maps)/\S+~iu', $text, $match)) {
            $profile['map_url'] = rtrim($match[0], '.,)');
        }

        return $profile;
    }

    private function cleanText(mixed $value, int $limit): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = preg_replace('/(?:^|\s)#[\p{L}\p{N}_]+/u', ' ', strip_tags($value)) ?? '';
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : Str::limit($value, $limit, '');
    }
}
