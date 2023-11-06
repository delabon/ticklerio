<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;

class FeatureTest extends FeatureTestCase
{
    public function testHttpRequestSuccessfully(): void
    {
        $response = $this->http->request('GET');

        $this->assertSame(200, $response->getStatusCode());
    }
}
