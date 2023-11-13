<?php

namespace Tests\Feature;

use Tests\AppTestCase;

class HomePageTest extends AppTestCase
{
    public function testHttpRequestSuccessfully(): void
    {
        $response = $this->http->request('GET', '/');

        $this->assertSame(200, $response->getStatusCode());
    }
}
