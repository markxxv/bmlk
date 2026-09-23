<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Representative;
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

    public function shopProduct(string $slug): View|RedirectResponse
    {
        $locale = $this->catalogLocale();

        $product = Product::query()
            ->where('active', true)
            ->with(['category', 'media'])
            ->whereRaw("slug->>? = ?", [$locale, $slug])
            ->first();

        if (! $product) {
            $product = Product::query()
                ->where('active', true)
                ->with(['category', 'media'])
                ->whereRaw("slug->>'fr' = ?", [$slug])
                ->firstOrFail();

            $localizedSlug = $product->getTranslation('slug', $locale, false)
                ?: $product->getTranslation('slug', 'fr', false);

            if ($localizedSlug && $localizedSlug !== $slug) {
                return redirect()->route('shop.product', ['slug' => $localizedSlug], 301);
            }
        }

        $name = $product->getTranslation('name', $locale, false)
            ?: $product->getTranslation('name', 'fr', false);

        $description = $product->getTranslation('description', $locale, false)
            ?: $product->getTranslation('description', 'fr', false);

        $categoryName = $product->category?->getTranslation('name', $locale, false)
            ?: $product->category?->getTranslation('name', 'fr', false);

        $categoryDescription = $product->category?->getTranslation('description', $locale, false)
            ?: $product->category?->getTranslation('description', 'fr', false);

        $metaTitle = $product->getTranslation('meta_title', $locale, false)
            ?: "{$name} – {$categoryName} | BLACK MILK";

        $metaDescription = $product->getTranslation('meta_description', $locale, false)
            ?: $description;

        if (blank($metaDescription) && $categoryDescription) {
            $metaDescription = __(':product fait partie de la collection :category. :description', [
                'product' => $name,
                'category' => $categoryName,
                'description' => $categoryDescription,
            ]);
        }

        if (blank($metaDescription)) {
            $metaDescription = __('Découvrez :product de BLACK MILK, produit professionnel pour la manucure.', [
                'product' => $name,
            ]);
        }

        $metaDescription = Str::limit(
            trim(preg_replace('/\s+/', ' ', strip_tags((string) $metaDescription))),
            160,
            ''
        );

        $localizedSlug = $product->getTranslation('slug', $locale, false)
            ?: $product->getTranslation('slug', 'fr', false);

        $canonical = route('shop.product', ['slug' => $localizedSlug]);

        $recommended = Product::query()
            ->where('active', true)
            ->whereKeyNot($product->id)
            ->with(['category', 'media'])
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$product->category_id])
            ->orderByDesc('featured')
            ->orderByDesc('is_new')
            ->orderBy('id')
            ->limit(4)
            ->get();

        return view('product', [
            'product' => $product,
            'recommended' => $recommended,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => $canonical,
            'ogImage' => $product->getFirstMediaUrl('images'),
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
