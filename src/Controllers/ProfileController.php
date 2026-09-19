<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Logger;
use App\Helpers\SecurityHelper;
use App\Models\UserModel;

/**
 * Controller responsável pelo perfil do usuário autênticado.
 */
class ProfileController extends Controller
{
    public function index(): void
    {
        $username = Auth::username() ?? Auth::login();
        if ($username === null) {
            $this->redirect('/login');
            return;
        }

        $userModel = new UserModel();
        $userData = $userModel->findByUsername($username) ?? $userModel->findByLogin($username);

        $this->render('perfil/index', [
            'title' => 'Meu Perfil — ' . SYSTEM_NAME,
            'user' => Auth::user(),
            'profileUser' => $userData,
            'csrfToken' => CSRF::token(),
            'page' => 'perfil',
        ], 'header');
    }

    public function update(): void
    {
        $username = Auth::username() ?? Auth::login();
        if ($username === null) {
            $this->jsonError('Não autenticado.', 401);
            return;
        }

        $input = self::input();
        $name = sanitize_input((string) ($input['name'] ?? ''));
        $email = sanitize_input((string) ($input['email'] ?? ''));
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['confirm_password'] ?? '');

        if ($name === '') {
            $this->jsonError('O campo nome é obrigatório.', 422);
            return;
        }

        $userModel = new UserModel();
        $userData = $userModel->findByUsername($username) ?? $userModel->findByLogin($username);
        if ($userData === null) {
            $this->jsonError('Usuário não encontrado.', 404);
            return;
        }

        if ($email !== '' && $userModel->emailExists($email, $username)) {
            $this->jsonError('O e-mail informado já está em uso por outro usuário.', 409);
            return;
        }

        $newHash = null;
        if ($newPassword !== '') {
            if (mb_strlen($newPassword) < 6) {
                $this->jsonError('A nova senha deve ter no mínimo 6 caracteres.', 422);
                return;
            }
            if ($newPassword !== $confirmPassword) {
                $this->jsonError('A confirmação de senha não confere.', 422);
                return;
            }
            $newHash = SecurityHelper::hashPassword($newPassword);
        }

        $success = $userModel->updateProfile($username, $name, $email !== '' ? $email : null, $newHash);
        if (!$success) {
            $this->jsonError('Falha ao atualizar perfil.', 500);
            return;
        }

        $updatedUser = $userModel->findByUsername($username) ?? $userModel->findByLogin($username);
        if ($updatedUser !== null) {
            Auth::loginUser($updatedUser);
        }

        Logger::log('profile_update', "Perfil do usuário {$username} atualizado com sucesso");

        $this->jsonOk([
            'message' => 'Perfil atualizado com sucesso!',
            'user' => Auth::user(),
        ]);
    }
}
