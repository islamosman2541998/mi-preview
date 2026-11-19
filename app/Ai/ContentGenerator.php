<?php

namespace App\Ai;

use App\Enums\SectionType;
use App\Filament\Support\SectionFields;
use App\Models\AiRun;
use App\Models\Section;
use App\Models\Site;
use App\Services\SiteBuilder;
use App\Services\SiteStructurePlanner;
use App\Services\SocialProfileImporter;
use App\Services\Stock\StockPhotos;
use App\Services\Stock\StockProvider;
use App\Support\ServiceIcons;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one AiRun: the source-aware planner first expands a whole site where useful,
 * then page-sized structured generation calls write the requested sections. Imported
 * business photos are reused before empty image slots fall back to stock photos.
 */
class ContentGenerator
{
    /** Rough JSON size per section, for the progress bar while streaming. */
    private const CHARS_PER_SECTION = 2200;

    public function __construct(
        private AiProviders $providers,
        private StockPhotos $stock,
        private SiteStructurePlanner $planner,
        private SiteBuilder $builder,
        private SocialProfileImporter $social,
    ) {}

    public function run(AiRun $run): void
    {
        $site = $run->site()->with('industry')->firstOrFail();

        if ($run->isWholeSite() && filled($site->source_data)) {
            $run->stage('يحلل نشاط الشركة ومحتوى السوشيال لتخطيط الصفحات…', 2);
            if ($this->planner->enrich($site)) {
                $this->builder->generateNav($site);
            }
            $site->refresh()->load('industry');
        }

        $sections = $this->sections($site, $run->section_ids);

        if ($sections->isEmpty()) {
            throw new AiException('لا توجد أقسام لتوليد محتواها — أضف صفحات وأقسامًا أولًا.');
        }

        $run->forceFill(['status' => AiRun::RUNNING, 'started_at' => now(), 'progress' => 5, 'stage' => 'يقرأ بيانات الشركة ويخطط للمحتوى…'])->save();

        $ai = $this->providers->resolve($run->options['provider'] ?? null);
        $rewrite = ! $run->isWholeSite();
        $expected = max(1, $sections->count() * self::CHARS_PER_SECTION);
        $lastWrite = 0.0;
        $processed = $inputTokens = $outputTokens = 0;
        $model = $ai->key();
        $fellBack = false;
        $written = [];

        // Keep each structured response comfortably below provider and shared-host
        // limits. Page boundaries also give the model a more coherent writing task.
        $batches = collect();
        foreach ($sections->groupBy('page_id') as $pageSections) {
            foreach ($pageSections->chunk(8) as $chunk) {
                $batches->push($chunk->values());
            }
        }

        foreach ($batches as $batchIndex => $batch) {
            $pageLabel = $batch->first()?->page?->title_ar ?: 'الموقع';
            $run->stage("يكتب محتوى صفحة {$pageLabel}…", 10 + (int) (70 * $processed / max(1, $sections->count())));
            $baseChars = $processed * self::CHARS_PER_SECTION;

            $result = $ai->generate(
                Prompt::SYSTEM,
                Prompt::user($site, $batch, $run->options['instructions'] ?? null, $rewrite),
                $ai->narrowSchema() ? SectionSchema::response($batch->pluck('type')->all()) : SectionSchema::response(),
                function (int $chars, bool $writing) use ($run, $expected, $baseChars, $pageLabel, &$lastWrite) {
                    if (microtime(true) - $lastWrite < 2.5) {
                        return; // throttle DB writes
                    }
                    $lastWrite = microtime(true);
                    $progress = 10 + (int) min(70, 70 * ($baseChars + $chars) / $expected);
                    $run->stage($writing
                        ? "يكتب محتوى صفحة {$pageLabel} بالعربية والإنجليزية…"
                        : "يجهّز محتوى صفحة {$pageLabel}…", $progress);
                },
            );

            $written += $this->apply(
                $site,
                $batch,
                $result->data,
                withSeo: $batchIndex === 0 && (! $rewrite || ($run->options['seo'] ?? false)),
            );
            $processed += $batch->count();
            $inputTokens += $result->inputTokens;
            $outputTokens += $result->outputTokens;
            $model = $result->model;
            $fellBack = $fellBack || $result->fellBack;
        }

        $result = new GenerationResult([], $model, $inputTokens, $outputTokens, $fellBack);
        $run->stage('يحفظ المحتوى ويوزع الصور…', 82);

        // Prefer the business's own imported photos before filling remaining gaps
        // from stock providers. This also covers rows the AI added to a section.
        $this->social->seedFromLibrary($site);
        foreach ($written as &$row) {
            $row['section']->refresh();
        }
        unset($row);

        $warnings = [];
        if (($run->options['fill_images'] ?? false) && ($provider = $this->provider())) {
            $warnings = $this->fillImages($run, $site, $written, $provider);
        }

        $missing = $sections->count() - count($written);
        $run->forceFill([
            'status' => AiRun::DONE,
            'progress' => 100,
            'stage' => $missing > 0 ? "تم — {$missing} قسم لم يصل محتواه، أعد توليده منفردًا." : 'تم',
            'usage' => $result->usage() + ['provider' => $ai->key(), 'sections' => count($written), 'warnings' => $warnings],
            'finished_at' => now(),
        ])->save();
    }

