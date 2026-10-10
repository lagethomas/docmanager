<?php
declare(strict_types=1);

/**
 * Script CLI para compilar e minificar assets (CSS & JS) em um único bundle unificado.
 * Uso: php bin/build_assets.php
 */

define('CLI_MODE', true);
require_once __DIR__ . '/../src/bootstrap.php';

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;

echo "[BUILD ASSETS] Iniciando compilação e minificação de assets..." . PHP_EOL;

$cssDir = ROOT_DIR . '/public/assets/css';
$jsSrcDir = ROOT_DIR . '/public/assets/js/src';

// ─── 1. COMPILAÇÃO DE CSS ─────────────────────────────────────────────
$cssFiles = [
    $cssDir . '/gentelella.css',
    $cssDir . '/modules/table.min.css',
    $cssDir . '/modules/docs.min.css',
    $cssDir . '/modules/reports.min.css',
];

$minifierCss = new CSS();

foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        echo "  [CSS] Adicionando: " . basename($file) . PHP_EOL;
        $minifierCss->add($file);
    } else {
        echo "  [AVISO] Arquivo CSS não encontrado: " . $file . PHP_EOL;
    }
}

$outputCssFile = $cssDir . '/utils.min.css';
$minifierCss->minify($outputCssFile);
$cssHash = substr(md5_file($outputCssFile), 0, 8);

$cssSizeKb = round(filesize($outputCssFile) / 1024, 2);
echo "  ✓ [CSS] Gerado {$outputCssFile} ({$cssSizeKb} KB) | Hash: {$cssHash}" . PHP_EOL;

// ─── 2. COMPILAÇÃO / MINIFICAÇÃO DE JS ──────────────────────────────
$outputJsDir = ROOT_DIR . '/public/assets/js';
if (!is_dir($outputJsDir)) {
    mkdir($outputJsDir, 0755, true);
}

// Bundle comum de scripts principais
$commonJsFiles = [
    $jsSrcDir . '/icons.js',
    $jsSrcDir . '/common.js',
    $jsSrcDir . '/searchable_select.js',
    $jsSrcDir . '/diff.js',
];

$minifierJs = new JS();
foreach ($commonJsFiles as $jsFile) {
    if (file_exists($jsFile)) {
        echo "  [JS BUNDLE] Adicionando: " . basename($jsFile) . PHP_EOL;
        $minifierJs->add($jsFile);
    }
}
$outputJsBundle = $outputJsDir . '/app.min.js';
$minifierJs->minify($outputJsBundle);
$jsHash = substr(md5_file($outputJsBundle), 0, 8);

$jsSizeKb = round(filesize($outputJsBundle) / 1024, 2);
echo "  ✓ [JS BUNDLE] Gerado {$outputJsBundle} ({$jsSizeKb} KB) | Hash: {$jsHash}" . PHP_EOL;

// Minificar scripts de páginas individuais (docs.js, usuarios.js, etc.)
if (is_dir($jsSrcDir)) {
    $srcFiles = glob($jsSrcDir . '/*.js');
    foreach ($srcFiles as $srcFile) {
        $baseName = basename($srcFile);
        $minJs = new JS($srcFile);
        $distFile = $outputJsDir . '/' . str_replace('.js', '.min.js', $baseName);
        $minJs->minify($distFile);
        $h = substr(md5_file($distFile), 0, 8);
        echo "  ✓ [JS PAGE] Minificado " . $baseName . " -> " . basename($distFile) . " | Hash: " . $h . PHP_EOL;
    }
}

// ─── 3. ATUALIZAÇÃO DO MANIFEST.JSON ───────────────────────────────
$manifestFile = $cssDir . '/manifest.json';
$manifest = [];

if (file_exists($manifestFile)) {
    $decoded = json_decode((string) file_get_contents($manifestFile), true);
    if (is_array($decoded)) {
        $manifest = $decoded;
    }
}

$manifest['utils.min.css'] = $cssHash;
$manifest['app.min.js'] = $jsHash;

// Atualizar hashes individuais dos arquivos fonte
foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        $relPath = preg_replace('#^' . preg_quote($cssDir . '/', '#') . '#', '', $file);
        $manifest[$relPath] = substr(md5_file($file), 0, 8);
    }
}

file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
echo "  ✓ [MANIFEST] Manifest atualizado em: {$manifestFile}" . PHP_EOL;

echo "[BUILD ASSETS] Concluído com sucesso!" . PHP_EOL;
