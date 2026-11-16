<?php

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Contracts\View\View;

class PreviewPageController extends Controller
{
    public function __invoke(Site $site, Locale $locale, ?string $page = null): View
    {
        $current = $site->pages()
            ->published()
            ->when(
                $page === null,
                fn ($q) => $q->where('is_home', true),
                fn ($q) => $q->where('slug', $page),
            )
            ->with('visibleSections')
            ->first();

        abort_if($current === null, 404);

        $site->load(['industry', 'menu', 'pages' => fn ($q) => $q->published()]);

        $view = 'templates.'.$site->template.'.layout';

        return view(view()->exists($view) ? $view : 'preview.placeholder', [
            'page' => $current,
            'sections' => $current->visibleSections,
            'homeSlug' => $site->pages->firstWhere('is_home', true)?->slug,
            'alternateUrl' => $this->alternatePath($current, $locale),
        ]);
    }

    private function alternatePath(Page $page, Locale $locale): string
    {
        return $page->path($locale->other());
    }
}
