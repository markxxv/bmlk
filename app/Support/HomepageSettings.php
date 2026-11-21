<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class HomepageSettings
{
    public const KEY = 'homepage';
    public const DEFAULT_BADGE_PERCENT = 70;

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
        'badge' => ['label' => 'Offer caption', 'max' => 42, 'default' => 'Jusqu’à'],
        'badge_note' => ['label' => 'Offer dates / note', 'max' => 80, 'default' => ''],
        'meta_title' => ['label' => 'Meta title', 'max' => 120, 'default' => ''],
        'meta_description' => ['label' => 'Meta description', 'max' => 320, 'default' => ''],
    ];

    public const THEMES = [
        'signature' => ['label' => 'Signature', 'background' => '#DDA1AA', 'text' => '#712B47', 'script' => '#70879C'],
        'black-friday' => ['label' => 'Black Friday', 'background' => '#0E0E10', 'text' => '#F6EEE6', 'script' => '#E9C782'],
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
            $copy['badge'] = match ($locale) {
                'en' => 'Up to',
                'es' => 'Hasta',
                default => 'Jusqu’à',
            };
            [$copy['meta_title'], $copy['meta_description']] = match ($locale) {
                'en' => ['HEMA-Free UV Gels, Bases & Tops | BLACK MILK France', 'Discover BLACK MILK UV gels, camouflage bases, top coats, polygels and cuticle care. Professional nail products featuring HEMA-free formulas.'],
                'es' => ['Geles UV, bases y tops sin HEMA | BLACK MILK France', 'Descubre los geles UV, bases camuflaje, tops, polygels y cuidado de cutículas BLACK MILK. Productos profesionales de manicura con fórmulas sin HEMA.'],
                default => ['Gels UV, bases et tops sans HEMA | BLACK MILK France', 'Découvrez les gels UV, bases camouflage, tops, polygels et soins des cuticules BLACK MILK. Produits de manucure professionnelle, avec des formules sans HEMA.'],
            };
            $translations[$locale] = $copy;
        }

        return ['theme' => 'signature', 'badge_enabled' => false, 'badge_percent' => self::DEFAULT_BADGE_PERCENT, 'benefit_icons' => HomepageBenefitIcons::DEFAULTS, 'translations' => $translations];
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
        $percent = $stored['badge_percent'] ?? null;
        if (is_int($percent) && $percent >= 1 && $percent <= 100) {
            $settings['badge_percent'] = $percent;
        }
        $settings['benefit_icons'] = HomepageBenefitIcons::normalize($stored['benefit_icons'] ?? null);

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
            'data.settings' => ['required', 'array:theme,badge_enabled,badge_percent,benefit_icons,translations'],
            'data.settings.theme' => ['required', 'string', Rule::in(array_keys(self::THEMES))],
            'data.settings.badge_enabled' => ['required', 'boolean:strict'],
            'data.settings.badge_percent' => ['required_if:data.settings.badge_enabled,true', 'nullable', 'integer:strict', 'between:1,100'],
            'data.settings.benefit_icons' => ['required', 'array:'.implode(',', array_keys(HomepageBenefitIcons::DEFAULTS))],
            'data.settings.translations' => ['required', 'array:'.implode(',', $locales)],
        ];
        $attributes = ['data.settings.badge_percent' => 'discount percentage'];

        foreach (HomepageBenefitIcons::DEFAULTS as $key => $default) {
            $path = 'data.settings.benefit_icons.'.$key;
            $rules[$path] = ['required', 'string', Rule::in(HomepageBenefitIcons::names())];
            $attributes[$path] = self::FIELDS[$key]['label'].' icon';
        }

        foreach ($locales as $locale) {
            $path = 'data.settings.translations.'.$locale;
            $rules[$path] = ['required', 'array:'.implode(',', array_keys(self::FIELDS))];

            foreach (self::FIELDS as $key => $field) {
                $rules[$path.'.'.$key] = [$key === 'badge_note' ? 'nullable' : 'required', 'string', 'max:'.$field['max'], 'not_regex:/[<>\p{Cc}]/u'];
                $attributes[$path.'.'.$key] = $field['label'].' ('.strtoupper($locale).')';
            }
        }

        $validated = Validator::make(['data' => $data], $rules, [], $attributes)->validate()['data']['settings'];

        // Reconstruct the document instead of mass-assigning unknown client keys.
        $settings = [
            'theme' => $validated['theme'],
            'badge_enabled' => $validated['badge_enabled'],
            'badge_percent' => (int) ($validated['badge_percent'] ?? self::DEFAULT_BADGE_PERCENT),
            'benefit_icons' => HomepageBenefitIcons::normalize($validated['benefit_icons']),
            'translations' => [],
        ];
        foreach ($locales as $locale) {
            foreach (self::FIELDS as $key => $field) {
                $settings['translations'][$locale][$key] = trim($validated['translations'][$locale][$key] ?? '');
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
