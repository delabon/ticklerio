<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;

class HomePageTest extends FeatureTestCase
{
    public function testAccessesHomePageSuccessfully(): void
    {
        $response = $this->get('/');

        $this->assertSame(200, $response->getStatusCode());
    }
}
