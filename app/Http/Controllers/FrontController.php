<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
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
                    ->limit(4),
            ])
            ->orderBy('id')
            ->get();

        return view('home', compact('coffrets', 'categories'));
    }

    public function shop(): View
    {
        $categories = $this->shopCategories();
        $products = $this->shopProducts()->paginate(24);

        $metaTitle = __('Boutique professionnelle | BLACK MILK');
        $metaDescription = __('Découvrez les produits professionnels BLACK MILK pour la manucure : gels, bases, tops, soins, outils, accessoires et coffrets.');
        $canonical = $products->currentPage() === 1
            ? route('shop.index')
            : $products->url($products->currentPage());

        return view('shop', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => null,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => $canonical,
        ]);
    }

    public function shopCategory(string $slug): View|RedirectResponse
    {
        $locale = $this->catalogLocale();

        $category = Category::query()
            ->whereRaw("slug->>? = ?", [$locale, $slug])
            ->first();

        if (! $category) {
            $category = Category::query()
                ->whereRaw("slug->>'fr' = ?", [$slug])
                ->firstOrFail();

            $localizedSlug = $category->getTranslation('slug', $locale, false)
                ?: $category->getTranslation('slug', 'fr', false);

            if ($localizedSlug && $localizedSlug !== $slug) {
                return redirect()->route('shop.category', ['slug' => $localizedSlug], 301);
            }
        }

        $categories = $this->shopCategories();

        $products = $this->shopProducts()
            ->where('category_id', $category->id)
            ->paginate(24);

        $name = $category->getTranslation('name', $locale, false)
            ?: $category->getTranslation('name', 'fr', false);

        $description = $category->getTranslation('description', $locale, false)
            ?: $category->getTranslation('description', 'fr', false);

        $metaTitle = $category->getTranslation('meta_title', $locale, false)
            ?: "{$name} | BLACK MILK";

        $metaDescription = $category->getTranslation('meta_description', $locale, false)
            ?: Str::limit(
                trim(preg_replace('/\s+/', ' ', strip_tags((string) $description))),
                160,
                ''
            );

        if ($metaDescription === '') {
            $metaDescription = __('Découvrez la collection :category de BLACK MILK.', [
                'category' => $name,
            ]);
        }

        $localizedSlug = $category->getTranslation('slug', $locale, false)
            ?: $category->getTranslation('slug', 'fr', false);

        $canonical = $products->currentPage() === 1
            ? route('shop.category', ['slug' => $localizedSlug])
            : $products->url($products->currentPage());

        return view('shop', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => $category,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => $canonical,
        ]);
    }

    private function shopCategories(): Collection
    {
        return Category::query()
            ->whereNotNull('slug')
            ->whereHas('products', fn ($query) => $query->where('active', true))
            ->withCount([
                'products as active_products_count' => fn ($query) => $query->where('active', true),
            ])
            ->orderBy('id')
            ->get();
    }

    private function shopProducts()
    {
        return Product::query()
            ->where('active', true)
            ->with('media')
            ->orderByDesc('featured')
            ->orderByDesc('is_new')
            ->orderBy('id');
    }

    private function catalogLocale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['fr', 'en', 'ro'], true) ? $locale : 'fr';
    }
}
