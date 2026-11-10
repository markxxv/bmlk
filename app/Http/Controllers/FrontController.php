<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\Page;
use App\Models\Product;
use App\Models\Representative;
use Illuminate\Http\RedirectResponse;
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

        $seo = match ($this->catalogLocale()) {
            'en' => [
                'title' => 'HEMA-Free UV Gels, Bases & Tops | BLACK MILK France',
                'description' => 'Discover BLACK MILK UV gels, camouflage bases, top coats, polygels and cuticle care. Professional nail products featuring HEMA-free formulas.',
            ],
            'es' => [
                'title' => 'Geles UV, bases y tops sin HEMA | BLACK MILK France',
                'description' => 'Descubre los geles UV, bases camuflaje, tops, polygels y cuidado de cutículas BLACK MILK. Productos profesionales de manicura con fórmulas sin HEMA.',
            ],
            default => [
                'title' => 'Gels UV, bases et tops sans HEMA | BLACK MILK France',
                'description' => 'Découvrez les gels UV, bases camouflage, tops, polygels et soins des cuticules BLACK MILK. Produits de manucure professionnelle, avec des formules sans HEMA.',
            ],
        };

        return view('home', [
            'coffrets' => $coffrets,
            'categories' => $categories,
            'events' => $events,
            'metaTitle' => $seo['title'],
            'metaDescription' => $seo['description'],
            'canonical' => route('home'),
        ]);
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
