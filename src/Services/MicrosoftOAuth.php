<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class MicrosoftOAuth
{
    private string $clientId;
    private string $clientSecret;
    private string $tenantId;
    private string $redirectUri;

    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $tenantId = null,
        ?string $redirectUri = null
    ) {
        $this->clientId = trim($clientId ?? (string) ($_ENV['OAUTH_CLIENT_ID'] ?? ''));
        $this->clientSecret = trim($clientSecret ?? (string) ($_ENV['OAUTH_CLIENT_SECRET'] ?? ''));
        $this->tenantId = trim($tenantId ?? (string) ($_ENV['OAUTH_TENANT_ID'] ?? 'common'));
        if ($this->tenantId === '') {
            $this->tenantId = 'common';
        }
        $this->redirectUri = trim($redirectUri ?? (string) ($_ENV['OAUTH_REDIRECT_URI'] ?? ''));
    }

    public function isEnabled(): bool
    {
        return $this->clientId !== '' && $this->redirectUri !== '';
    }

    public function getAuthorizationUrl(): string
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Integração Office 365 (OAuth) não está configurada.');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth2state'] = $state;

        $codeVerifier = $this->base64UrlEncode(random_bytes(32));
        $_SESSION['oauth2code_verifier'] = $codeVerifier;
        $codeChallenge = $this->base64UrlEncode(hash('sha256', $codeVerifier, true));

        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?%s',
            urlencode($this->tenantId),
            http_build_query($params)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAccessToken(string $code): array
    {
        $tokenUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', urlencode($this->tenantId));

        $params = [
            'client_id' => $this->clientId,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'openid profile email User.Read',
        ];

        if ($this->clientSecret !== '') {
            $params['client_secret'] = $this->clientSecret;
        }

        if (!empty($_SESSION['oauth2code_verifier'])) {
            $params['code_verifier'] = (string) $_SESSION['oauth2code_verifier'];
        }

        $ch = curl_init($tokenUrl);
        if ($ch === false) {
            throw new RuntimeException('Não foi possível inicializar o cURL para obtenção do token OAuth.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            throw new RuntimeException('Erro de comunicação cURL ao obter token OAuth: ' . $curlError);
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            throw new RuntimeException('Resposta inválida do servidor Microsoft OAuth.');
        }

        if ($httpCode >= 400 || isset($data['error'])) {
            $errorMsg = (string) ($data['error_description'] ?? $data['error'] ?? 'Erro ao obter access token');
            throw new RuntimeException($errorMsg);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchUserProfile(string $accessToken): array
    {
        $graphUrl = 'https://graph.microsoft.com/v1.0/me';

        $ch = curl_init($graphUrl);
        if ($ch === false) {
            throw new RuntimeException('Não foi possível inicializar o cURL para consulta do perfil do usuário.');
        }

        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            throw new RuntimeException('Erro de comunicação cURL ao consultar perfil Microsoft Graph: ' . $curlError);
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            throw new RuntimeException('Resposta inválida do servidor Microsoft Graph.');
        }

        if ($httpCode >= 400 || isset($data['error'])) {
            $errorMsg = is_array($data['error'] ?? null)
                ? (string) ($data['error']['message'] ?? 'Erro ao consultar Graph API')
                : (string) ($data['error'] ?? 'Erro ao consultar Graph API');
            throw new RuntimeException($errorMsg);
        }

        return $data;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
