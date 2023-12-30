<?php

namespace App\Core\Utilities;

use App\Core\Http\Response;
use RuntimeException;

class View
{
    public static function load(string $path): Response
    {
        $path = str_replace('.', '/', $path);
        $path = str_replace('\\', '/', $path);
        $path = __DIR__ . '/../../../resources/views/' . $path . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException('View not found.');
        }

        ob_start();
        require_once $path;
        $content = ob_get_clean();

        return new Response($content);
    }
}