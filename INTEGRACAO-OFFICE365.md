# Integração Office 365 (Microsoft Entra ID) — SSO

Guia completo para habilitar o login "Entrar com Office 365" na tela de autenticação do painel Kanban.

---

## Sumário

1. [Visão geral do fluxo](#1-visão-geral-do-fluxo)
2. [Pré-requisitos](#2-pré-requisitos)
3. [Criar o App Registration no Microsoft Entra ID](#3-criar-o-app-registration-no-microsoft-entra-id)
4. [Configurar o Redirect URI](#4-configurar-o-redirect-uri)
5. [Configurar as permissões (API Permissions)](#5-configurar-as-permissões-api-permissions)
6. [Gerar o Client Secret (ou usar PKCE)](#6-gerar-o-client-secret-ou-usar-pkce)
7. [Criar as variáveis no .env](#7-criar-as-variáveis-no-env)
8. [Habilitar o botão na tela de login](#8-habilitar-o-botão-na-tela-de-login)
9. [Testar o login](#9-testar-o-login)
10. [Resolução de problemas](#10-resolução-de-problemas)

---

## 1. Visão geral do fluxo

O painel utiliza **OAuth 2.0 Authorization Code** com suporte a **PKCE** (Proof Key for Code Exchange) para autenticar usuários via Microsoft.

**Fluxo passo a passo:**

```
Usuário clica "Entrar com Office 365"
        │
        ▼
Painel redireciona para login.microsoftonline.com
        │
        ▼
Microsoft autentica o usuário (login + senha + MFA se aplicável)
        │
        ▼
Microsoft redireciona de volta para /login/oauth/callback?code=XXX
        │
        ▼
Painel troca o código por um access_token via API Microsoft
        │
        ▼
Painel consulta o Microsoft Graph (/v1.0/me) para obter o e-mail
        │
        ▼
Se o e-mail existir na tabela users → sessão criada (24h) → painel
```

> **IMPORTANTE:** O login OAuth **não cria usuários automaticamente**. O e-mail utilizado na Microsoft deve estar previamente cadastrado no painel administrativo.

---

## 2. Pré-requisitos

- Conta com acesso ao **Portal Azure** (https://portal.azure.com) com permissão para registrar aplicativos no Microsoft Entra ID.
- Conta com licença **Microsoft 365 / Office 365** (para testar o login).
- Acesso ao servidor para editar o arquivo `.env`.
- O painel deve estar acessível por **HTTPS** (a Microsoft exige redirect URI com HTTPS, exceto localhost).

---

## 3. Criar o App Registration no Microsoft Entra ID

### 3.1 Acessar o Portal Azure

1. Acesse https://portal.azure.com.
2. No menu de busca, digite **"Microsoft Entra ID"** (antigo Azure Active Directory) e abra o serviço.

### 3.2 Criar o registro

1. No menu lateral, clique em **"App registrations"** (Registros de aplicativo).
2. Clique em **"New registration"** (Novo registro).
3. Preencha o formulário:

| Campo | Valor |
|-------|-------|
| **Name** | `Kanban GLPI` (ou qualquer nome descritivo) |
| **Supported account types** | Escolha uma das opções abaixo |
| **Redirect URI** | Deixe em branco agora (configura no passo 4) |

**Opções de "Supported account types":**

| Opção | Quando usar |
|-------|-------------|
| Accounts in this organizational directory only | Apenas usuários do seu tenant Office 365 |
| Accounts in any organizational directory | Usuários de qualquer tenant (multi-organização) |
| Accounts in any organizational directory + personal | Inclui contas pessoais (Microsoft, Gmail, etc.) |

4. Clique em **"Register"**.

### 3.3 Copiar os IDs

Após o registro, na página de visão geral do app, copie:

| Campo | Usar no .env |
|-------|-------------|
| **Application (client) ID** | `OAUTH_CLIENT_ID` |
| **Directory (tenant) ID** | `OAUTH_TENANT_ID` |

Guarde esses valores — você vai precisar deles no passo 7.

---

## 4. Configurar o Redirect URI

1. No app registrado, vá em **"Authentication"** (Autenticação).
2. Na seção **"Platform configurations"**, clique em **"Add a platform"** e escolha **"Web"**.
3. Em **"Redirect URIs"**, adicione:

```
https://SEU-DOMINIO/login/oauth/callback
```

> Troque `SEU-DOMINIO` pelo endereço público do painel. O valor deve ser **idêntico** ao `OAUTH_REDIRECT_URI` do `.env` — qualquer diferença (barra final, http/https, maiúsculas) causa erro.

4. Clique em **"Save"**.

---

## 5. Configurar as permissões (API Permissions)

1. No app registrado, vá em **"API permissions"**.
2. Clique em **"Add a permission"**.
3. Escolha **"Microsoft Graph"** → **"Delegated permissions"**.
4. Adicione as seguintes permissões:

| Permissão | Descrição | Obrigatória? |
|-----------|-----------|-------------|
| `User.Read` | Ler o perfil do usuário | Sim |
| `email` | Acessar o e-mail do usuário | Recomendada |
| `openid` | Login via OpenID Connect | Recomendada |
| `profile` | Acessar dados básicos do perfil | Recomendada |

5. Clique em **"Add permissions"**.

> Se o tenant exigir consentimento do administrador, clique em **"Grant admin consent for [seu tenant]"** na aba de permissões.

---

## 6. Gerar o Client Secret (ou usar PKCE)

### Opção A — Client Secret (recomendada para servidores web)

1. No app registrado, vá em **"Certificates & secrets"**.
2. Na aba **"Client secrets"**, clique em **"New client secret"**.
3. Dê uma descrição (ex.: `kanban-prod`), defina a data de expiração e clique em **"Add"**.
4. **Copie o valor imediatamente** — ele só é exibido uma vez.
5. Use esse valor em `OAUTH_CLIENT_SECRET` no `.env`.

### Opção B — PKCE (sem segredo de cliente)

Se você deixar `OAUTH_CLIENT_SECRET` vazio no `.env`, o painel usa automaticamente o fluxo **PKCE (RFC 7636)**, gerando um *code verifier* e *code challenge*.

Nesse caso, configure o App Registration como **cliente público** no Azure.

---

## 7. Criar as variáveis no .env

Abra o arquivo `.env` na raiz do projeto e adicione ou complete o bloco abaixo:

```bash
# --- OFFICE 365 (MICROSOFT ENTRA ID) LOGIN ---
OAUTH_CLIENT_ID=aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
OAUTH_CLIENT_SECRET=seu-client-secret-aqui
OAUTH_TENANT_ID=ffffffff-gggg-hhhh-iiii-jjjjjjjjjjjj
OAUTH_REDIRECT_URI=https://SEU-DOMINIO/login/oauth/callback
```

### Descrição de cada variável

| Variável | Onde obter | Descrição |
|----------|-----------|-----------|
| `OAUTH_CLIENT_ID` | Portal Azure → App Registration → Overview | Application (client) ID |
| `OAUTH_CLIENT_SECRET` | Portal Azure → Certificates & secrets | Valor do Client Secret (deixe vazio para PKCE) |
| `OAUTH_TENANT_ID` | Portal Azure → App Registration → Overview | Directory (tenant) ID. Use `common` para multi-tenant |
| `OAUTH_REDIRECT_URI` | Deve bater EXATAMENTE com o Redirect URI do Azure | URL de retorno: `https://dominio/login/oauth/callback` |

### Exemplo com dados reais

```bash
OAUTH_CLIENT_ID=a1b2c3d4-e5f6-7890-abcd-ef1234567890
OAUTH_CLIENT_SECRET=xYzAbC123DefGHI456jklMNO789pqrSTU
OAUTH_TENANT_ID=12345678-1234-1234-1234-123456789012
OAUTH_REDIRECT_URI=https://kanban.empresa.com.br/login/oauth/callback
```

### Exemplo com PKCE (sem secret)

```bash
OAUTH_CLIENT_ID=a1b2c3d4-e5f6-7890-abcd-ef1234567890
OAUTH_CLIENT_SECRET=
OAUTH_TENANT_ID=common
OAUTH_REDIRECT_URI=https://kanban.empresa.com.br/login/oauth/callback
```

### Regras importantes

- O `.env` é **gitignored** — nunca será commitado no repositório.
- Nunca exponha `OAUTH_CLIENT_SECRET` em logs ou mensagens de erro.
- Após alterar o `.env`, o painel lê automaticamente na próxima requisição (não precisa reiniciar o PHP-FPM).

---

## 8. Habilitar o botão na tela de login

O botão "Entrar com Office 365" aparece **automaticamente** quando as variáveis `OAUTH_CLIENT_ID` e `OAUTH_REDIRECT_URI` estão preenchidas no `.env`.

**Não é necessário alterar código.** A tela de login (`src/Views/auth/login.php`) verifica a variável `$oauth_enabled` e renderiza a aba condicionalmente:

```php
<?php if ($oauth_enabled ?? false): ?>
    <button class="tab-btn tab-btn-office" onclick="handleOfficeLogin()">
        <svg><!-- logo Microsoft --></svg>
        Office 365
    </button>
<?php endif; ?>
```

A variável `$oauth_enabled` é definida no controlador `AuthController::loginView()` com base em:

```php
$oauth = new MicrosoftOAuth();
$oauth_enabled = $oauth->isEnabled(); // true se OAUTH_CLIENT_ID + OAUTH_REDIRECT_URI não estiverem vazios
```

### Resumo da lógica

| Condição | Resultado |
|----------|-----------|
| `OAUTH_CLIENT_ID` + `OAUTH_REDIRECT_URI` preenchidos | Botão visível |
| `OAUTH_CLIENT_ID` ou `OAUTH_REDIRECT_URI` vazio | Botão oculto |
| `OAUTH_CLIENT_SECRET` vazio | Fluxo PKCE ativado (sem segredo) |

---

## 9. Testar o login

1. Acesse a tela de login do painel.
2. Confirme que a aba **"Office 365"** está visível.
3. Clique no botão — você será redirecionado para a página de login da Microsoft.
4. Faça login com uma conta Office 365 cujo e-mail esteja cadastrado no painel.
5. Ao autorizar, você voltará ao painel já autenticado (sessão de 24h).

### Verificação no servidor

Para monitorar as requisições OAuth em tempo real:

```bash
tail -f storage/logs/office.log
```

Logs de debug no log diário:

```bash
tail -f storage/logs/app.log | grep -i oauth
```

---

## 10. Resolução de problemas

| Sintoma | Causa provável | Solução |
|---------|---------------|---------|
| Botão "Office 365" não aparece | `OAUTH_CLIENT_ID` ou `OAUTH_REDIRECT_URI` vazios | Preencha ambos no `.env` |
| **AADSTS50011** (redirect URI inválido) | Redirect URI no Azure não bate com o `.env` | Verifique http/https, barra final, domínio — devem ser idênticos |
| **AADSTS50013** (audience inválida) | `OAUTH_CLIENT_ID` incorreto | Confira o Application ID no Azure |
| **AADSTS7000218** (client secret inválido) | Secret incorreto ou expirado | Gere um novo secret no Azure e atualize o `.env` |
| "E-mail não cadastrado no painel" | E-mail Microsoft não existe na tabela `users` | Cadastre o usuário no painel administrativo |
| "Falha na validação de segurança" | Parâmetro `state` inválido | Tente novamente (pode ser página recarregada) |
| Permissão negada no consentimento | Admin não concedeu consentimento | Clique em "Grant admin consent" no Azure |
| Erro 401/403 do Graph API | Permissões delegadas incorretas | Verifique `User.Read`, `email`, `openid`, `profile` |

---

## Arquitetura técnica (para desenvolvedores)

A integração é implementada nos seguintes arquivos:

| Arquivo | Responsabilidade |
|---------|-----------------|
| `src/Services/MicrosoftOAuth.php` | Serviço OAuth (authorize URL, troca de código, Graph API, PKCE) |
| `src/Controllers/AuthController.php` | Métodos `oauthStart()` e `oauthCallback()` (linhas 307-504) |
| `src/Views/auth/login.php` | Aba Office 365 condicional (linha 24) |
| `src/routes.php` | Rotas `GET /login/oauth` e `GET /login/oauth/callback` (linhas 43-44) |
| `src/bootstrap.php` | Whitelist das rotas OAuth no middleware de sessão (linha 141) |
| `src/Core/Log.php` | Método `Log::office()` para log dedicado (`storage/logs/office.log`) |
| `.env.example` | Template com as 4 variáveis OAuth (linhas 48-55) |

### Não usa bibliotecas externas

Toda a integração OAuth é feita com **cURL nativo do PHP** — não há dependência de pacotes Composer para autenticação.

### Fluxo de segurança

1. **State token** — gerado aleatoriamente, armazenado em `$_SESSION` + cookie como fallback, validado com `hash_equals` no callback.
2. **PKCE** — quando `OAUTH_CLIENT_SECRET` está vazio, o painel gera automaticamente `code_verifier` e `code_challenge` (SHA256).
3. **Rate limiting** — o OAuth não passa pelo rate limiter de login (o fluxo é redirecionado pela Microsoft).
4. **Sessão** — compartilhada com os demais métodos de login (OTP e senha), com TTL de 24 horas na tabela `sessions`.
