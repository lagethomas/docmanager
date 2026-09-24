<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Logger;
use App\Models\LocationModel;

/**
 * Módulo Localizações: CRUD de localizações para restrição geográfica de documentos.
 */
class LocationController extends Controller
{
    public function index(): void
    {
        $this->render('localizacoes/index', [
            'title' => 'Gestão de Localizações — ' . SYSTEM_NAME,
            'user' => Auth::user(),
            'csrfToken' => CSRF::token(),
            'page' => 'localizacoes',
        ], 'header');
    }

    /**
     * Retorna lista de localizações ativas (utilizada em selects de formulários).
     */
    public function locations(): void
    {
        $model = new LocationModel();
        $locations = array_map(static function (array $loc): array {
            return [
                'id' => (int) $loc['id'],
                'name' => (string) $loc['name'],
                'description' => isset($loc['description']) ? (string) $loc['description'] : '',
                'status' => (string) $loc['status'],
            ];
        }, $model->allActive());

        $this->jsonOk($locations);
    }

    /**
     * Retorna todas as localizações com estatísticas para a tabela de administração.
     */
    public function adminLocations(): void
    {
        $model = new LocationModel();
        $locations = array_map(static function (array $loc): array {
            return [
                'id' => (int) $loc['id'],
                'name' => (string) $loc['name'],
                'description' => isset($loc['description']) ? (string) $loc['description'] : '',
                'status' => (string) $loc['status'],
                'users_count' => (int) ($loc['users_count'] ?? 0),
                'docs_count' => (int) ($loc['docs_count'] ?? 0),
                'created_at' => (string) ($loc['created_at'] ?? ''),
            ];
        }, $model->allWithStats());

        $this->jsonOk($locations);
    }

    public function show(string $id): void
    {
        $intId = (int) $id;
        $model = new LocationModel();
        $loc = $model->find($intId);
        if ($loc === null) {
            $this->jsonError('Localização não encontrada.', 404);
            return;
        }

        $this->jsonOk([
            'id' => (int) $loc['id'],
            'name' => (string) $loc['name'],
            'description' => isset($loc['description']) ? (string) $loc['description'] : '',
            'status' => (string) $loc['status'],
        ]);
    }

    public function store(): void
    {
        $input = self::input();
        $name = sanitize_input((string) ($input['name'] ?? ''));
        $description = sanitize_input((string) ($input['description'] ?? ''));
        $status = ($input['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

        if ($name === '') {
            $this->jsonError('O nome da localização é obrigatório.', 422);
            return;
        }

        $model = new LocationModel();
        if ($model->nameExists($name)) {
            $this->jsonError('Já existe uma localização com este nome.', 409);
            return;
        }

        $id = $model->create($name, $description !== '' ? $description : null, $status);
        Logger::log('create_location', "Localização {$name} (ID: {$id}) criada");

        $this->jsonOk(['id' => $id, 'name' => $name], 201);
    }

    public function update(string $id): void
    {
        $intId = (int) $id;
        $model = new LocationModel();
        $loc = $model->find($intId);
        if ($loc === null) {
            $this->jsonError('Localização não encontrada.', 404);
            return;
        }

        $input = self::input();
        $name = sanitize_input((string) ($input['name'] ?? $loc['name']));
        $description = sanitize_input((string) ($input['description'] ?? ($loc['description'] ?? '')));
        $status = ($input['status'] ?? $loc['status']) === 'inativo' ? 'inativo' : 'ativo';

        if ($name === '') {
            $this->jsonError('O nome da localização não pode ficar em branco.', 422);
            return;
        }

        if ($model->nameExists($name, $intId)) {
            $this->jsonError('Já existe outra localização com este nome.', 409);
            return;
        }

        $model->update($intId, $name, $description !== '' ? $description : null, $status);
        Logger::log('edit_location', "Localização {$name} (ID: {$intId}) atualizada");

        $this->jsonOk(['id' => $intId, 'name' => $name]);
    }

    public function delete(string $id): void
    {
        $intId = (int) $id;
        $model = new LocationModel();
        $loc = $model->find($intId);
        if ($loc === null) {
            $this->jsonError('Localização não encontrada.', 404);
            return;
        }

        $model->delete($intId);
        Logger::log('delete_location', "Localização " . ($loc['name'] ?? $intId) . " excluída");

        $this->jsonOk(['id' => $intId]);
    }
}
