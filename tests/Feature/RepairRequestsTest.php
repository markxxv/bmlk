<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RepairRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.chat_id', '-1001234567890');
    }

    public function test_page_has_both_modes_and_subscription_prices(): void
    {
        $this->get(route('reparation'))
            ->assertOk()
            ->assertSee('Réparation')
            ->assertSee('Diagnostic gratuit avant paiement')
            ->assertSee('119 €')
            ->assertSee('199 €')
            ->assertSee('349 €')
            ->assertSee(route('reparation.submit'), false);
    }

    public function test_diagnostic_is_sent_immediately_with_original_photo_and_without_queue(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);
        Queue::fake();

        $this->post(route('reparation.submit'), [
            'mode' => 'diagnostic',
            'tool' => 'scissors',
            'description' => 'La pointe des ciseaux est abîmée.',
            'photos' => [UploadedFile::fake()->image('ciseaux.jpg')],
            'email' => 'client@example.com',
            'consent' => '1',
        ])->assertRedirect(route('reparation'))->assertSessionHas('success');

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) =>
            str_ends_with($request->url(), '/sendMessage')
            && str_contains($request['text'], 'La pointe des ciseaux')
            && str_contains($request['text'], 'client@example.com')
        );
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendDocument'));
        Queue::assertNothingPushed();
    }

    public function test_sharpening_request_sends_selected_quantities_and_total(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->post(route('reparation.submit'), [
            'mode' => 'sharpening',
            'scissors' => 3,
            'pliers' => 2,
            'pushers' => 2,
            'email' => 'client@example.com',
            'consent' => '1',
        ])->assertRedirect(route('reparation'))->assertSessionHas('success');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) =>
            str_contains($request['text'], 'Ciseaux : 3')
            && str_contains($request['text'], 'Total indicatif : 68 €')
        );
    }

    public function test_subscription_sends_request_without_creating_payment(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->post(route('reparation.submit'), [
            'mode' => 'subscription',
            'plan' => 'master',
            'email' => 'client@example.com',
            'consent' => '1',
        ])->assertRedirect(route('reparation'))->assertSessionHas('success');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) =>
            str_contains($request['text'], 'Master')
            && str_contains($request['text'], '199 € / an')
        );
    }

    public function test_request_rejects_empty_tool_selection_and_missing_consent(): void
    {
        Http::fake();

        $this->post(route('reparation.submit'), [
            'mode' => 'sharpening',
            'scissors' => 0,
            'pliers' => 0,
            'pushers' => 0,
            'email' => 'client@example.com',
            'consent' => '1',
        ])->assertSessionHasErrors('scissors');

        $this->post(route('reparation.submit'), [
            'mode' => 'subscription',
            'plan' => 'start',
            'email' => 'client@example.com',
        ])->assertSessionHasErrors('consent');

        Http::assertNothingSent();
    }

    public function test_failed_telegram_request_is_not_reported_as_success(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => false], 500)]);

        $this->from(route('reparation'))->post(route('reparation.submit'), [
            'mode' => 'subscription',
            'plan' => 'salon',
            'email' => 'client@example.com',
            'consent' => '1',
        ])->assertRedirect(route('reparation'))->assertSessionHasErrors('request')->assertSessionMissing('success');
    }
}
