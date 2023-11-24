<?php

namespace Tests\Feature;

use App\Core\Http\HttpStatusCode;
use Tests\FeatureTestCase;

class NotFound404Test extends FeatureTestCase
{
    public function test404NotFound(): void
    {
        $response = $this->get(
            '/65z4eaz6aze-' . uniqid() . '-98999-azeze-xsdqsd5411-z5er5ezr/',
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
    }
}