    /** @return Collection<int, Section> */
    private function sections(Site $site, ?array $ids): Collection
    {
        $query = Section::query()
            ->with('page')
            ->whereHas('page', fn ($q) => $q->where('site_id', $site->id))
            ->join('pages', 'pages.id', '=', 'sections.page_id')
            ->orderBy('pages.sort')->orderBy('pages.id')->orderBy('sections.sort')->orderBy('sections.id')
            ->select('sections.*');

        if ($ids !== null) {
            $query->whereIn('sections.id', $ids);
        }

        return $query->get();
    }

    /**
     * Write the generated text into the sections (keeping images and links).
     *
     * @return array<int, array{section: Section, ai: array}> written sections keyed by id
     */
    public function apply(Site $site, Collection $sections, array $data, bool $withSeo): array
    {
        $byId = $sections->keyBy('id');
        $written = [];

        DB::transaction(function () use ($site, $byId, $data, $withSeo, &$written) {
            foreach ((array) ($data['sections'] ?? []) as $entry) {
                $section = $byId->get((int) ($entry['id'] ?? 0));

                // only the sections we asked for, with the type we asked for
                if (! $section || ($entry['type'] ?? null) !== $section->type->value || ! is_array($entry['content'] ?? null)) {
                    continue;
                }

                $section->content = self::merge($section->type, $section->content ?? [], $entry['content']);
                $section->save();
                $written[$section->id] = ['section' => $section, 'ai' => $entry['content']];
            }

            if ($withSeo && is_array($data['seo'] ?? null)) {
                $seo = array_map(fn ($v) => is_string($v) ? trim($v) : null, array_intersect_key(
                    $data['seo'], array_flip(['title_ar', 'title_en', 'description_ar', 'description_en'])
                ));
                $site->forceFill(['seo' => array_filter($seo) ?: null])->save();
            }
        });

        return $written;
    }

    /**
     * AI content (form-shaped) over the existing content: text and numbers come from the
     * AI, images / links / embeds stay. Rows keep the existing image at the same index.
     */
    public static function merge(SectionType $type, array $existing, array $ai): array
    {
        $def = SectionFields::definition($type);
        $form = SectionFields::toForm($type, $existing);

        foreach (['ar', 'en'] as $locale) {
            foreach (array_keys($def['text'] ?? []) as $f) {
                $form[$locale][$f] = $ai[$locale][$f] ?? $form[$locale][$f] ?? null;
            }
        }

        foreach ($def['shared'] ?? [] as $f => $spec) {
            if ($spec[0] === 'number' && array_key_exists($f, $ai['shared'] ?? [])) {
                $form['shared'][$f] = $ai['shared'][$f];
            }
        }

        if (($items = $def['items'] ?? null) && is_array($ai['items'] ?? null)) {
            [, , $text, $sharedFields] = $items;
            $old = $form['items'] ?? [];
            $form['items'] = [];
            foreach (array_values($ai['items']) as $i => $row) {
                $new = [];
                foreach (array_keys($text) as $f) {
                    $new["{$f}_ar"] = $row["{$f}_ar"] ?? null;
                    $new["{$f}_en"] = $row["{$f}_en"] ?? null;
                }
                foreach ($sharedFields as $f => $spec) {
                    $new[$f] = match ($spec[0]) {
                        'image' => $old[$i][$f] ?? null,
                        'icon' => ServiceIcons::resolve($row[$f] ?? null),
                        default => $row[$f] ?? null,
                    };
                }
                $form['items'][] = $new;
            }
        }

        foreach (array_keys($def['lists'] ?? []) as $key) {
            if (is_array($ai['lists'][$key] ?? null)) {
                $form['lists'][$key] = array_values($ai['lists'][$key]);
            }
        }

        if (! empty($def['logos']) && is_array($ai['logos'] ?? null)) {
            $old = $form['logos'] ?? [];
            $form['logos'] = array_map(
                fn ($row, $i) => ['name' => $row['name'] ?? null, 'image' => $old[$i]['image'] ?? null],
                array_values($ai['logos']),
                array_keys(array_values($ai['logos'])),
            );
        }

        if (! empty($def['menu']) && is_array($ai['menu'] ?? null)) {
            $form['menu'] = array_values($ai['menu']);
        }

        $content = SectionFields::fromForm($type, $form);

        // keep the model's photo searches: used to fill images and as picker suggestions
        $queries = array_filter([
            'image_query' => is_string($ai['image_query'] ?? null) ? trim($ai['image_query']) : null,
            'item_queries' => array_values(array_map(fn ($r) => trim((string) ($r['image_query'] ?? '')), (array) ($ai['items'] ?? []))),
        ]);
        if ($queries) {
            $content['ai'] = $queries + ['generated_at' => now()->toIso8601String()];
        }

        return $content;
    }

