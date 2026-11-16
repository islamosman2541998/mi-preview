<?php

namespace App\Ai;

use App\Models\Section;
use App\Models\Site;
use Illuminate\Support\Collection;

/**
 * Prompts for site content generation. The system prompt is fixed (cacheable);
 * everything about the site goes in the user message.
 */
class Prompt
{
    public const SYSTEM = <<<'TXT'
You write the content for preview websites that MI Web Development, a web agency, builds to pitch prospective clients in Egypt and the Gulf. The client will open the preview on their phone, so it must read like their own real website, written by someone who knows their trade.

Language
- Arabic is the main language: clear Modern Standard Arabic as used by good Arab businesses, natural and direct, never a translation of English.
- The English is written separately with the same meaning, idiomatic, not a word-for-word translation.
- Same facts, numbers and list order in both languages; each list item's ar/en fields describe the same thing.

Writing
- Be specific to this business, its trade and its region. Prefer concrete nouns (materials, equipment, service areas, timelines, standards) over adjectives.
- Short sentences. Headings are plain statements, not slogans.
- Do not use filler or agency clichés. In Arabic avoid phrases like: نفخر، رائدة، الريادة، حلول متكاملة، نسعى جاهدين، في عالم اليوم، شريكك الأمثل، تجربة فريدة، الجودة والاحتراف. In English avoid: elevate, unlock, seamless, cutting-edge, world-class, one-stop, passionate, tailored solutions, in today's world.
- No emoji, no exclamation marks, no hashtags.
- Use only the facts given. Where something is unknown (exact numbers, founding year, project names), write plausible, modest, believable details typical for a business of this kind and size. Never invent awards, certifications from named bodies other than common standards relevant to the trade, real client brand names, or real people's names.
- Testimonials: first name + last-name initial, generic role. Client lists: generic client types, not brand names.
- Calls to action talk to the visitor plainly (e.g. "اطلب عرض سعر على واتساب" / "Get a quote on WhatsApp").

Output
- Return exactly one entry in "sections" for every section listed in the request, with the same id and type, in the same order. Fill every field; use an empty string only where a field truly doesn't apply.
- image_query fields: short English searches for free stock photos of the scene (e.g. "concrete pouring construction site", "dental clinic interior"); no brand names or text.
- seo: title and meta description for the home page in both languages.
TXT;

    /**
     * @param  Collection<int, Section>  $sections  sections to write (with page loaded)
     */
    public static function user(Site $site, Collection $sections, ?string $instructions, bool $rewrite): string
    {
        $industry = $site->industry;

        $facts = array_filter([
            'company_name_ar' => $site->company_name_ar,
            'company_name_en' => $site->company_name_en,
            'industry' => $industry ? "{$industry->name_ar} / {$industry->name_en}" : null,
            'description' => $site->short_description,
            'address_ar' => $site->address_ar,
            'address_en' => $site->address_en,
            'has_whatsapp' => filled($site->whatsapp) ? 'yes' : null,
        ]);

        $source = (array) $site->source_data;
        $sourceContext = array_filter([
            'platform' => $source['platform'] ?? null,
            'profile_biography' => $source['biography'] ?? null,
            'website' => $source['website'] ?? null,
            'post_captions' => collect($source['posts'] ?? [])->pluck('caption')->filter()
                ->take(8)->map(fn ($caption) => mb_substr((string) $caption, 0, 600))->values()->all(),
        ]);

        $list = $sections->map(function (Section $s) use ($rewrite) {
            $row = [
                'id' => $s->id,
                'type' => $s->type->value,
                'page' => $s->page->slug.' ('.$s->page->title_ar.' / '.$s->page->title_en.')',
            ];
            if ($s->variant) {
                $row['variant'] = $s->variant;
            }
            if ($rewrite) {
                // section regeneration: show what is there now
                $row['current'] = ['ar' => $s->content['ar'] ?? [], 'en' => $s->content['en'] ?? []];
            }

            return $row;
        })->values()->all();

        $parts = [
            "Business facts:\n".json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ];

        if ($industry?->ai_hints) {
            $parts[] = "Notes for this industry:\n".$industry->ai_hints;
        }

        if ($sourceContext) {
            $parts[] = "Verified source material imported from the business social account. Reuse its facts and service/project descriptions; rewrite captions for website copy and never include hashtags:\n"
                .json_encode($sourceContext, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if ($rewrite) {
            $others = $site->sections()->with('page')->whereNotIn('sections.id', $sections->pluck('id'))->get()
                ->map(fn (Section $s) => $s->type->value.': '.($s->content['ar']['title'] ?? ''))
                ->filter()->implode("\n");
            if ($others) {
                $parts[] = "Other sections already on the site (stay consistent, don't repeat them):\n".$others;
            }
        }

        $parts[] = ($rewrite ? 'Rewrite these sections' : 'Write the content for these sections')
            .":\n".json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (filled($instructions)) {
            $parts[] = "Extra instructions from the agency (follow them):\n".trim($instructions);
        }

        return implode("\n\n", $parts);
    }
}
