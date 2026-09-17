<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Helpers\SecurityHelper;
use App\Models\UserModel;
use App\Services\MicrosoftOAuth;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirect('/');
        }
        $oauth = new MicrosoftOAuth();
        $oauth_enabled = $oauth->isEnabled();
        $error = sanitize_input((string) ($_GET['error'] ?? ''));

        $this->render('auth/login', [
            'title' => 'Entrar',
            'oauth_enabled' => $oauth_enabled,
            'error' => $error,
        ], 'public_header');
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

    public function oauthStart(): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirect('/');
        }

        $oauth = new MicrosoftOAuth();
        if (!$oauth->isEnabled()) {
            Logger::office('Tentativa de login OAuth quando o serviço estava desabilitado.');
            $this->redirect('/login?error=oauth_disabled');
        }

        try {
            $url = $oauth->getAuthorizationUrl();
            Logger::office('Redirecionando usuário para autenticação na Microsoft.');
            $this->redirect($url);
        } catch (\Throwable $e) {
            Logger::office('Erro ao gerar URL de autorização OAuth: ' . $e->getMessage());
            $this->redirect('/login?error=oauth_init_error');
        }
    }

    public function oauthCallback(): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirect('/');
        }

        $oauth = new MicrosoftOAuth();
        if (!$oauth->isEnabled()) {
            Logger::office('Callback OAuth recebido mas serviço está desabilitado.');
            $this->redirect('/login?error=oauth_disabled');
        }

        $sessionState = (string) ($_SESSION['oauth2state'] ?? '');
        $requestState = (string) ($_GET['state'] ?? '');
        unset($_SESSION['oauth2state']);

        if ($sessionState === '' || $requestState === '' || !hash_equals($sessionState, $requestState)) {
            Logger::office('Falha de validação CSRF (state inválido ou ausente no callback OAuth).');
            $this->redirect('/login?error=invalid_state');
        }

        $code = (string) ($_GET['code'] ?? '');
        if ($code === '') {
            $oauthError = (string) ($_GET['error_description'] ?? $_GET['error'] ?? 'Código de autorização não recebido.');
            Logger::office('Erro retornado pela Microsoft no callback: ' . $oauthError);
            $this->redirect('/login?error=authorization_denied');
        }

        try {
            $tokenData = $oauth->fetchAccessToken($code);
            $accessToken = (string) ($tokenData['access_token'] ?? '');
            if ($accessToken === '') {
                throw new \RuntimeException('Token de acesso veio vazio da API Microsoft.');
            }

            $profile = $oauth->fetchUserProfile($accessToken);
            $email = (string) ($profile['mail'] ?? $profile['userPrincipalName'] ?? '');
            $email = mb_strtolower(trim($email));

            if ($email === '') {
                Logger::office('E-mail do usuário não encontrado no perfil do Microsoft Graph.');
                $this->redirect('/login?error=no_email');
            }

            $userModel = new UserModel();
            $user = $userModel->findByLogin($email);

            if ($user === null) {
                Logger::office('Login OAuth falhou: E-mail "' . $email . '" não está cadastrado na tabela cp_users.');
                $this->redirect('/login?error=user_not_found');
            }

            if ((string) ($user['status'] ?? '') !== 'ativo') {
                Logger::office('Login OAuth falhou: Usuário "' . $email . '" cadastrado, porém inativo.');
                $this->redirect('/login?error=user_inactive');
            }

            Auth::loginUser($user);
            unset($_SESSION['oauth2code_verifier']);

            Logger::log('oauth_login', 'Usuário entrou via Office 365 (' . $email . ')');
            Logger::office('Login SSO efetuado com sucesso para o usuário: ' . $email);

            $this->redirect('/');
        } catch (\Throwable $e) {
            Logger::office('Erro durante o processamento do callback OAuth: ' . $e->getMessage());
            $this->redirect('/login?error=oauth_process_error');
        }
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