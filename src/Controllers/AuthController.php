<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Helpers\SecurityHelper;
use App\Models\UserModel;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirect('/');
        }
        $this->render('auth/login', ['title' => 'Entrar'], 'public_header');
    }

    public function login(): void
    {
        $input = self::input();
        $login = sanitize_input((string) ($input['login'] ?? ''));
        $senha = (string) ($input['senha'] ?? '');

        $userModel = new UserModel();
        $user = $userModel->findByLogin($login);
        if ($user === null || (string) $user['status'] !== 'ativo' || !SecurityHelper::verifyPassword($senha, (string) $user['password'])) {
            Logger::log('login_failed', 'Tentativa de login falhou para: ' . ($login !== '' ? $login : '(vazio)'));
            $this->jsonError('Usuário ou senha inválidos.', 401);
            return;
        }

        if (SecurityHelper::passwordNeedsRehash((string) $user['password'])) {
            $userModel->updatePassword($login, SecurityHelper::hashPassword($senha));
        }

        Auth::loginUser($user);
        Logger::log('login', 'Usuário entrou no portal');
        $this->jsonOk(['user' => self::publicUser($user)]);
    }

    public function logout(): void
    {
        if (Auth::isLoggedIn()) {
            Logger::log('logout', 'Usuário saiu do portal');
        }
        Auth::logout();
        $this->jsonOk(['logout' => true]);
    }

    public function me(): void
    {
        $user = Auth::user();
        $this->jsonOk($user !== null ? self::publicUser($user) : null);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public static function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'login' => (string) $user['login'],
            'name' => (string) $user['name'],
            'role' => (string) $user['role'],
            'status' => (string) $user['status'],
        ];
    }
}