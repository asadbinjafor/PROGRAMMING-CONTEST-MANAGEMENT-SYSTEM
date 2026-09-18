<?php
declare(strict_types=1);

namespace PCMS\Support;

final class View
{
    public static function render(string $template, array $viewData = [], string $layout = 'layouts/app'): never
    {
        $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR;
        $templateFile = $base . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';
        $layoutFile = $base . str_replace('/', DIRECTORY_SEPARATOR, $layout) . '.php';
        if (!is_file($templateFile) || !is_file($layoutFile)) {
            http_response_code(500);
            exit('View not found.');
        }
        extract($viewData, EXTR_SKIP);
        ob_start();
        require $templateFile;
        $content = ob_get_clean();
        $flashes = Flash::consume();
        require $layoutFile;
        exit;
    }
}
