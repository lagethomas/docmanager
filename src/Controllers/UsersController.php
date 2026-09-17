<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Logger;
use App\Helpers\SecurityHelper;
use App\Models\RoleModel;
use App\Models\UserModel;

/**
 * Módulo Usuários (Administração): CRUD de usuários, somente Admin.
 */
class UsersController extends Controller
{
    public function index(): void
    {
        $this->render('usuarios/index', [
            'title' => 'Administração de Usuários — ' . SYSTEM_NAME,
            'user' => Auth::user(),
            'csrfToken' => CSRF::token(),
            'page' => 'usuarios',
        ], 'header');
    }

    public function users(): void
    {
        $users = array_map(static function (array $user): array {
            return [
                'login' => (string) $user['login'],
                'name' => (string) $user['name'],
                'role' => (string) $user['role'],
                'status' => (string) $user['status'],
            ];
        }, (new UserModel())->all());
        $this->jsonOk($users);
    }

    public function roles(): void
    {
        $roles = array_map(static function (array $role): array {
            return [
                'id' => (int) $role['id'],
                'name' => (string) $role['name'],
                'description' => isset($role['description']) ? (string) $role['description'] : null,
            ];
        }, (new RoleModel())->all('name', 'ASC'));
        $this->jsonOk($roles);
    }

    public function createUser(): void
    {
        $input = self::input();
        $login = sanitize_input((string) ($input['login'] ?? ''));
        $nome = sanitize_input((string) ($input['name'] ?? ''));
        $senha = (string) ($input['password'] ?? '');
        $role = trim((string) ($input['role'] ?? ''));
        $status = ($input['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

        $roleModel = new RoleModel();
        if ($login === '' || $nome === '' || $senha === '' || !$roleModel->nameExists($role)) {
            $this->jsonError('Preencha usuário, nome, senha e um perfil válido.', 422);
            return;
        }

        $model = new UserModel();
        if ($model->loginExists($login)) {
            $this->jsonError('Já existe um usuário com esse login.', 409);
            return;
        }

        $model->create($login, $nome, SecurityHelper::hashPassword($senha), $role, $status);
        Logger::log('create_user', "Usuário {$login} criado com perfil {$role}");

        $this->jsonOk(['login' => mb_strtolower(trim($login))], 201);
    }

    public function updateUser(string $login): void
    {
        $model = new UserModel();
        $user = $model->findByLogin($login);
        if ($user === null) {
            $this->jsonError('Usuário não encontrado.', 404);
            return;
        }

        $input = self::input();
        $nome = sanitize_input((string) ($input['name'] ?? $user['name']));
        $senha = (string) ($input['password'] ?? '');

        $isAdminPrincipal = $login === 'admin';
        $role = $isAdminPrincipal ? 'Admin' : trim((string) ($input['role'] ?? $user['role']));
        $status = $isAdminPrincipal ? 'ativo' : (($input['status'] ?? $user['status']) === 'inativo' ? 'inativo' : 'ativo');

        $roleModel = new RoleModel();
        if ($nome === '' || !$roleModel->nameExists($role)) {
            $this->jsonError('Dados de usuário inválidos ou perfil inexistente.', 422);
            return;
        }

        $hash = $senha !== '' ? SecurityHelper::hashPassword($senha) : null;
        $model->update($login, $nome, $role, $status, $hash);
        Logger::log('edit_user', "Usuário {$login} atualizado");

        $this->jsonOk(['login' => $login]);
    }

    public function deleteUser(string $login): void
    {
        if ($login === 'admin') {
            $this->jsonError('Não é possível excluir o administrador principal.', 422);
            return;
        }

        $model = new UserModel();
        if ($model->findByLogin($login) === null) {
            $this->jsonError('Usuário não encontrado.', 404);
            return;
        }

        $model->delete($login);
        Logger::log('delete_user', "Usuário {$login} excluído");

        $this->jsonOk(['login' => $login]);
    }
}