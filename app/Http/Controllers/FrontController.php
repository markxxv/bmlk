<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\Page;
use App\Models\Product;
use App\Models\Representative;
use App\Support\HomepageBenefitIcons;
use App\Support\HomepageSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FrontController extends Controller
{
    public function home(): View
    {
        $coffrets = Product::query()
            ->where('category_id', 7)
            ->where('active', true)
            ->with('media')
            ->orderBy('id')
            ->get();

        $categories = Category::query()
            ->whereKeyNot(7)
            ->whereHas('products', fn ($query) => $query->where('active', true))
            ->with([
                'products' => fn ($query) => $query
                    ->where('active', true)
                    ->with('media')
                    ->orderBy('id')
                    ->limit(5),
            ])
            ->orderBy('id')
            ->get();

        $events = $this->upcomingEvents(4);

        $homepage = HomepageSettings::load();
        $heroCopy = HomepageSettings::copy($homepage, $this->catalogLocale());

        return view('home', [
            'coffrets' => $coffrets,
            'categories' => $categories,
            'events' => $events,
            'homepage' => $homepage,
            'heroCopy' => $heroCopy,
            'metaTitle' => $heroCopy['meta_title'],
            'metaDescription' => $heroCopy['meta_description'],
            'canonical' => route('home'),
        ]);
    }

    public function homepagePreview(): View
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $homepage = HomepageSettings::load();

        return view('filament.pages.homepage-preview', [
            'homepage' => $homepage,
            'heroCopy' => HomepageSettings::copy($homepage, config('app.default_locale', 'fr')),
        ]);
    }

    public function homepageIcons(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $input = $request->validate(['q' => ['nullable', 'string', 'max:80'], 'page' => ['nullable', 'integer', 'min:1', 'max:1000']]);

        return response()->json(HomepageBenefitIcons::search($input['q'] ?? '', (int) ($input['page'] ?? 1)));
    }

    public function events(): View
    {
        return view('events', [
            'events' => $this->upcomingEvents(),
            'metaTitle' => __('Événements BLACK MILK | Formations, workshops et rencontres'),
            'metaDescription' => __('Découvrez les prochaines formations, workshops et rencontres BLACK MILK en France et à l’international.'),
            'canonical' => route('events'),
        ]);
    }

    public function formations(): View
    {
        $courses = Course::query()->active()->orderBy('sort')->orderBy('id')->get();
        $onlineCourses = $courses->where('mode', 'online')->values();

        return view('formations', [
            'onlineCourses' => $onlineCourses,
            'liveCourses' => $courses->where('mode', 'live')->values(),
            // The detailed programme belongs to whichever online course carries one.
            'programmeCourse' => $onlineCourses->first(fn (Course $course): bool => $course->programmeData() !== null),
            'waxSkins' => config('formations.wax_skins'),
            'ownedIds' => auth()->check()
                ? auth()->user()->coursePurchases()->where('status', 'paid')->pluck('course_id')->all()
                : [],
            'metaTitle' => __('Formations pour prothésistes ongulaires | BLACK MILK'),
            'metaDescription' => __('Découvrez les formations de la Smirnova School : Wax Manicure, Pop-It Architect, BLACK MILK Instructor et Instagram Creator, en ligne ou en présentiel.'),
            'canonical' => route('formations'),
        ]);
    }

    public function whereToBuy(): View
    {
        $representatives = Representative::query()
            ->where('active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return view('where-to-buy', [
            'representatives' => $representatives,
            'metaTitle' => __('Où acheter BLACK MILK | Représentants officiels'),
            'metaDescription' => __('Trouvez un représentant officiel, un revendeur ou un point de vente BLACK MILK près de chez vous.'),
            'canonical' => route('where-to-buy'),
        ]);
    }

    public function page(string $slug): View|RedirectResponse
    {
        $locale = $this->catalogLocale();

        $page = Page::query()
            ->where('active', true)
            ->whereRaw("slug->>? = ?", [$locale, $slug])
            ->first();

        if (! $page) {
            $page = Page::query()
                ->where('active', true)
                ->whereRaw("slug->>'fr' = ?", [$slug])
                ->firstOrFail();

            $localizedSlug = $page->getTranslation('slug', $locale, false)
                ?: $page->getTranslation('slug', 'fr', false);

            if ($localizedSlug && $localizedSlug !== $slug) {
                return redirect()->route('page', ['slug' => $localizedSlug], 301);
            }
        }

        \App\Support\LocaleUrls::slugs($page->getTranslations('slug'));

        $title = $page->getTranslation('title', $locale, false)
            ?: $page->getTranslation('title', 'fr', false);

        $body = $page->getTranslation('body', $locale, false)
            ?: $page->getTranslation('body', 'fr', false);

        $metaTitle = $page->getTranslation('meta_title', $locale, false)
            ?: "{$title} | BLACK MILK";

        $metaDescription = $page->getTranslation('meta_description', $locale, false);

        if (blank($metaDescription)) {
            $metaDescription = Str::limit(
                trim(preg_replace('/\\s+/', ' ', strip_tags((string) $body))),
                160,
                ''
            );
        }

        $localizedSlug = $page->getTranslation('slug', $locale, false)
            ?: $page->getTranslation('slug', 'fr', false);

        return view('page', [
            'page' => $page,
            'title' => $title,
            'body' => $body,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => route('page', ['slug' => $localizedSlug]),
        ]);
    }

    public function sitemap(): Response
    {
        $categories = Category::query()
            ->whereNotNull('slug')
            ->whereHas('products', fn ($query) => $query->where('active', true))
            ->orderBy('id')
            ->get();

        $products = Product::query()
            ->where('active', true)
            ->with('media')
            ->orderBy('id')
            ->get();

        return response()
            ->view('sitemap', compact('categories', 'products'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function upcomingEvents(?int $limit = null): Collection
    {
        return Event::query()
            ->where('active', true)
            ->where(function ($query) {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '>=', now()->startOfDay())
                    ->orWhere('ends_at', '>=', now()->startOfDay());
            })
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->orderBy('sort')
            ->orderBy('id')
            ->when($limit, fn ($query, int $limit) => $query->limit($limit))
            ->get();
    }

    private function catalogLocale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, config('app.supported_locales', ['fr']), true) ? $locale : 'fr';
    }
}
