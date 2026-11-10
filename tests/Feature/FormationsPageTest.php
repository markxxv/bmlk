<?php

namespace Tests\Feature;

use App\Models\Course;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormationsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CourseSeeder::class);
    }

    public function test_formations_page_is_public_and_reads_courses_from_the_database(): void
    {
        $this->get(route('formations'))
            ->assertOk()
            ->assertSee('Smirnova School')
            ->assertSee('WAX MANICURE')
            ->assertSee('POP-IT ARCHITECT')
            ->assertSee('INSTAGRAM CREATOR')
            ->assertSee('img/formations/wax-tin.webp')
            ->assertSee('mailto:office.blackmilk@gmail.com')
            ->assertSee(route('events'))
            ->assertSee('Objectifs de la formation');
    }

    public function test_seeder_imports_the_whole_catalogue_once_with_all_translations(): void
    {
        $this->assertSame(10, Course::count());
        $this->seed(CourseSeeder::class); // idempotent
        $this->assertSame(10, Course::count());

        $wax = Course::where('slug', 'wax-manicure')->firstOrFail();
        $this->assertSame('150.00', $wax->price);
        $this->assertSame('Lifetime access to the online course', $wax->getTranslation('points', 'en')[0]);
        $this->assertSame('Acceso de por vida al curso online', $wax->getTranslation('points', 'es')[0]);
        $this->assertCount(6, $wax->getTranslation('programme', 'es')['modules']);
        $this->assertNull(Course::where('slug', 'instagram-creator')->value('price'));
    }

    public function test_seeder_does_not_overwrite_admin_edits(): void
    {
        Course::where('slug', 'wax-manicure')->update(['price' => 199, 'telegram_chat_url' => 'https://t.me/wax-chat']);
        $this->seed(CourseSeeder::class);

        $this->assertSame('199.00', Course::where('slug', 'wax-manicure')->value('price'));
        $this->assertSame('https://t.me/wax-chat', Course::where('slug', 'wax-manicure')->value('telegram_chat_url'));
    }

    public function test_page_is_translated_and_priced_courses_get_a_buy_button(): void
    {
        $this->get('/en/formations')
            ->assertOk()
            ->assertSee('Buy now')
            ->assertSee('Lifetime access to the online course')
            ->assertSee(route('courses.buy', ['slug' => 'wax-manicure', 'locale' => 'en']), false);

        $this->get('/es/formations')->assertOk()->assertSee('Comprar ahora');
    }

    public function test_courses_without_a_price_and_live_courses_stay_on_request(): void
    {
        $this->post(route('courses.buy', ['slug' => 'instagram-creator']))->assertNotFound();
        $this->post(route('courses.buy', ['slug' => 'wax-manicure-live']))->assertNotFound();
    }

    public function test_telegram_links_never_appear_on_the_public_page(): void
    {
        Course::where('slug', 'wax-manicure')->update([
            'telegram_channel_url' => 'https://t.me/secret-materials',
            'telegram_chat_url' => 'https://t.me/secret-chat',
        ]);

        $this->get(route('formations'))->assertOk()
            ->assertDontSee('secret-materials')->assertDontSee('secret-chat');
    }
}
