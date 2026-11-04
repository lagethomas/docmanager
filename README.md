# DocManager

Sistema web para centralizar a criação, organização, revisão e compartilhamento de documentos. O projeto oferece recursos de gestão de conteúdo e colaboração, com controle de acesso por usuário e integração opcional com Microsoft 365.

## Funcionalidades

- Cadastro e organização de documentos por categorias e etiquetas.
- Edição, histórico de versões, rascunhos e lixeira.
- Anexos e exportação de documentos para PDF.
- Fluxo de aprovação de documentos e notificações.
- Comentários e acompanhamento de pendências (*issues*).
- Compartilhamento com usuários ou por links públicos.
- Documentos públicos e níveis de confidencialidade/restrição.
- Gerenciamento de usuários, perfis, áreas e localizações.
- Relatórios, auditoria e painel administrativo.
- Login opcional via Microsoft 365 / Microsoft Entra ID.

## Tecnologias

- PHP 8.1 ou superior, sem framework.
- MariaDB e PDO.
- JavaScript e CSS.
- Composer para dependências PHP.

Bibliotecas principais: Dompdf, Parsedown, Minify, PHPMailer e PHP dotenv.

## Requisitos

- PHP 8.1+ com as extensões PDO e JSON habilitadas; configure também o driver PDO compatível com MariaDB/MySQL.
- MariaDB acessível pela aplicação.
- Composer.
- Um servidor web configurado para servir a pasta `public/` e encaminhar as requisições para `public/index.php`.

Redis e SMTP podem ser configurados conforme a necessidade do ambiente. A integração OAuth com Microsoft 365 também é opcional.

## Instalação para desenvolvimento

1. Clone o repositório e instale as dependências:

   ```bash
   git clone https://github.com/lagethomas/docmanager.git
   cd docmanager
   composer install
   ```

2. Crie seu arquivo de ambiente a partir do exemplo e configure as credenciais e opções necessárias:

   ```bash
   cp .env.example .env
   ```

   Preencha, no mínimo, as configurações de ambiente da aplicação e de conexão com o MariaDB. Gere uma `APP_KEY` exclusiva para o ambiente e defina uma `SITE_URL` adequada. Não reutilize chaves ou credenciais de outros ambientes.

3. Crie um banco de dados MariaDB para a aplicação e execute as migrações:

   ```bash
   composer migrate
   ```

4. **Antes de executar o seed, leia `bin/seed.php`.** Ele cria uma conta administrativa inicial com credenciais fixas (`admin` / `admin@1234`). Use-o somente em desenvolvimento local; nunca mantenha essas credenciais em uma instalação pública ou de produção. Remova ou altere o comportamento padrão do seed e crie credenciais fortes e exclusivas antes de disponibilizar o sistema.

   Para executar o seed local:

   ```bash
   composer seed
   ```

5. Configure o servidor web para usar `public/` como raiz pública do site. Não exponha a raiz do repositório, arquivos `.env`, código-fonte, logs ou diretórios de armazenamento de arquivos enviados.

## Configuração

As opções disponíveis estão documentadas em [`.env.example`](.env.example). Entre elas:

- **Aplicação:** `APP_ENV`, `APP_KEY`, `SITE_URL`, fuso horário e nome/versão do sistema.
- **Banco de dados:** `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`.
- **Sessões e cache:** opções de sessão e configuração Redis.
- **E-mail:** parâmetros SMTP para envio de notificações.
- **Microsoft 365:** credenciais OAuth e URI de redirecionamento.

O arquivo `.env` contém segredos e configurações locais: não o versione nem o publique. Use valores próprios para cada ambiente.

## Comandos úteis

```bash
composer lint          # Verifica a sintaxe dos arquivos PHP
vendor/bin/phpunit     # Executa os testes
composer migrate       # Aplica as migrações pendentes
composer seed          # Cria dados iniciais (leia o aviso sobre credenciais acima)
composer build-assets  # Compila e minifica os arquivos CSS/JS
vendor/bin/phpstan analyse  # Análise estática
```

## Estrutura do projeto

```text
public/             Ponto de entrada e arquivos públicos
src/Controllers/    Controladores
src/Core/           Componentes centrais, incluindo roteador e acesso ao banco
src/Helpers/        Funções e auxiliares
src/Middlewares/    Middlewares de autenticação e autorização
src/Models/         Modelos e acesso aos dados
src/Routes/         Rotas web e API
src/Views/          Telas e componentes
bin/                Scripts CLI, migrações, seed e build de assets
database/           Arquivos SQL de referência
```

## Segurança para disponibilização pública

Antes de publicar ou implantar o sistema:

- Troque ou remova as credenciais fixas do seed e use senhas fortes.
- Configure `APP_ENV=production`, `APP_KEY`, `SITE_URL` e credenciais próprias.
- Use HTTPS e restrinja o acesso ao banco, Redis, logs e arquivos enviados.
- Confira as permissões de usuários, documentos, links compartilhados e conteúdo público.
- Não inclua arquivos `.env`, dados reais, credenciais, logs ou documentos privados no Git.

Tornar o código-fonte público não significa que uma instalação do sistema esteja pronta ou segura para produção. Faça uma revisão de configuração e segurança específica do seu ambiente antes de disponibilizá-la.

## Licença

O `composer.json` declara o projeto como **proprietário** e o repositório não contém uma licença de software livre identificada. Tornar o repositório público no GitHub, por si só, não concede permissão para reutilizar, modificar ou distribuir o código. Inclua uma licença explícita se quiser definir esses direitos.
