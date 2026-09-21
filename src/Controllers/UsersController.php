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
            $username = (string) ($user['username'] ?? $user['login']);
            return [
                'username' => $username,
                'login' => $username,
                'email' => !empty($user['email']) ? (string) $user['email'] : null,
                'name' => (string) $user['name'],
                'role' => (string) $user['role'],
                'status' => (string) $user['status'],
                'location_id' => !empty($user['location_id']) ? (int) $user['location_id'] : null,
                'location_name' => !empty($user['location_name']) ? (string) $user['location_name'] : null,
                'area_id' => !empty($user['area_id']) ? (int) $user['area_id'] : null,
                'area_name' => !empty($user['area_name']) ? (string) $user['area_name'] : null,
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
        $username = sanitize_input((string) ($input['username'] ?? $input['login'] ?? ''));
        $email = sanitize_input((string) ($input['email'] ?? ''));
        $nome = sanitize_input((string) ($input['name'] ?? ''));
        $senha = (string) ($input['password'] ?? '');
        $role = trim((string) ($input['role'] ?? ''));
        $status = ($input['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';
        $locationId = !empty($input['location_id']) ? (int) $input['location_id'] : null;
        $areaId = !empty($input['area_id']) ? (int) $input['area_id'] : null;

        $roleModel = new RoleModel();
        if ($username === '' || $nome === '' || $senha === '' || !$roleModel->nameExists($role)) {
            $this->jsonError('Preencha usuário, nome, senha e um perfil válido.', 422);
            return;
        }

        $model = new UserModel();
        if ($model->usernameExists($username) || $model->loginExists($username)) {
            $this->jsonError('Já existe um usuário com esse username de login.', 409);
            return;
        }

        if ($email !== '' && $model->emailExists($email)) {
            $this->jsonError('O e-mail informado já está em uso por outro usuário.', 409);
            return;
        }

        $model->create($username, $nome, SecurityHelper::hashPassword($senha), $role, $status, $locationId, $email !== '' ? $email : null, $areaId);
        Logger::log('create_user', "Usuário {$username} criado com perfil {$role}");

        $this->jsonOk(['login' => mb_strtolower(trim($username)), 'username' => mb_strtolower(trim($username))], 201);
    }

    public function updateUser(string $login): void
    {
        $username = mb_strtolower(trim($login));
        $model = new UserModel();
        $user = $model->findByUsername($username) ?? $model->findByLogin($username);
        if ($user === null) {
            $this->jsonError('Usuário não encontrado.', 404);
            return;
        }

        $input = self::input();
        $nome = sanitize_input((string) ($input['name'] ?? $user['name']));
        $email = sanitize_input((string) ($input['email'] ?? $user['email'] ?? ''));
        $senha = (string) ($input['password'] ?? '');

        $isAdminPrincipal = $username === 'admin';
        $role = $isAdminPrincipal ? 'Admin' : trim((string) ($input['role'] ?? $user['role']));
        $status = $isAdminPrincipal ? 'ativo' : (($input['status'] ?? $user['status']) === 'inativo' ? 'inativo' : 'ativo');
        $locationId = array_key_exists('location_id', $input)
            ? (!empty($input['location_id']) ? (int) $input['location_id'] : null)
            : (!empty($user['location_id']) ? (int) $user['location_id'] : null);
        $areaId = array_key_exists('area_id', $input)
            ? (!empty($input['area_id']) ? (int) $input['area_id'] : null)
            : (!empty($user['area_id']) ? (int) $user['area_id'] : null);

        $roleModel = new RoleModel();
        if ($nome === '' || !$roleModel->nameExists($role)) {
            $this->jsonError('Dados de usuário inválidos ou perfil inexistente.', 422);
            return;
        }

        if ($email !== '' && $model->emailExists($email, $username)) {
            $this->jsonError('O e-mail informado já está em uso por outro usuário.', 409);
            return;
        }

        $hash = $senha !== '' ? SecurityHelper::hashPassword($senha) : null;
        $model->update($username, $nome, $role, $status, $hash, $locationId, $email !== '' ? $email : null, $areaId);
        Logger::log('edit_user', "Usuário {$username} atualizado");

        $this->jsonOk(['login' => $username, 'username' => $username]);
    }

    public function deleteUser(string $login): void
    {
        $username = mb_strtolower(trim($login));
        if ($username === 'admin') {
            $this->jsonError('Não é possível excluir o administrador principal.', 422);
            return;
        }

        $model = new UserModel();
        if ($model->findByUsername($username) === null && $model->findByLogin($username) === null) {
            $this->jsonError('Usuário não encontrado.', 404);
            return;
        }

        $model->delete($username);
        Logger::log('delete_user', "Usuário {$username} excluído");

        $this->jsonOk(['login' => $username, 'username' => $username]);
    }
}