<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Engine de views: resolve Views/pages/<modulo>/<view>.php e layouts.
 * PHP puro, sem template engine.
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = null): void
    {
        $viewFile = ROOT_DIR . '/src/Views/pages/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);

        if ($layout === null) {
            include $viewFile;
            return;
        }

        $layoutFile = ROOT_DIR . '/src/Views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout não encontrado: {$layout}");
        }

        ob_start();
        include $viewFile;
        $content = (string) ob_get_clean();
        include $layoutFile;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function component(string $name, array $data = []): void
    {
        $file = ROOT_DIR . '/src/Views/components/' . $name . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Component não encontrado: {$name}");
        }
        extract($data, EXTR_SKIP);
        include $file;
    }
}