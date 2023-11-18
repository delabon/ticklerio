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

}
