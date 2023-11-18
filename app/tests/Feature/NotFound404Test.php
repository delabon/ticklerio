<?php

namespace Tests\Feature;

use GuzzleHttp\Exception\GuzzleException;
use Tests\FeatureTestCase;

class NotFound404Test extends FeatureTestCase
{
    public function test404NotFound(): void
    {
        $errorCode = 1;

        try {
            $this->http->request('GET', '/65z4eaz6aze-' . uniqid() . '-98999-azeze-xsdqsd5411-z5er5ezr/');
        } catch (GuzzleException $e) {
            $errorCode = $e->getCode();
        }

        $this->assertSame(404, $errorCode);
    }
}