    private function provider(): ?StockProvider
    {
        return array_values($this->stock->configured())[0] ?? null;
    }

    /**
     * Fill only image fields that are still empty, so nothing chosen by hand is replaced.
     *
     * @param  array<int, array{section: Section, ai: array}>  $written
     * @return list<string> warnings (images that couldn't be fetched)
     */
    private function fillImages(AiRun $run, Site $site, array $written, StockProvider $provider): array
    {
        $jobs = collect($written)->filter(fn ($w) => SectionSchema::imageFields($w['section']->type) !== []);
        $used = [];
        $warnings = [];
        $done = 0;

        foreach ($jobs as $w) {
            $run->stage('يختار صورًا مناسبة من '.$provider->label().'…', 85 + (int) (14 * $done++ / max(1, $jobs->count())));

            /** @var Section $section */
            $section = $w['section'];
            $content = $section->content;
            $query = $content['ai']['image_query'] ?? null;

            try {
                foreach (SectionSchema::imageFields($section->type) as $field) {
                    if ($field === 'image' && blank($content['shared']['image'] ?? null) && $query) {
                        $content['shared']['image'] = $this->pick($provider, $site, $query, 1, $used)[0] ?? null;
                    }

                    // a second photo of the same scene: the same search, next unused result
                    if ($field === 'image2' && blank($content['shared']['image2'] ?? null) && $query) {
                        $content['shared']['image2'] = $this->pick($provider, $site, $query, 1, $used)[0] ?? null;
                    }

                    if ($field === 'images' && empty($content['shared']['images'] ?? []) && $query) {
                        $content['shared']['images'] = $this->pick($provider, $site, $query, 6, $used);
                    }

                    if ($field === 'items.image') {
                        $key = SectionFields::definition($section->type)['items'][0];
                        foreach (($content['shared'][$key] ?? []) as $i => $row) {
                            if (blank($row['image'] ?? null)) {
                                $q = ($content['ai']['item_queries'][$i] ?? '') ?: $query;
                                if ($q) {
                                    $content['shared'][$key][$i]['image'] = $this->pick($provider, $site, $q, 1, $used)[0] ?? null;
                                }
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                Log::info('AI image fill skipped', ['section' => $section->id, 'error' => $e->getMessage()]);
                $warnings[] = $section->type->getLabel().': '.mb_substr($e->getMessage(), 0, 120);
            }

            $section->content = $content;
            $section->save();
        }

        return $warnings;
    }

    /**
     * Import up to $count photos for a query, skipping ones already used on this site run.
     *
     * @param  array<int, true>  $used
     * @return list<string> stored paths
     */
    private function pick(StockProvider $provider, Site $site, string $query, int $count, array &$used): array
    {
        $paths = [];

        foreach ($provider->search($query, 1, $count > 1 ? null : 'landscape')['photos'] as $photo) {
            if (isset($used[$photo['id']])) {
                continue;
            }
            $used[$photo['id']] = true;
            $paths[] = $provider->import($site, $photo['id'])->path;

            if (count($paths) >= $count) {
                break;
            }
        }

        return $paths;
    }
}
