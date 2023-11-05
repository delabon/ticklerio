<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class FeatureTest extends TestCase
{
    private Client $http;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->http = new Client([
            'base_uri' => $_ENV['BASE_URL']
        ]);
    }

    public function testHttpRequestSuccessfully(): void
    {
        $response = $this->http->request('GET');

        $this->assertSame(200, $response->getStatusCode());
    }
}
