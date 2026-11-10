<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_docs_homepage_includes_the_signature_tutorial(): void
    {
        $this->app['env'] = 'local';

        $response = $this->get(route('scramble.docs.ui'));

        $response->assertOk();
        $response->assertSee('Store the secret key securely and never send it with a request.', false);
        $response->assertSee('How to build the signature', false);
        $response->assertSee('X-Client-ID', false);
        $response->assertSee('X-Nonce', false);
        $response->assertSee('X-Api-Signature', false);
        $response->assertSee('unique on each request to the same endpoint', false);
        $response->assertSee('rejected for one hour', false);
        $response->assertSee('The API signature has already been used.', false);
        $response->assertSee('api_signature', false);
        $response->assertSee('96c25359bf61c9c5745403c6da4bb1c2838f7d1d40dbadab865f274023378927', false);
    }
}
