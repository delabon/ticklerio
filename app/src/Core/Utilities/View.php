<?php

namespace App\Core\Utilities;

use App\Core\Http\Response;
use RuntimeException;

class View
{
    /**
     * @param string $path
     * @param array<string, mixed> $params
     * @return Response
     */
    public static function load(string $path, array $params = []): Response
    {
        $path = str_replace('.', '/', $path);
        $path = str_replace('\\', '/', $path);
        $path = __DIR__ . '/../../../resources/views/' . $path . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException('View not found.');
        }

        ob_start();
        extract($params);
        require $path;
        $content = ob_get_clean();

        return new Response($content);
    }
}
