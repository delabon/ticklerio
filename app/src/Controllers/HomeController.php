<?php

namespace App\Controllers;

use App\Core\Http\Response;
use App\Core\Utilities\View;

class HomeController
{
    public function index(): Response
    {
        return View::load('home');
    }
}
