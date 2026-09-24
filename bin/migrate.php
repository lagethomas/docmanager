<?php
declare(strict_types=1);

define('CLI_MODE', true);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();

$db->exec(
    'CREATE TABLE IF NOT EXISTS cp_migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migrations_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$migrations = [
    '0001_create_users_table' => "
        CREATE TABLE IF NOT EXISTS cp_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            name VARCHAR(120) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL,
            status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_users_username (username),
            UNIQUE KEY uq_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    '0002_create_settings_table' => "
        CREATE TABLE IF NOT EXISTS cp_settings (
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT NOT NULL,
            PRIMARY KEY (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        INSERT IGNORE INTO cp_settings (setting_key, setting_value) VALUES ('per_page', '10');
    ",

    '0003_create_roles_table' => "
        CREATE TABLE IF NOT EXISTS cp_roles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            description VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_roles_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        INSERT IGNORE INTO cp_roles (name, description) VALUES ('Admin', 'Administrador do sistema');
    ",

    '0004_create_doc_categories_table' => "
        CREATE TABLE IF NOT EXISTS cp_doc_categories (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            parent_id INT UNSIGNED DEFAULT NULL,
            icon VARCHAR(50) DEFAULT 'folder',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_doc_cat_parent (parent_id),
            CONSTRAINT fk_doc_cat_parent FOREIGN KEY (parent_id)
                REFERENCES cp_doc_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    '0005_create_doc_tags_table' => "
        CREATE TABLE IF NOT EXISTS cp_doc_tags (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_doc_tags_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    '0006_create_documents_table' => "
        CREATE TABLE IF NOT EXISTS cp_documents (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT,
            category_id INT UNSIGNED DEFAULT NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_docs_category (category_id),
            KEY idx_docs_created_by (created_by),
            FULLTEXT INDEX ft_docs (title, content),
            CONSTRAINT fk_docs_category FOREIGN KEY (category_id)
                REFERENCES cp_doc_categories(id) ON DELETE SET NULL,
            CONSTRAINT fk_docs_created_by FOREIGN KEY (created_by)
                REFERENCES cp_users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    '0007_create_document_tags_table' => "
        CREATE TABLE IF NOT EXISTS cp_document_tags (
            document_id INT UNSIGNED NOT NULL,
            tag_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (document_id, tag_id),
            CONSTRAINT fk_dtag_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_dtag_tag FOREIGN KEY (tag_id)
                REFERENCES cp_doc_tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    '0008_create_document_versions_table' => "
        CREATE TABLE IF NOT EXISTS cp_document_versions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            content LONGTEXT,
            version_note VARCHAR(255) DEFAULT '',
            edited_by INT UNSIGNED NOT NULL,
            edited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_dver_doc (document_id),
            CONSTRAINT fk_dver_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_dver_user FOREIGN KEY (edited_by)
                REFERENCES cp_users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    '0009_create_doc_uploads_table' => "
        CREATE TABLE IF NOT EXISTS cp_doc_uploads (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            size_bytes INT UNSIGNED NOT NULL,
            uploaded_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            CONSTRAINT fk_dupload_user FOREIGN KEY (uploaded_by)
                REFERENCES cp_users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    '0010_add_deleted_at_and_version_settings' => "
        ALTER TABLE cp_documents ADD COLUMN deleted_at DATETIME DEFAULT NULL;
        CREATE INDEX idx_docs_deleted_at ON cp_documents (deleted_at);
        INSERT IGNORE INTO cp_settings (setting_key, setting_value) VALUES ('doc_max_versions', '10');
    ",

    '0011_add_roles_editor_and_visualizador' => "
        INSERT IGNORE INTO cp_roles (name, description) VALUES 
        ('Editor', 'Criação e edição de documentos e pastas'),
        ('Visualizador', 'Acesso de somente leitura aos documentos');
    ",

    '0012_add_approval_workflow_and_views_count' => "
        ALTER TABLE cp_documents 
            ADD COLUMN status ENUM('rascunho', 'pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
            ADD COLUMN approved_by INT UNSIGNED DEFAULT NULL,
            ADD COLUMN approved_at DATETIME DEFAULT NULL,
            ADD COLUMN rejection_reason TEXT DEFAULT NULL,
            ADD COLUMN views_count INT UNSIGNED NOT NULL DEFAULT 0;

        CREATE INDEX idx_docs_status ON cp_documents (status, deleted_at);
        CREATE INDEX idx_docs_views ON cp_documents (views_count);

        UPDATE cp_documents SET status = 'aprovado' WHERE deleted_at IS NULL;

        INSERT IGNORE INTO cp_roles (name, description) VALUES 
        ('Aprovador', 'Revisão e aprovação de documentos para publicação');
    ",

    '0013_add_document_shares_table' => "
        CREATE TABLE IF NOT EXISTS cp_document_shares (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            can_edit TINYINT(1) NOT NULL DEFAULT 1,
            shared_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_doc_user (document_id, user_id),
            CONSTRAINT fk_doc_share_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_doc_share_user FOREIGN KEY (user_id)
                REFERENCES cp_users(id) ON DELETE CASCADE,
            CONSTRAINT fk_doc_share_by FOREIGN KEY (shared_by)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    '0014_add_color_to_doc_categories' => "
        ALTER TABLE cp_doc_categories ADD COLUMN color VARCHAR(20) NOT NULL DEFAULT '#1abb9c';
    ",

    '0015_create_document_favorites_table' => "
        CREATE TABLE IF NOT EXISTS cp_document_favorites (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_doc_user_fav (document_id, user_id),
            CONSTRAINT fk_fav_document FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_fav_user FOREIGN KEY (user_id)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    '0016_add_rejected_by_to_documents' => "
        ALTER TABLE cp_documents
            ADD COLUMN rejected_by INT UNSIGNED DEFAULT NULL AFTER approved_by;
        ALTER TABLE cp_documents
            ADD CONSTRAINT fk_docs_rejected_by FOREIGN KEY (rejected_by)
                REFERENCES cp_users(id) ON DELETE SET NULL;
    ",

    '0017_add_new_modules_tables' => "
        CREATE TABLE IF NOT EXISTS cp_notifications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'info',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            read_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_notif_user (user_id, read_at),
            CONSTRAINT fk_notif_user FOREIGN KEY (user_id)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS cp_comments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            comment TEXT NOT NULL,
            resolved_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_comm_doc (document_id),
            CONSTRAINT fk_comm_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_comm_user FOREIGN KEY (user_id)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS cp_shared_links (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            password_hash VARCHAR(255) DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            views_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_shared_token (token),
            CONSTRAINT fk_slink_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_slink_user FOREIGN KEY (created_by)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS cp_attachments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            size_bytes INT UNSIGNED NOT NULL,
            uploaded_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_attach_doc (document_id),
            CONSTRAINT fk_attach_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_attach_user FOREIGN KEY (uploaded_by)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS cp_document_subscribers (
            document_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (document_id, user_id),
            CONSTRAINT fk_sub_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_sub_user FOREIGN KEY (user_id)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        ALTER TABLE cp_documents
            ADD COLUMN confidentiality_level ENUM('publico', 'interno', 'confidencial', 'restrito') NOT NULL DEFAULT 'interno';
    ",

    '0018_create_logs_table' => "
        CREATE TABLE IF NOT EXISTS cp_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            action VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            user_login VARCHAR(50) DEFAULT NULL,
            user_name VARCHAR(120) DEFAULT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_logs_action (action),
            KEY idx_logs_user (user_login),
            KEY idx_logs_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    '0019_add_resolved_by_to_comments' => "
        ALTER TABLE cp_comments ADD COLUMN resolved_by INT UNSIGNED DEFAULT NULL AFTER resolved_at;
        ALTER TABLE cp_comments ADD CONSTRAINT fk_comm_resolved_by FOREIGN KEY (resolved_by) REFERENCES cp_users(id) ON DELETE SET NULL;
    ",

    '0020_create_document_followers' => "
        CREATE TABLE IF NOT EXISTS cp_document_followers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_doc_follow (document_id, user_id),
            CONSTRAINT fk_follow_doc FOREIGN KEY (document_id)
                REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_follow_user FOREIGN KEY (user_id)
                REFERENCES cp_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    '0021_add_is_public_to_documents' => "
        ALTER TABLE cp_documents ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0;
        CREATE INDEX idx_docs_is_public ON cp_documents (is_public, status, deleted_at);
    ",

    '0022_create_locations_table' => "
        CREATE TABLE IF NOT EXISTS cp_locations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            description VARCHAR(255) NULL,
            status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_locations_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    '0023_add_location_id_to_users' => "
        ALTER TABLE cp_users ADD COLUMN location_id INT UNSIGNED DEFAULT NULL AFTER role;
        ALTER TABLE cp_users ADD CONSTRAINT fk_users_location FOREIGN KEY (location_id) REFERENCES cp_locations(id) ON DELETE SET NULL;
    ",

    '0024_add_restrictions_to_documents' => "
        ALTER TABLE cp_documents ADD COLUMN is_restricted TINYINT(1) NOT NULL DEFAULT 0 AFTER is_public;
        CREATE INDEX idx_docs_is_restricted ON cp_documents (is_restricted, status, deleted_at);

        CREATE TABLE IF NOT EXISTS cp_document_locations (
            document_id INT UNSIGNED NOT NULL,
            location_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (document_id, location_id),
            CONSTRAINT fk_docloc_doc FOREIGN KEY (document_id) REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_docloc_loc FOREIGN KEY (location_id) REFERENCES cp_locations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    '0025_separate_email_and_username_in_users' => "
        ALTER TABLE cp_users ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER username;
    ",

    '0026_add_issue_approval_flow_to_comments' => "
        ALTER TABLE cp_comments ADD COLUMN status ENUM('aberto', 'resolvido_pendente', 'concluido') NOT NULL DEFAULT 'aberto' AFTER comment;
        ALTER TABLE cp_comments ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER resolved_by;
        ALTER TABLE cp_comments ADD COLUMN approved_by INT UNSIGNED DEFAULT NULL AFTER approved_at;
        ALTER TABLE cp_comments ADD CONSTRAINT fk_comm_approved_by FOREIGN KEY (approved_by) REFERENCES cp_users(id) ON DELETE SET NULL;
    ",

    '0027_add_title_category_to_document_versions' => "
        ALTER TABLE cp_document_versions ADD COLUMN title VARCHAR(255) DEFAULT NULL AFTER document_id;
        ALTER TABLE cp_document_versions ADD COLUMN category_id INT UNSIGNED DEFAULT NULL AFTER title;
        UPDATE cp_document_versions v JOIN cp_documents d ON v.document_id = d.id SET v.title = d.title, v.category_id = d.category_id WHERE v.title IS NULL;
    ",

    '0028_create_areas_and_add_user_doc_area_restrictions' => "
        CREATE TABLE IF NOT EXISTS cp_areas (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            description VARCHAR(255) NULL,
            status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_areas_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        INSERT IGNORE INTO cp_areas (name, description, status) VALUES 
        ('Tecnologia da Informação', 'Setor de TI e Infraestrutura', 'ativo'),
        ('Recursos Humanos', 'Setor de RH e Gestão de Pessoas', 'ativo'),
        ('Financeiro', 'Setor Financeiro e Contabilidade', 'ativo'),
        ('Comercial', 'Setor Comercial e Vendas', 'ativo'),
        ('Operações', 'Setor Operacional', 'ativo'),
        ('Jurídico', 'Setor Jurídico e Compliance', 'ativo'),
        ('Diretoria', 'Diretoria e Execução', 'ativo');

        ALTER TABLE cp_users ADD COLUMN area_id INT UNSIGNED DEFAULT NULL AFTER location_id;
        ALTER TABLE cp_users ADD CONSTRAINT fk_users_area FOREIGN KEY (area_id) REFERENCES cp_areas(id) ON DELETE SET NULL;

        CREATE TABLE IF NOT EXISTS cp_document_areas (
            document_id INT UNSIGNED NOT NULL,
            area_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (document_id, area_id),
            CONSTRAINT fk_docarea_doc FOREIGN KEY (document_id) REFERENCES cp_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_docarea_area FOREIGN KEY (area_id) REFERENCES cp_areas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    '0029_create_approval_requests_table' => "
        CREATE TABLE IF NOT EXISTS cp_approval_requests (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id INT UNSIGNED NOT NULL,
            requested_by INT UNSIGNED NOT NULL,
            approver_id INT UNSIGNED NOT NULL,
            status ENUM('pendente','aprovado','rejeitado','cancelado') NOT NULL DEFAULT 'pendente',
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            decided_at DATETIME NULL,
            decided_by INT UNSIGNED NULL,
            decision_reason TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_approval_token (token),
            KEY idx_approval_doc_status (document_id, status),
            KEY idx_approval_approver_status (approver_id, status),
            CONSTRAINT fk_approval_doc FOREIGN KEY (document_id) REFERENCES cp_documents(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
];

$stmt = $db->query('SELECT name FROM cp_migrations');
$applied = $stmt->fetchAll(\PDO::FETCH_COLUMN);
$appliedMap = is_array($applied) ? array_flip($applied) : [];

function isIgnorableMigrationError(\PDOException $e): bool
{
    $sqlState = (string) ($e->errorInfo[0] ?? '');
    $driverCode = (int) ($e->errorInfo[1] ?? 0);
    $msg = $e->getMessage();

    // Common MySQL/MariaDB error codes for schema elements that already exist or were already dropped:
    // 1005: Can't create table / errno: 121 (Duplicate key on write or update - FK constraint exists)
    // 1050: Table already exists
    // 1060: Duplicate column name
    // 1061: Duplicate key name / Index exists
    // 1062: Duplicate entry for key
    // 1091: Can't DROP / Key doesn't exist
    // 1826: Duplicate constraint name
    $ignorableDriverCodes = [1005, 1050, 1060, 1061, 1062, 1091, 1826];
    if (in_array($driverCode, $ignorableDriverCodes, true)) {
        return true;
    }

    $ignorableStates = ['42S01', '42S21'];
    if (in_array($sqlState, $ignorableStates, true)) {
        return true;
    }

    if (
        str_contains($msg, 'errno: 121') ||
        str_contains($msg, 'Duplicate key') ||
        str_contains($msg, 'already exists') ||
        str_contains($msg, 'Duplicate constraint')
    ) {
        return true;
    }

    return false;
}

// Auto-fix integrity check: Rename login to username if needed & ensure email column exists
try {
    $checkUserCols = $db->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'cp_users'")->fetchAll(\PDO::FETCH_COLUMN);
    if (is_array($checkUserCols)) {
        if (in_array('login', $checkUserCols, true) && !in_array('username', $checkUserCols, true)) {
            $db->exec("ALTER TABLE cp_users CHANGE COLUMN login username VARCHAR(50) NOT NULL");
            echo "  [AUTO-FIX] Coluna 'cp_users.login' renomeada para 'username'." . PHP_EOL;
        }
        if (!in_array('email', $checkUserCols, true)) {
            $db->exec("ALTER TABLE cp_users ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER username");
            echo "  [AUTO-FIX] Coluna 'cp_users.email' criada." . PHP_EOL;
        }
    }
} catch (\Throwable $t) {
    // Tabela cp_users ainda não criada
}

// Verificações de integridade estrutural
$integrityChecks = [
    'cp_users' => [
        'location_id' => "ALTER TABLE cp_users ADD COLUMN location_id INT UNSIGNED DEFAULT NULL AFTER role",
        'area_id' => "ALTER TABLE cp_users ADD COLUMN area_id INT UNSIGNED DEFAULT NULL AFTER location_id",
        'email' => "ALTER TABLE cp_users ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER username",
    ],
    'cp_comments' => [
        'resolved_by' => "ALTER TABLE cp_comments ADD COLUMN resolved_by INT UNSIGNED DEFAULT NULL AFTER resolved_at",
        'status' => "ALTER TABLE cp_comments ADD COLUMN status ENUM('aberto', 'resolvido_pendente', 'concluido') NOT NULL DEFAULT 'aberto' AFTER comment",
        'approved_at' => "ALTER TABLE cp_comments ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER resolved_by",
        'approved_by' => "ALTER TABLE cp_comments ADD COLUMN approved_by INT UNSIGNED DEFAULT NULL AFTER approved_at",
    ],
    'cp_documents' => [
        'deleted_at' => "ALTER TABLE cp_documents ADD COLUMN deleted_at DATETIME DEFAULT NULL",
        'status' => "ALTER TABLE cp_documents ADD COLUMN status ENUM('rascunho', 'pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente'",
        'rejected_by' => "ALTER TABLE cp_documents ADD COLUMN rejected_by INT UNSIGNED DEFAULT NULL AFTER approved_by",
        'confidentiality_level' => "ALTER TABLE cp_documents ADD COLUMN confidentiality_level ENUM('publico', 'interno', 'confidencial', 'restrito') NOT NULL DEFAULT 'interno'",
        'views_count' => "ALTER TABLE cp_documents ADD COLUMN views_count INT UNSIGNED NOT NULL DEFAULT 0",
        'is_restricted' => "ALTER TABLE cp_documents ADD COLUMN is_restricted TINYINT(1) NOT NULL DEFAULT 0 AFTER is_public",
    ],
    'cp_doc_categories' => [
        'color' => "ALTER TABLE cp_doc_categories ADD COLUMN color VARCHAR(20) NOT NULL DEFAULT '#1abb9c'",
    ],
    'cp_document_versions' => [
        'title' => "ALTER TABLE cp_document_versions ADD COLUMN title VARCHAR(255) DEFAULT NULL AFTER document_id",
        'category_id' => "ALTER TABLE cp_document_versions ADD COLUMN category_id INT UNSIGNED DEFAULT NULL AFTER title",
    ],
];

foreach ($integrityChecks as $table => $columns) {
    foreach ($columns as $column => $alterSql) {
        try {
            $checkStmt = $db->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
            $checkStmt->execute([$table, $column]);
            if ($checkStmt->fetchColumn() === false) {
                $db->exec($alterSql);
                echo sprintf("  [AUTO-FIX] Coluna '%s.%s' criada." . PHP_EOL, $table, $column);
            }
        } catch (\Throwable $t) {
            // Ignora se a tabela ainda não existir (será criada pelas migrações)
        }
    }
}

$count = 0;
foreach ($migrations as $name => $sql) {
    $key = (string) $name;
    if (isset($appliedMap[$key])) {
        continue;
    }

    $statements = array_filter(array_map('trim', explode(';', (string) $sql)));
    $migrationSuccess = true;

    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }

        try {
            $db->exec($statement);
        } catch (\PDOException $e) {
            if (isIgnorableMigrationError($e)) {
                echo sprintf("  [AVISO] (%s) %s" . PHP_EOL, $key, $e->getMessage());
            } else {
                fwrite(STDERR, sprintf("  [ERRO] Migração '%s' falhou: %s" . PHP_EOL, $key, $e->getMessage()));
                $migrationSuccess = false;
                break;
            }
        }
    }

    if ($migrationSuccess) {
        $insert = $db->prepare('INSERT IGNORE INTO cp_migrations (name) VALUES (?)');
        $insert->execute([$key]);
        echo 'Aplicada migração: ' . $key . PHP_EOL;
        $count++;
    } else {
        fwrite(STDERR, sprintf("Execução de migrações interrompida na migração '%s'." . PHP_EOL, $key));
        exit(1);
    }
}

echo $count === 0 ? 'Nenhuma migração pendente.' . PHP_EOL : $count . ' migração(ões) processada(s).' . PHP_EOL;