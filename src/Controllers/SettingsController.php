<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Logger;
use App\Models\SettingsModel;

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->render('configuracoes/index', [
            'title' => 'Configuracoes — ' . SYSTEM_NAME,
            'user' => Auth::user(),
            'csrfToken' => CSRF::token(),
            'page' => 'configuracoes',
        ], 'header');
    }

    public function get(): void
    {
        $settings = (new SettingsModel())->allAsMap();
        $this->jsonOk($settings);
    }

    public function save(): void
    {
        $input = self::input();
        $model = new SettingsModel();

        $perPage = (int) ($input['per_page'] ?? 10);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 10;
        }

        $docMaxVersions = (int) ($input['doc_max_versions'] ?? 10);
        if ($docMaxVersions < 1 || $docMaxVersions > 100) {
            $docMaxVersions = 10;
        }

        $model->set('per_page', (string) $perPage);
        $model->set('doc_max_versions', (string) $docMaxVersions);

        Logger::log('edit_settings', "Configuracoes atualizadas: per_page={$perPage}, doc_max_versions={$docMaxVersions}");

        $this->jsonOk([
            'per_page' => $perPage,
            'doc_max_versions' => $docMaxVersions,
        ]);
    }
}
