<?php

namespace App\Controllers;

use App\Core\Http\Response;

class HomeController
{
    public function index(): Response
    {
        return new Response('Welcome to homepage!');
    }
}
