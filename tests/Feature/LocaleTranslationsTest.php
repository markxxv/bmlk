<?php

namespace Tests\Feature;

use App\Mail\PaymentStatusMail;
use App\Models\Order;
use App\Support\OrderLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocaleTranslationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_language_files_have_the_same_keys_and_placeholders(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
        $es = json_decode(file_get_contents(lang_path('es.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(array_keys($en), array_keys($es));

        foreach ($en as $key => $translation) {
            foreach ([$translation, $es[$key]] as $text) {
                $this->assertNotSame('', trim($text), "Empty translation for [$key]");
                preg_match_all('/:[a-z_]+/', $key, $expected);
                preg_match_all('/:[a-z_]+/', $text, $actual);
                $this->assertEqualsCanonicalizing($expected[0], $actual[0], "Placeholders differ for [$key]");
            }
        }
    }

    public function test_storefront_chrome_is_translated_under_each_prefix(): void
    {
        $this->get('/livraison')->assertOk()->assertSee('Mon espace');
        $this->get('/en/livraison')->assertOk()->assertSee('My account')->assertDontSee('Mon espace');
        $this->get('/es/livraison')->assertOk()->assertSee('Mi cuenta')->assertDontSee('Mon espace');
    }

    public function test_orders_default_to_french_and_helper_falls_back_for_unknown_locales(): void
    {
        $order = $this->order();
        $this->assertSame('fr', $order->fresh()->locale);

        $order->locale = 'ro';
        $this->assertSame('fr', OrderLocale::for($order));

        $order->locale = 'es';
        $this->assertSame('es', OrderLocale::for($order));
    }

    public function test_order_locale_helper_switches_translations_and_urls_and_restores_them(): void
    {
        $order = $this->order(['locale' => 'es']);

        $result = OrderLocale::run($order, fn () => [app()->getLocale(), route('order.status', ['orderNumber' => $order->order_number])]);

        $this->assertSame('es', $result[0]);
        $this->assertSame(url('/es/order/'.$order->order_number), $result[1]);
        $this->assertSame('fr', app()->getLocale());
        $this->assertSame(url('/order/'.$order->order_number), route('order.status', ['orderNumber' => $order->order_number]));
        $this->assertSame('fr', URL::getDefaultParameters()['locale']);
    }

    public function test_payment_email_is_sent_in_the_order_language(): void
    {
        Mail::fake();
        $order = $this->order(['locale' => 'es', 'payment_status' => 'paid', 'paid_at' => now()]);

        $this->artisan('orders:payment-emails')->assertSuccessful();

        Mail::assertSent(PaymentStatusMail::class, fn (PaymentStatusMail $mail): bool => $mail->hasTo('marie@example.com') && $mail->locale === 'es');

        app()->setLocale('es');
        $this->assertStringContainsString('Pago confirmado', (new PaymentStatusMail($order, 'paid'))->envelope()->subject);
    }

    private function order(array $attributes = []): Order
    {
        return Order::query()->create($attributes + [
            'access_token' => Str::random(48),
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'email' => 'marie@example.com',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [['product_id' => 1, 'name' => 'Gel', 'unit_price' => 12, 'quantity' => 1, 'line_total' => 12]],
            'subtotal' => 12,
            'delivery_cost' => 3,
            'total_amount' => 15,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }
}
