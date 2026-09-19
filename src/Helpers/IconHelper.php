<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper de ícones centralizado para o backend (PHP Views).
 * Mantém catálogo sincronizado com o frontend (icons.js).
 */
final class IconHelper
{
    /**
     * @var array<string, array{type: string, faClass?: string, defaultSize?: int, viewBox?: string, body?: string}>
     */
    private static array $registry = [
        'share' => ['type' => 'fa', 'faClass' => 'fa-solid fa-share-nodes', 'defaultSize' => 13],
        'key' => ['type' => 'fa', 'faClass' => 'fa-solid fa-key', 'defaultSize' => 13],
        'pencil' => ['type' => 'fa', 'faClass' => 'fa-regular fa-pen-to-square', 'defaultSize' => 14],
        'edit' => ['type' => 'fa', 'faClass' => 'fa-regular fa-pen-to-square', 'defaultSize' => 14],
        'trash' => ['type' => 'fa', 'faClass' => 'fa-regular fa-trash-can', 'defaultSize' => 14],
        'delete' => ['type' => 'fa', 'faClass' => 'fa-regular fa-trash-can', 'defaultSize' => 14],
        'copy' => ['type' => 'fa', 'faClass' => 'fa-regular fa-copy', 'defaultSize' => 13],
        'check' => ['type' => 'fa', 'faClass' => 'fa-solid fa-check', 'defaultSize' => 13],
        'plus' => ['type' => 'fa', 'faClass' => 'fa-solid fa-plus', 'defaultSize' => 13],
        'add' => ['type' => 'fa', 'faClass' => 'fa-solid fa-plus', 'defaultSize' => 13],
        'eye' => ['type' => 'fa', 'faClass' => 'fa-regular fa-eye', 'defaultSize' => 13],
        'view' => ['type' => 'fa', 'faClass' => 'fa-regular fa-eye', 'defaultSize' => 13],
        'restore' => ['type' => 'fa', 'faClass' => 'fa-solid fa-rotate-left', 'defaultSize' => 13],
        'forceDelete' => ['type' => 'fa', 'faClass' => 'fa-regular fa-trash-can', 'defaultSize' => 13],
        'duplicate' => ['type' => 'fa', 'faClass' => 'fa-regular fa-clone', 'defaultSize' => 13],
        'diff' => ['type' => 'fa', 'faClass' => 'fa-solid fa-code-compare', 'defaultSize' => 13],
        'star' => ['type' => 'fa', 'faClass' => 'fa-regular fa-star', 'defaultSize' => 13],
        'starSolid' => ['type' => 'fa', 'faClass' => 'fa-solid fa-star', 'defaultSize' => 13],
        'starOutline' => ['type' => 'fa', 'faClass' => 'fa-regular fa-star', 'defaultSize' => 13],
        'clock' => ['type' => 'fa', 'faClass' => 'fa-regular fa-clock', 'defaultSize' => 12],
        'checkCircle' => ['type' => 'fa', 'faClass' => 'fa-regular fa-circle-check', 'defaultSize' => 13],
        'xCircle' => ['type' => 'fa', 'faClass' => 'fa-regular fa-circle-xmark', 'defaultSize' => 13],
        'print' => ['type' => 'fa', 'faClass' => 'fa-solid fa-print', 'defaultSize' => 13],
        'pdf' => ['type' => 'fa', 'faClass' => 'fa-solid fa-file-pdf', 'defaultSize' => 13],
        'comment' => ['type' => 'fa', 'faClass' => 'fa-regular fa-comment', 'defaultSize' => 13],
        'comments' => ['type' => 'fa', 'faClass' => 'fa-regular fa-comments', 'defaultSize' => 13],
        'preview' => ['type' => 'fa', 'faClass' => 'fa-regular fa-file-lines', 'defaultSize' => 13],
        'search' => ['type' => 'fa', 'faClass' => 'fa-solid fa-magnifying-glass', 'defaultSize' => 13],
        'filter' => ['type' => 'fa', 'faClass' => 'fa-solid fa-filter', 'defaultSize' => 13],
        'download' => ['type' => 'fa', 'faClass' => 'fa-solid fa-download', 'defaultSize' => 13],
        'upload' => ['type' => 'fa', 'faClass' => 'fa-solid fa-upload', 'defaultSize' => 13],
        'close' => ['type' => 'fa', 'faClass' => 'fa-solid fa-xmark', 'defaultSize' => 14],
        'location' => ['type' => 'fa', 'faClass' => 'fa-solid fa-location-dot', 'defaultSize' => 14],
        'pin' => ['type' => 'fa', 'faClass' => 'fa-solid fa-location-dot', 'defaultSize' => 14],
        'folder' => ['type' => 'fa', 'faClass' => 'fa-regular fa-folder', 'defaultSize' => 14],
        'doc' => ['type' => 'fa', 'faClass' => 'fa-regular fa-file-lines', 'defaultSize' => 14],
        'file' => ['type' => 'fa', 'faClass' => 'fa-regular fa-file-lines', 'defaultSize' => 14],
        'favorite' => ['type' => 'fa', 'faClass' => 'fa-solid fa-star', 'defaultSize' => 13],
        'user' => ['type' => 'fa', 'faClass' => 'fa-regular fa-user', 'defaultSize' => 13],
        'users' => ['type' => 'fa', 'faClass' => 'fa-solid fa-users', 'defaultSize' => 14],
        'bell' => ['type' => 'fa', 'faClass' => 'fa-regular fa-bell', 'defaultSize' => 14],
        'settings' => ['type' => 'fa', 'faClass' => 'fa-solid fa-gear', 'defaultSize' => 14],
        'tag' => ['type' => 'fa', 'faClass' => 'fa-solid fa-tag', 'defaultSize' => 13],
        'history' => ['type' => 'fa', 'faClass' => 'fa-solid fa-clock-rotate-left', 'defaultSize' => 13],
        'lock' => ['type' => 'fa', 'faClass' => 'fa-solid fa-lock', 'defaultSize' => 13],
        'auth' => ['type' => 'fa', 'faClass' => 'fa-solid fa-shield-halved', 'defaultSize' => 14],
        'compute' => ['type' => 'fa', 'faClass' => 'fa-solid fa-microchip', 'defaultSize' => 14],
        'database' => ['type' => 'fa', 'faClass' => 'fa-solid fa-database', 'defaultSize' => 14],
        'network' => ['type' => 'fa', 'faClass' => 'fa-solid fa-network-wired', 'defaultSize' => 14],
        'cloud' => ['type' => 'fa', 'faClass' => 'fa-solid fa-cloud', 'defaultSize' => 14],
        'server' => ['type' => 'fa', 'faClass' => 'fa-solid fa-server', 'defaultSize' => 14],
        'code' => ['type' => 'fa', 'faClass' => 'fa-solid fa-code', 'defaultSize' => 14],
        'terminal' => ['type' => 'fa', 'faClass' => 'fa-solid fa-terminal', 'defaultSize' => 14],
        'android' => ['type' => 'fa', 'faClass' => 'fa-brands fa-android', 'defaultSize' => 14],
        'apple' => ['type' => 'fa', 'faClass' => 'fa-brands fa-apple', 'defaultSize' => 14],
        'book' => ['type' => 'fa', 'faClass' => 'fa-solid fa-book', 'defaultSize' => 14],
        'archive' => ['type' => 'fa', 'faClass' => 'fa-solid fa-box-archive', 'defaultSize' => 14],
        'devops' => ['type' => 'fa', 'faClass' => 'fa-solid fa-gears', 'defaultSize' => 14],
        'db' => ['type' => 'fa', 'faClass' => 'fa-solid fa-database', 'defaultSize' => 14],
        'tool' => ['type' => 'fa', 'faClass' => 'fa-solid fa-wrench', 'defaultSize' => 14],
        'idea' => ['type' => 'fa', 'faClass' => 'fa-solid fa-lightbulb', 'defaultSize' => 14],
        'goal' => ['type' => 'fa', 'faClass' => 'fa-solid fa-bullseye', 'defaultSize' => 14],
        'rocket' => ['type' => 'fa', 'faClass' => 'fa-solid fa-rocket', 'defaultSize' => 14],
        'docker' => ['type' => 'fa', 'faClass' => 'fa-brands fa-docker', 'defaultSize' => 14],
        'workflow' => ['type' => 'fa', 'faClass' => 'fa-solid fa-diagram-project', 'defaultSize' => 14],
        'kanban' => ['type' => 'fa', 'faClass' => 'fa-solid fa-table-columns', 'defaultSize' => 14],
        'robot' => ['type' => 'fa', 'faClass' => 'fa-solid fa-robot', 'defaultSize' => 14],
        'truck' => ['type' => 'fa', 'faClass' => 'fa-solid fa-truck', 'defaultSize' => 14],
        'calendar' => ['type' => 'fa', 'faClass' => 'fa-solid fa-calendar', 'defaultSize' => 14],
        'chart' => ['type' => 'fa', 'faClass' => 'fa-solid fa-chart-line', 'defaultSize' => 14],
        'mail' => ['type' => 'fa', 'faClass' => 'fa-solid fa-envelope', 'defaultSize' => 14],
        'home' => ['type' => 'fa', 'faClass' => 'fa-solid fa-house', 'defaultSize' => 14],
        'shield' => ['type' => 'fa', 'faClass' => 'fa-solid fa-shield-halved', 'defaultSize' => 14],
        'link' => ['type' => 'fa', 'faClass' => 'fa-solid fa-link', 'defaultSize' => 14],
        'linux' => ['type' => 'fa', 'faClass' => 'fa-brands fa-linux', 'defaultSize' => 14],
        'windows' => ['type' => 'fa', 'faClass' => 'fa-brands fa-windows', 'defaultSize' => 14],
    ];

