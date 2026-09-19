<?php
declare(strict_types=1);

namespace App\Models;

class DocArticle
{
    /**
     * @var array<string, array{
     *     slug: string,
     *     title: string,
     *     category: string,
     *     category_name: string,
     *     icon: string,
     *     summary: string,
     *     read_time: string,
     *     content_html: string
     * }>
     */
    private static array $articles = [
        'variaveis-env' => [
            'slug' => 'variaveis-env',
            'title' => '1. Guia Completo do Arquivo .env e Variáveis de Ambiente',
            'category' => 'configuracao',
            'category_name' => 'Configuração',
            'icon' => 'settings',
            'summary' => 'Explicação detalhada de cada variável de ambiente do DocManager, incluindo banco de dados, sessão, SMTP e chaves OAuth.',
            'read_time' => '6 min de leitura',
            'content_html' => '
                <h3>Visão Geral do Arquivo .env</h3>
                <p>O arquivo <code>.env</code> na raiz do projeto armazena todas as configurações sensíveis e específicas do ambiente de execução (Desenvolvimento, Homologação ou Produção). Ele nunca deve ser enviado para repositórios Git públicos.</p>

                <div class="alert alert-info" style="margin: 1.25rem 0;">
                    <div class="alert-body">
                        <strong>Dica:</strong> Em novos ambientes, renomeie o arquivo <code>.env.example</code> para <code>.env</code> e preencha as variáveis correspondentes.
                    </div>
                </div>

                <h4>📋 Detalhamento de Cada Variável</h4>
                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="table" style="font-size: 0.88rem;">
                        <thead>
                            <tr>
                                <th>Variável</th>
                                <th>Valor Padrão / Exemplo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>APP_ENV</code></td>
                                <td><code>production</code> ou <code>development</code></td>
                                <td>Define o ambiente. Em modo <code>development</code>, exibe erros detalhados.</td>
                            </tr>
                            <tr>
                                <td><code>APP_TIMEZONE</code></td>
                                <td><code>America/Sao_Paulo</code></td>
                                <td>Fuso horário padrão para registro de datas e logs do sistema.</td>
                            </tr>
                            <tr>
                                <td><code>APP_DATE_FORMAT</code></td>
                                <td><code>d/m/Y H:i</code></td>
                                <td>Formato de data e hora utilizado na exibição.</td>
                            </tr>
                            <tr>
                                <td><code>APP_KEY</code></td>
                                <td><code>base64:...</code> ou hex 32 chars</td>
                                <td>Chave secreta de criptografia da aplicação.</td>
                            </tr>
                            <tr>
                                <td><code>SITE_URL</code></td>
                                <td><code>https://docmanager.empresa.com.br</code></td>
                                <td>URL pública base do sistema (sem barra no final).</td>
                            </tr>
                            <tr>
                                <td><code>LOGS</code></td>
                                <td><code>true</code></td>
                                <td>Força a gravação de logs em arquivo PHP mesmo se desativado no painel.</td>
                            </tr>
                            <tr>
                                <td><code>SESSION_NAME</code></td>
                                <td><code>portal_quebras_session</code></td>
                                <td>Nome do cookie de sessão enviado ao navegador.</td>
                            </tr>
                            <tr>
                                <td><code>SESSION_LIFETIME</code></td>
                                <td><code>480</code> (minutos = 8h)</td>
                                <td>Tempo máximo de inatividade antes de expirar a sessão do usuário.</td>
                            </tr>
                            <tr>
                                <td><code>SYSTEM_NAME</code></td>
                                <td><code>DocManager</code></td>
                                <td>Nome do sistema exibido na barra superior e títulos.</td>
                            </tr>
                            <tr>
                                <td><code>SYSTEM_VERSION</code></td>
                                <td><code>1.0.0</code></td>
                                <td>Versão atual da aplicação.</td>
                            </tr>
                            <tr>
                                <td><code>DB_HOST</code></td>
                                <td><code>127.0.0.1</code></td>
                                <td>Endereço IP ou hostname do servidor MariaDB/MySQL.</td>
                            </tr>
                            <tr>
                                <td><code>DB_PORT</code></td>
                                <td><code>3306</code></td>
                                <td>Porta de conexão com o banco de dados.</td>
                            </tr>
                            <tr>
                                <td><code>DB_NAME</code></td>
                                <td><code>docmanager_db</code></td>
                                <td>Nome do banco de dados relacional.</td>
                            </tr>
                            <tr>
                                <td><code>DB_USER</code></td>
                                <td><code>docmanager_user</code></td>
                                <td>Usuário de conexão do banco de dados.</td>
                            </tr>
                            <tr>
                                <td><code>DB_PASS</code></td>
                                <td><code>sua_senha_segura</code></td>
                                <td>Senha de acesso ao banco de dados.</td>
                            </tr>
                            <tr>
                                <td><code>DB_CHARSET</code></td>
                                <td><code>utf8mb4</code></td>
                                <td>Conjunto de caracteres (deve ser utf8mb4).</td>
                            </tr>
                            <tr>
                                <td><code>SMTP_HOST</code></td>
                                <td><code>smtp.office365.com</code></td>
                                <td>Servidor de envio de e-mails transacionais.</td>
                            </tr>
                            <tr>
                                <td><code>SMTP_PORT</code></td>
                                <td><code>587</code></td>
                                <td>Porta do servidor SMTP (TLS/SSL).</td>
                            </tr>
                            <tr>
                                <td><code>SMTP_USER</code> / <code>SMTP_PASS</code></td>
                                <td><code>notificacoes@empresa.com</code></td>
                                <td>Credenciais da conta de e-mail emissora.</td>
                            </tr>
                            <tr>
                                <td><code>OAUTH_CLIENT_ID</code></td>
                                <td><code>a1b2c3d4-...</code></td>
                                <td>ID da aplicação registrada no Microsoft Entra ID (Portal Azure).</td>
                            </tr>
                            <tr>
                                <td><code>OAUTH_CLIENT_SECRET</code></td>
                                <td><code>xYzAbC123...</code></td>
                                <td>Chave secreta gerada no Azure (deixe vazio para fluxo PKCE puro).</td>
                            </tr>
                            <tr>
                                <td><code>OAUTH_TENANT_ID</code></td>
                                <td><code>common</code> ou ID do Tenant</td>
                                <td>ID do diretório Microsoft. Use <code>common</code> para multi-tenant.</td>
                            </tr>
                            <tr>
                                <td><code>OAUTH_REDIRECT_URI</code></td>
                                <td><code>https://SEU-DOMINIO/login/oauth/callback</code></td>
                                <td>URL de retorno cadastrada obrigatoriamente no App Registration.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            '
        ],

        'configuracao-office365' => [
            'slug' => 'configuracao-office365',
            'title' => '2. Integração Office 365 (Microsoft Entra ID) & Redirect URI',
            'category' => 'autenticacao',
            'category_name' => 'Autenticação & SSO',
            'icon' => 'key',
            'summary' => 'Passo a passo completo para registrar o aplicativo no Portal Azure, configurar permissões e habilitar o login "Entrar com Office 365".',
            'read_time' => '5 min de leitura',
            'content_html' => '
                <h3>Autenticação Única (SSO) com Office 365</h3>
                <p>O DocManager possui suporte nativo ao padrão <strong>OAuth 2.0 Authorization Code Flow + PKCE</strong> (Proof Key for Code Exchange) para permitir login corporativo usando contas Microsoft Entra ID (antigo Azure Active Directory).</p>

                <h4>📌 1. Registrar o aplicativo no Portal Azure</h4>
                <ol style="margin-left: 1.5rem; margin-bottom: 1.5rem; line-height: 1.8;">
                    <li>Acesse o <strong>Portal do Azure</strong> (<a href="https://portal.azure.com" target="_blank" rel="noopener">https://portal.azure.com</a>).</li>
                    <li>Pesquise por <strong>"Microsoft Entra ID"</strong> e clique em <strong>"App registrations"</strong> (Registros de aplicativo).</li>
                    <li>Clique em <strong>"New registration"</strong> (Novo registro).</li>
                    <li>Preencha o nome (ex.: <code>DocManager SSO</code>) e defina os tipos de conta suportados.</li>
                </ol>

                <h4>⚡ 2. URL de Redirecionamento (Obrigatória)</h4>
                <div class="alert alert-warning" style="margin: 1.25rem 0;">
                    <div class="alert-body">
                        <div style="font-weight: 700; margin-bottom: 0.5rem; font-size: 0.95rem; color: #b45309;">URL de Redirect EXATA:</div>
                        <div style="background: rgba(0,0,0,0.05); padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 0.9rem; margin-bottom: 0.75rem; word-break: break-all;">
                            https://SEU-DOMINIO/login/oauth/callback
                        </div>
                        <div style="font-size: 0.85rem; line-height: 1.6;">
                            <em>Exemplo real: <code>https://docmanager.suaempresa.com.br/login/oauth/callback</code></em><br>
                            Esta URL deve bater exatamente com o valor da variável <code>OAUTH_REDIRECT_URI</code> no arquivo <code>.env</code>. Qualquer discrepância (ex: falta de HTTPS ou barra final) causará o erro <strong>AADSTS50011</strong> na Microsoft.
                        </div>
                    </div>
                </div>

                <h4>🛡️ 3. Permissões da API (API Permissions)</h4>
                <p>No aplicativo registrado, acesse a aba <strong>API permissions</strong> e adicione as seguintes permissões delegadas do <strong>Microsoft Graph</strong>:</p>
                <ul style="line-height: 1.8;">
                    <li><code>User.Read</code> — Ler o perfil básico do usuário autenticado</li>
                    <li><code>email</code> — Acessar o endereço de e-mail corporativo</li>
                    <li><code>openid</code> — Autenticação OpenID Connect</li>
                    <li><code>profile</code> — Acessar dados básicos de identificação</li>
                </ul>

                <h4>🔑 4. Segredo de Cliente ou PKCE</h4>
                <p>Na aba <strong>Certificates & secrets</strong>, gere um novo <em>Client Secret</em> e copie seu valor imediatamente para a variável <code>OAUTH_CLIENT_SECRET</code> no <code>.env</code>.</p>
                <p><em>Nota: Caso a variável <code>OAUTH_CLIENT_SECRET</code> fique vazia, o sistema utilizará automaticamente o fluxo **PKCE puro**, gerando o `code_verifier` e o `code_challenge` SHA-256 a cada requisição.</em></p>

                <h4>👥 5. Associação com a Tabela de Usuários</h4>
                <p>O login OAuth <strong>não cria usuários desconhecidos automaticamente</strong>. O e-mail da conta Microsoft autenticada deve corresponder ao campo <code>login</code> de um usuário previamente cadastrado e com status <code>ativo</code> no painel de Administração de Usuários.</p>
            '
        ],

        'arquitetura-sistema' => [
            'slug' => 'arquitetura-sistema',
            'title' => '3. Arquitetura MVC, Segurança e Padrões de Código',
            'category' => 'arquitetura',
            'category_name' => 'Arquitetura',
            'icon' => 'cpu',
            'summary' => 'Conheça o padrão MVC sem frameworks pesados, uso de Prepared Statements, hashing Argon2id e proteção CSRF.',
            'read_time' => '4 min de leitura',
            'content_html' => '
                <h3>Arquitetura MVC Pura em PHP 8.x</h3>
                <p>O DocManager é construído sobre uma arquitetura MVC limpa e de alta performance, sem dependência de frameworks corporativos pesados (Laravel/Symfony), garantindo tempo de resposta na casa dos milissegundos.</p>

                <h4>Estágios do Fluxo de Requisição</h4>
                <pre style="background: rgba(15, 23, 42, 0.85); color: #38bdf8; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem; line-height: 1.5; overflow-x: auto;">
public/index.php ──► src/bootstrap.php (Autoload PSR-4, Error Handlers, DB Alias)
                          │
                          ▼
                   src/config.php (Env, Sessão, Fuso Horário)
                          │
                          ▼
                 src/Routes/web.php + api.php
                          │
                          ▼
                   Router::run() ──► Middlewares (Auth, Admin, CSRF)
                          │
                          ▼
                      Controller ──► Model (Repository SQL) ──► View (Render)
                </pre>

                <h4>🔒 Diretrizes Críticas de Segurança</h4>
                <ul style="line-height: 1.8;">
                    <li><strong>Strict Types:</strong> Todo arquivo PHP começa com <code>declare(strict_types=1);</code>.</li>
                    <li><strong>Prepared Statements:</strong> Todas as requisições ao banco usam <code>$db->prepare()</code> com binding seguro. Nenhuma concatenação direta de SQL.</li>
                    <li><strong>Argon2id:</strong> Senhas locais utilizam <code>SecurityHelper::hashPassword()</code>.</li>
                    <li><strong>CSRF:</strong> Formulários utilizam <code>csrf_field()</code> e requisições Fetch usam o cabeçalho <code>X-CSRF-Token</code>.</li>
                    <li><strong>Escape em Views:</strong> Saídas utilizam a função global <code>e()</code> para prevenir XSS.</li>
                </ul>
            '
        ],

        'troubleshooting-logs' => [
            'slug' => 'troubleshooting-logs',
            'title' => '4. Trilha de Logs, Auditoria e Resolução de Erros',
            'category' => 'troubleshooting',
            'category_name' => 'Troubleshooting',
            'icon' => 'file-text',
            'summary' => 'Como monitorar logs de auditoria, logs do Office 365 em storage/logs/office.log e solucionar problemas comuns.',
            'read_time' => '3 min de leitura',
            'content_html' => '
                <h3>Estrutura de Logs do Sistema</h3>
                <p>O sistema grava logs em três níveis principais para facilitar a auditoria e o diagnóstico técnico:</p>

                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="table" style="font-size: 0.88rem;">
                        <thead>
                            <tr>
                                <th>Arquivo / Local</th>
                                <th>Finalidade</th>
                                <th>Comando para Leitura (Terminal)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>tabela cp_logs</code></td>
                                <td>Auditoria de ações de usuários (login, aprovações, edições).</td>
                                <td>Acessível pelo painel em <strong>Relatórios</strong></td>
                            </tr>
                            <tr>
                                <td><code>storage/logs/app.log</code></td>
                                <td>Log em arquivo de auditoria e ações do portal.</td>
                                <td><code>tail -f storage/logs/app.log</code></td>
                            </tr>
                            <tr>
                                <td><code>storage/logs/office.log</code></td>
                                <td>Log dedicado a requisições OAuth e erros de SSO Office 365.</td>
                                <td><code>tail -f storage/logs/office.log</code></td>
                            </tr>
                            <tr>
                                <td><code>storage/logs/php-errors.log</code></td>
                                <td>Exceções PHP não capturadas e erros de execução.</td>
                                <td><code>tail -f storage/logs/php-errors.log</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h4 style="margin-top: 1.5rem;">Resolução de Sintomas Comuns</h4>
                <ul style="line-height: 1.8;">
                    <li><strong>Botão Office 365 não aparece:</strong> Verifique se <code>OAUTH_CLIENT_ID</code> e <code>OAUTH_REDIRECT_URI</code> estão preenchidos no <code>.env</code>.</li>
                    <li><strong>Erro AADSTS50011 no login Office:</strong> A URL no Azure não bate exatamente com o <code>OAUTH_REDIRECT_URI</code>.</li>
                    <li><strong>E-mail não cadastrado no painel:</strong> O e-mail autenticado no Office 365 não foi encontrado na coluna <code>login</code> da tabela <code>cp_users</code>.</li>
                </ul>
            '
        ],

        'biblioteca-icones' => [
            'slug' => 'biblioteca-icones',
            'title' => '5. Biblioteca Central de Ícones SVG (IconHelper & Icons)',
            'category' => 'arquitetura',
            'category_name' => 'Arquitetura & UI',
            'icon' => 'grid',
            'summary' => 'Guia completo da biblioteca interna de ícones SVG vetorizados. Como utilizar IconHelper no PHP e Icons no JavaScript sem dependências externas.',
            'read_time' => '4 min de leitura',
            'content_html' => '
                <h3>Biblioteca Centralizada de Ícones SVG</h3>
                <p>O DocManager conta com uma biblioteca nativa e centralizada de ícones SVG vetorizados, eliminando totalmente a dependência de fontes pesadas e libs externas (como FontAwesome). O sistema disponibiliza paridade total de nomenclatura entre o backend (<strong>PHP</strong>) e o frontend (<strong>JavaScript</strong>).</p>

                <div class="alert alert-info" style="margin: 1.25rem 0;">
                    <div class="alert-body">
                        <strong>Vantagens da Lib Própria:</strong> Alta performance (zero requisições HTTP externas), estilização nativa via CSS <code>currentColor</code>, suporte a ícones vazados (outline) e preenchidos (solid/filled), além de ícones técnicos de infraestrutura (Linux Penguin, Docker Whale, Database, Terminal, etc).
                    </div>
                </div>

                <h4>🐘 1. Uso no Backend (PHP)</h4>
                <p>No PHP, utilize a classe <code>App\Helpers\IconHelper</code>. Ela disponibiliza os métodos estáticos abaixo:</p>
                <pre><code>// 1. Obter SVG simples por nome
echo IconHelper::get("folder", "me-2 text-primary");

// 2. Definir tamanho customizado em pixels (ex: 20px)
echo IconHelper::get("linux", "align-middle", 20);

// 3. Ícone preenchido (Solid / Filled) - ex: Estrela de Favoritos
echo IconHelper::get("starSolid", "text-warning", 18);

// 4. Ícone automático por categoria de documentos/pastas
echo IconHelper::getCategoryIcon("Servidores Linux", "me-2");</code></pre>

                <h4>⚡ 2. Uso no Frontend (JavaScript)</h4>
                <p>No JS client-side (carregado via <code>public/assets/js/src/icons.js</code>), utilize o objeto global <code>Icons</code>:</p>
                <pre><code>// 1. Gerar string HTML do SVG
const svgHtml = Icons.get("folder", { class: "icon-sm me-2", size: 16 });

// 2. Inserir direto em um elemento do DOM
Icons.renderInto(document.getElementById("myIconContainer"), "starSolid", { class: "text-warning" });

// 3. Obter mapa de ícones por nome de categoria
const categoryMap = Icons.getCategoryMap();</code></pre>

                <h4>🎨 3. Parâmetros e Opções Suportadas</h4>
                <p>O método <code>IconHelper::get($name, $options)</code> no PHP e <code>Icons.get($name, $options)</code> no JS aceitam as seguintes propriedades no array ou objeto de opções:</p>
                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="table" style="font-size: 0.88rem;">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição & Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>size</code></td>
                                <td><code>int|string</code></td>
                                <td>Tamanho em pixels (ex: <code>16</code> ou <code>"24px"</code>). Ajusta <code>width</code> e <code>height</code> automaticamente.</td>
                            </tr>
                            <tr>
                                <td><code>class</code> / <code>className</code></td>
                                <td><code>string</code></td>
                                <td>Classes CSS adicionais (ex: <code>"me-2 text-primary align-middle"</code>).</td>
                            </tr>
                            <tr>
                                <td><code>color</code></td>
                                <td><code>string</code></td>
                                <td>Cor hexadecimal ou var CSS aplicada diretamente no <code>style</code> (ex: <code>"#f59e0b"</code>).</td>
                            </tr>
                            <tr>
                                <td><code>filled</code> / <code>solid</code></td>
                                <td><code>bool</code></td>
                                <td>Força preenchimento contínuo <code>fill="currentColor"</code> em vez de <code>stroke</code>.</td>
                            </tr>
                            <tr>
                                <td><code>viewBox</code></td>
                                <td><code>string</code></td>
                                <td>Sobrescreve a caixa de visualização SVG (ex: <code>"0 0 640 640"</code>).</td>
                            </tr>
                            <tr>
                                <td><code>title</code></td>
                                <td><code>string</code></td>
                                <td>Adiciona atributo <code>title="..."</code> para tooltip nativo do navegador.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h4>➕ 4. Como Adicionar Novos Ícones à Biblioteca (Passo a Passo)</h4>
                <p>Para adicionar um novo ícone SVG vetorizado à biblioteca do DocManager, siga os dois passos abaixo para manter paridade entre o servidor e o cliente:</p>

                <h5>Passo 1: Registrar no Backend (PHP) — <code>src/Helpers/IconHelper.php</code></h5>
                <p>Abra o arquivo <code>src/Helpers/IconHelper.php</code> e adicione a nova chave dentro do array estático <code>$registry</code>:</p>
                <pre><code>"meuIcone" => [
    "type" => "svg",
    "body" => "&lt;path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M...\" /&gt;",
    "defaultSize" => 14,             // opcional
    "viewBox" => "0 0 24 24",         // opcional (padrão "0 0 24 24")
    "filled" => false,               // opcional (true se for sólido)
],</code></pre>

                <h5>Passo 2: Registrar no Frontend (JS) — <code>public/assets/js/src/icons.js</code></h5>
                <p>Abra o arquivo <code>public/assets/js/src/icons.js</code> e insira a mesma definição dentro do objeto <code>REGISTRY</code>:</p>
                <pre><code>meuIcone: {
  type: "svg",
  body: "&lt;path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M...\" /&gt;",
  defaultSize: 14
},</code></pre>

                <p><em>Alternativamente, no JavaScript você também pode registrar ícones dinamicamente em tempo de execução usando o método:</em></p>
                <pre><code>Icons.register("novoIcone", { body: "&lt;path d=\"...\" /&gt;", viewBox: "0 0 640 640", filled: true });</code></pre>

                <h4>📦 5. Catálogo de Ícones Disponíveis</h4>
                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="table" style="font-size: 0.88rem;">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Nomes das Chaves de Ícones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Navegação & Geral</strong></td>
                                <td><code>home</code>, <code>folder</code>, <code>folderPlus</code>, <code>file</code>, <code>doc</code>, <code>document</code>, <code>star</code>, <code>starSolid</code>, <code>history</code>, <code>version</code>, <code>shield</code>, <code>bell</code>, <code>sun</code>, <code>moon</code>, <code>users</code>, <code>user</code>, <code>settings</code>, <code>menu</code>, <code>bars</code>, <code>logout</code></td>
                            </tr>
                            <tr>
                                <td><strong>Ações & Interface</strong></td>
                                <td><code>check</code>, <code>x</code>, <code>close</code>, <code>plus</code>, <code>add</code>, <code>pencil</code>, <code>edit</code>, <code>trash</code>, <code>delete</code>, <code>forceDelete</code>, <code>restore</code>, <code>duplicate</code>, <code>diff</code>, <code>eye</code>, <code>view</code>, <code>download</code>, <code>upload</code>, <code>share</code>, <code>link</code>, <code>search</code>, <code>filter</code>, <code>lock</code>, <code>unlock</code>, <code>paperclip</code>, <code>printer</code></td>
                            </tr>
                            <tr>
                                <td><strong>Formatação & Editor</strong></td>
                                <td><code>list</code>, <code>listOrdered</code>, <code>checkSquare</code>, <code>indent</code>, <code>outdent</code>, <code>quote</code>, <code>code</code>, <code>table</code>, <code>minus</code>, <code>image</code>, <code>columns</code>, <code>key</code>, <code>globe</code>, <code>location</code>, <code>pin</code></td>
                            </tr>
                            <tr>
                                <td><strong>Tecnologias & Sistemas</strong></td>
                                <td><code>linux</code> (Tux), <code>docker</code> (Whale), <code>windows</code>, <code>android</code>, <code>apple</code>, <code>terminal</code>, <code>database</code> / <code>db</code>, <code>cloud</code>, <code>server</code>, <code>chart</code>, <code>mail</code></td>
                            </tr>
                            <tr>
                                <td><strong>Arquitetura & Categorias</strong></td>
                                <td><code>auth</code>, <code>compute</code>, <code>network</code>, <code>book</code>, <code>archive</code>, <code>devops</code>, <code>api</code>, <code>saas</code>, <code>sec</code>, <code>tool</code>, <code>idea</code>, <code>goal</code>, <code>rocket</code>, <code>workflow</code>, <code>kanban</code>, <code>robot</code>, <code>truck</code>, <code>calendar</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            '
        ]
    ];

    /**
     * @return array<string, array{
     *     slug: string,
     *     title: string,
     *     category: string,
     *     category_name: string,
     *     icon: string,
     *     summary: string,
     *     read_time: string,
     *     content_html: string
     * }>
     */
    public static function all(): array
    {
        return self::$articles;
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     category: string,
     *     category_name: string,
     *     icon: string,
     *     summary: string,
     *     read_time: string,
     *     content_html: string
     * }|null
     */
    public static function findBySlug(string $slug): ?array
    {
        return self::$articles[$slug] ?? null;
    }
}
