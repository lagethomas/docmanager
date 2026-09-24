<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Logger;
use App\Models\AreaModel;

/**
 * Módulo Áreas / Setores: CRUD de áreas para atribuição a usuários e restrição de documentos.
 */
class AreaController extends Controller
{
    public function index(): void
    {
        $this->render('areas/index', [
            'title' => 'Gestão de Áreas / Setores — ' . SYSTEM_NAME,
            'user' => Auth::user(),
            'csrfToken' => CSRF::token(),
            'page' => 'areas',
        ], 'header');
    }

    /**
     * Retorna lista de áreas ativas (utilizada em selects de formulários).
     */
    public function areas(): void
    {
        $model = new AreaModel();
        $areas = array_map(static function (array $area): array {
            return [
                'id' => (int) $area['id'],
                'name' => (string) $area['name'],
                'description' => isset($area['description']) ? (string) $area['description'] : '',
                'status' => (string) $area['status'],
            ];
        }, $model->allActive());

        $this->jsonOk($areas);
    }

    /**
     * Retorna todas as áreas com estatísticas para a tabela de administração.
     */
    public function adminAreas(): void
    {
        $model = new AreaModel();
        $areas = array_map(static function (array $area): array {
            return [
                'id' => (int) $area['id'],
                'name' => (string) $area['name'],
                'description' => isset($area['description']) ? (string) $area['description'] : '',
                'status' => (string) $area['status'],
                'users_count' => (int) ($area['users_count'] ?? 0),
                'docs_count' => (int) ($area['docs_count'] ?? 0),
                'created_at' => (string) ($area['created_at'] ?? ''),
            ];
        }, $model->allWithStats());

        $this->jsonOk($areas);
    }

    public function show(string $id): void
    {
        $intId = (int) $id;
        $model = new AreaModel();
        $area = $model->find($intId);
        if ($area === null) {
            $this->jsonError('Área / Setor não encontrado.', 404);
            return;
        }

        $this->jsonOk([
            'id' => (int) $area['id'],
            'name' => (string) $area['name'],
            'description' => isset($area['description']) ? (string) $area['description'] : '',
            'status' => (string) $area['status'],
        ]);
    }

    public function store(): void
    {
        $input = self::input();
        $name = sanitize_input((string) ($input['name'] ?? ''));
        $description = sanitize_input((string) ($input['description'] ?? ''));
        $status = ($input['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

        if ($name === '') {
            $this->jsonError('O nome da área / setor é obrigatório.', 422);
            return;
        }

        $model = new AreaModel();
        if ($model->nameExists($name)) {
            $this->jsonError('Já existe uma área / setor com este nome.', 409);
            return;
        }

        $id = $model->create($name, $description !== '' ? $description : null, $status);
        Logger::log('create_area', "Área {$name} (ID: {$id}) criada");

        $this->jsonOk(['id' => $id, 'name' => $name], 201);
    }

    public function update(string $id): void
    {
        $intId = (int) $id;
        $model = new AreaModel();
        $area = $model->find($intId);
        if ($area === null) {
            $this->jsonError('Área / Setor não encontrado.', 404);
            return;
        }

        $input = self::input();
        $name = sanitize_input((string) ($input['name'] ?? $area['name']));
        $description = sanitize_input((string) ($input['description'] ?? ($area['description'] ?? '')));
        $status = ($input['status'] ?? $area['status']) === 'inativo' ? 'inativo' : 'ativo';

        if ($name === '') {
            $this->jsonError('O nome da área / setor não pode ficar em branco.', 422);
            return;
        }

        if ($model->nameExists($name, $intId)) {
            $this->jsonError('Já existe outra área / setor com este nome.', 409);
            return;
        }

        $model->update($intId, $name, $description !== '' ? $description : null, $status);
        Logger::log('edit_area', "Área {$name} (ID: {$intId}) atualizada");

        $this->jsonOk(['id' => $intId, 'name' => $name]);
    }

    public function delete(string $id): void
    {
        $intId = (int) $id;
        $model = new AreaModel();
        $area = $model->find($intId);
        if ($area === null) {
            $this->jsonError('Área / Setor não encontrado.', 404);
            return;
        }

        $model->delete($intId);
        Logger::log('delete_area', "Área " . ($area['name'] ?? $intId) . " excluída");

        $this->jsonOk(['id' => $intId]);
    }
}