    /**
     * Retorna o HTML de um ícone a partir do nome cadastrado.
     *
     * @param string $name Nome do ícone
     * @param array{size?: int|string, color?: string, class?: string, style?: string, title?: string, variant?: string} $options
     */
    public static function get(string $name, array $options = []): string
    {
        $def = self::$registry[$name] ?? ['type' => 'fa', 'faClass' => 'fa-solid fa-circle-question', 'defaultSize' => 14];

        $size = $options['size'] ?? ($def['defaultSize'] ?? 14);
        $sizePx = is_numeric($size) ? ($size . 'px') : (string) $size;

        $styleParts = [];
        if ($sizePx !== '') {
            $styleParts[] = 'font-size:' . $sizePx;
        }
        if (!empty($options['color'])) {
            $styleParts[] = 'color:' . $options['color'];
        }
        if (!empty($options['style'])) {
            $styleParts[] = $options['style'];
        }

        $styleAttr = !empty($styleParts) ? (' style="' . implode(';', $styleParts) . '"') : '';
        $titleAttr = !empty($options['title']) ? (' title="' . e($options['title']) . '"') : '';

        if ($def['type'] === 'fa') {
            $cls = $def['faClass'] ?? 'fa-solid fa-circle-question';
            if (($options['variant'] ?? '') === 'regular') {
                $cls = str_replace('fa-solid', 'fa-regular', $cls);
            } elseif (($options['variant'] ?? '') === 'solid') {
                $cls = str_replace('fa-regular', 'fa-solid', $cls);
            }
            if (!empty($options['class'])) {
                $cls .= ' ' . $options['class'];
            }
            return '<i class="' . e($cls) . '"' . $styleAttr . $titleAttr . '></i>';
        }

        if ($def['type'] === 'svg') {
            $viewBox = $def['viewBox'] ?? '0 0 24 24';
            $w = is_numeric($size) ? (string) $size : '18';
            $h = is_numeric($size) ? (string) $size : '18';
            $cls = !empty($options['class']) ? (' class="' . e($options['class']) . '"') : '';
            $body = $def['body'] ?? '';
            return '<svg viewBox="' . e($viewBox) . '" width="' . e($w) . '" height="' . e($h) . '" fill="none" stroke="currentColor" stroke-width="2"' . $cls . $styleAttr . $titleAttr . '>' . $body . '</svg>';
        }

        return '';
    }

    /**
     * @param array{type: string, faClass?: string, defaultSize?: int, viewBox?: string, body?: string} $definition
     */
    public static function register(string $name, array $definition): void
    {
        self::$registry[$name] = $definition;
    }

    public static function has(string $name): bool
    {
        return isset(self::$registry[$name]);
    }

    /**
     * @return array<string, array{type: string, faClass?: string, defaultSize?: int, viewBox?: string, body?: string}>
     */
    public static function all(): array
    {
        return self::$registry;
    }
}
