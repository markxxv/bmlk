<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class HomepageSettings
{
    public const KEY = 'homepage';

    public const FIELDS = [
        'eyebrow' => ['label' => 'Eyebrow', 'max' => 100, 'default' => 'Conçu par une prothésiste ongulaire, pour les prothésistes ongulaires'],
        'headline_script' => ['label' => 'Script headline', 'max' => 32, 'default' => 'Moins de produits,'],
        'headline' => ['label' => 'Main headline', 'max' => 42, 'default' => 'plus de précision'],
        'description' => ['label' => 'Description', 'max' => 180, 'default' => 'L’essentiel sans HEMA pour des poses d’ongles rapides, faciles et durables.'],
        'primary_button' => ['label' => 'Shop button', 'max' => 30, 'default' => 'Découvrir la boutique'],
        'secondary_button' => ['label' => 'Stockists button', 'max' => 30, 'default' => 'Points de vente'],
        'feature_one' => ['label' => 'Benefit 1', 'max' => 40, 'default' => 'Formule sans HEMA'],
        'feature_one_detail' => ['label' => 'Benefit 1 detail', 'max' => 45, 'default' => 'Préserve vos ongles'],
        'feature_two' => ['label' => 'Benefit 2', 'max' => 40, 'default' => 'Disponible dans +50 pays'],
        'feature_three' => ['label' => 'Benefit 3', 'max' => 40, 'default' => 'Qualité professionnelle'],
        'badge' => ['label' => 'Campaign badge', 'max' => 42, 'default' => 'Black Friday'],
        'meta_title' => ['label' => 'Meta title', 'max' => 120, 'default' => ''],
        'meta_description' => ['label' => 'Meta description', 'max' => 320, 'default' => ''],
    ];

    public const THEMES = [
        'signature' => ['label' => 'Signature', 'background' => '#DDA1AA', 'text' => '#712B47', 'script' => '#70879C'],
        'black-friday' => ['label' => 'Black Friday', 'background' => '#26222A', 'text' => '#F6EEE6', 'script' => '#E3B69B'],
        'porcelain' => ['label' => 'Porcelain', 'background' => '#F6EEE6', 'text' => '#712B47', 'script' => '#6A839A'],
        'blue-atelier' => ['label' => 'Blue Atelier', 'background' => '#CBDBE9', 'text' => '#343C53', 'script' => '#712B47'],
    ];

    public static function locales(): array
    {
        return config('app.supported_locales', ['fr']);
    }

    public static function defaults(): array
    {
        $translations = [];

        foreach (self::locales() as $locale) {
            $copy = [];

            foreach (self::FIELDS as $key => $field) {
                $copy[$key] = __($field['default'], [], $locale);
            }

            $copy['headline_script'] = rtrim($copy['headline_script'], ', ');
            [$copy['meta_title'], $copy['meta_description']] = match ($locale) {
                'en' => ['HEMA-Free UV Gels, Bases & Tops | BLACK MILK France', 'Discover BLACK MILK UV gels, camouflage bases, top coats, polygels and cuticle care. Professional nail products featuring HEMA-free formulas.'],
                'es' => ['Geles UV, bases y tops sin HEMA | BLACK MILK France', 'Descubre los geles UV, bases camuflaje, tops, polygels y cuidado de cutículas BLACK MILK. Productos profesionales de manicura con fórmulas sin HEMA.'],
                default => ['Gels UV, bases et tops sans HEMA | BLACK MILK France', 'Découvrez les gels UV, bases camouflage, tops, polygels et soins des cuticules BLACK MILK. Produits de manucure professionnelle, avec des formules sans HEMA.'],
            };
            $translations[$locale] = $copy;
        }

        return ['theme' => 'signature', 'badge_enabled' => false, 'translations' => $translations];
    }

    public static function load(): array
    {
        $settings = self::defaults();
        $stored = SiteSetting::query()->where('key', self::KEY)->first()?->value;

        if (! is_array($stored)) {
            return $settings;
        }

        if (is_string($stored['theme'] ?? null) && isset(self::THEMES[$stored['theme']])) {
            $settings['theme'] = $stored['theme'];
        }

        $settings['badge_enabled'] = ($stored['badge_enabled'] ?? false) === true;

        foreach ($settings['translations'] as $locale => $copy) {
            foreach ($copy as $key => $default) {
                $value = $stored['translations'][$locale][$key] ?? null;

                if (is_string($value) && $value !== '') {
                    $settings['translations'][$locale][$key] = $value;
                }
            }
        }

        return $settings;
    }

    public static function save(array $data): array
    {
        $locales = self::locales();
        $rules = [
            'data.settings' => ['required', 'array:theme,badge_enabled,translations'],
            'data.settings.theme' => ['required', 'string', Rule::in(array_keys(self::THEMES))],
            'data.settings.badge_enabled' => ['required', 'boolean:strict'],
            'data.settings.translations' => ['required', 'array:'.implode(',', $locales)],
        ];
        $attributes = [];

        foreach ($locales as $locale) {
            $path = 'data.settings.translations.'.$locale;
            $rules[$path] = ['required', 'array:'.implode(',', array_keys(self::FIELDS))];

            foreach (self::FIELDS as $key => $field) {
                $rules[$path.'.'.$key] = ['required', 'string', 'max:'.$field['max'], 'not_regex:/[<>\p{Cc}]/u'];
                $attributes[$path.'.'.$key] = $field['label'].' ('.strtoupper($locale).')';
            }
        }

        $validated = Validator::make(['data' => $data], $rules, [], $attributes)->validate()['data']['settings'];

        // Reconstruct the document instead of mass-assigning unknown client keys.
        $settings = ['theme' => $validated['theme'], 'badge_enabled' => $validated['badge_enabled'], 'translations' => []];
        foreach ($locales as $locale) {
            foreach (self::FIELDS as $key => $field) {
                $settings['translations'][$locale][$key] = trim($validated['translations'][$locale][$key]);
            }
        }

        SiteSetting::query()->updateOrCreate(['key' => self::KEY], ['value' => $settings]);

        return $settings;
    }

    public static function copy(array $settings, string $locale): array
    {
        return $settings['translations'][$locale] ?? $settings['translations'][config('app.default_locale', 'fr')] ?? reset($settings['translations']);
    }
}
