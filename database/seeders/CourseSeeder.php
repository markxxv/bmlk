<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

/**
 * Imports the catalogue from config/formations.php into the courses table,
 * translating through lang/{en,es}.json. Existing courses are left untouched
 * so that edits made in the admin (prices, Telegram links, PDFs) survive.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $locales = config('app.supported_locales', ['fr', 'en', 'es']);

        foreach (['online', 'live'] as $mode) {
            foreach (config("formations.{$mode}", []) as $index => $item) {
                $course = Course::firstOrNew(['slug' => $item['slug']]);

                if ($course->exists) {
                    continue;
                }

                $course->fill([
                    'mode' => $mode,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'image' => $item['image'],
                    'active' => true,
                    'sort' => ($index + 1) * 10,
                ]);

                foreach ($locales as $locale) {
                    $course->setTranslation('tag', $locale, $this->t($item['tag'], $locale));
                    $course->setTranslation('description', $locale, $this->t($item['description'], $locale));
                    $course->setTranslation('points', $locale, array_map(fn (string $p) => $this->t($p, $locale), $item['points']));

                    if ($item['slug'] === 'wax-manicure') {
                        $course->setTranslation('programme', $locale, $this->waxProgramme($locale));
                    }
                }

                $course->save();
            }
        }
    }

    private function waxProgramme(string $locale): array
    {
        $map = fn (array $items): array => array_map(fn (string $s) => $this->t($s, $locale), $items);

        return [
            'facts' => array_map(fn (array $f) => [
                'value' => $this->t($f['value'], $locale),
                'label' => $this->t($f['label'], $locale),
            ], config('formations.wax_facts')),
            'about' => $map(config('formations.wax_about')),
            'goals' => $map(config('formations.wax_goals')),
            'included' => $map(config('formations.wax_included')),
            'modules' => array_map(fn (array $m) => [
                'title' => $this->t($m['title'], $locale),
                'description' => $this->t($m['description'], $locale),
            ], config('formations.wax_modules')),
        ];
    }

    private function t(string $text, string $locale): string
    {
        return $locale === 'fr' ? $text : (string) __($text, [], $locale);
    }
}
