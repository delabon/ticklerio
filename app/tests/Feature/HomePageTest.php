<?php

namespace Tests\Feature;

use GuzzleHttp\Exception\GuzzleException;
use Tests\FeatureTestCase;

class HomePageTest extends FeatureTestCase
{
    public function testHttpRequestSuccessfully(): void
    {
        $response = $this->http->request('GET', '/');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test404NotFound(): void
    {
        try {
            $this->http->request('GET', '/65z4eaz6aze-' . uniqid() . '-98999-azeze-xsdqsd5411-z5er5ezr/');
            $errorCode = 1;
        } catch (GuzzleException $e) {
            $errorCode = $e->getCode();
        }

        $this->assertSame(404, $errorCode);
    }
}
