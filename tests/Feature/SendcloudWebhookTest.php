<?php

namespace Tests\Feature;

use Tests\TestCase;

class SendcloudWebhookTest extends TestCase
{
    public function test_connectivity_check_returns_ok(): void
    {
        $this->get('/api/webhooks/sendcloud')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_webhook_post_is_acknowledged(): void
    {
        $this->postJson('/api/webhooks/sendcloud', ['action' => 'test'])
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }
}
