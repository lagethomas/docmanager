-- =====================================================================
-- DocManager - Schema completo (MariaDB/MySQL).
-- Uso: mysql -u root -p < database.sql
-- Para aplicar incrementalmente, prefira: php bin/migrate.php
-- =====================================================================

CREATE DATABASE IF NOT EXISTS docmanager
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE docmanager;

-- ---------------------------------------------------------------------
-- cp_users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_settings
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_settings (
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_roles
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_migrations
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_migrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_migrations_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_logs (Auditoria)
-- ---------------------------------------------------------------------
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

-- =====================================================================
-- Dados iniciais
-- =====================================================================

-- Roles padrao
INSERT IGNORE INTO cp_roles (name, description) VALUES 
('Admin', 'Administrador do sistema'),
('Aprovador', 'Revisão e aprovação de documentos para publicação'),
('Editor', 'Criação e edição de documentos e pastas'),
('Visualizador', 'Acesso de somente leitura aos documentos');

-- Usuario padrao: admin / admin@1234
INSERT IGNORE INTO cp_users (login, name, password, role, status)
VALUES ('admin', 'Administrador', '$2y$12$8NcsVUHXH7Io5bVvnXdE7eE8Go2eJVFiKbVNAQe0W0SGXKwJcXUNm', 'Admin', 'ativo');

-- Configuracoes padrao
INSERT IGNORE INTO cp_settings (setting_key, setting_value) VALUES ('per_page', '10');

-- =====================================================================
-- Módulo Documentação
-- =====================================================================

-- ---------------------------------------------------------------------
-- cp_doc_categories (pastas hierárquicas)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_doc_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'folder',
    color VARCHAR(20) NOT NULL DEFAULT '#1abb9c',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_doc_cat_parent (parent_id),
    CONSTRAINT fk_doc_cat_parent FOREIGN KEY (parent_id)
        REFERENCES cp_doc_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_doc_tags
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_doc_tags (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_doc_tags_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_documents
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_documents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    category_id INT UNSIGNED DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    status ENUM('rascunho', 'pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
    approved_by INT UNSIGNED DEFAULT NULL,
    rejected_by INT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    confidentiality_level ENUM('publico', 'interno', 'confidencial', 'restrito') NOT NULL DEFAULT 'interno',
    deleted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_docs_category (category_id),
    KEY idx_docs_created_by (created_by),
    KEY idx_docs_status (status, deleted_at),
    KEY idx_docs_views (views_count),
    KEY idx_docs_deleted_at (deleted_at),
    FULLTEXT INDEX ft_docs (title, content),
    CONSTRAINT fk_docs_category FOREIGN KEY (category_id)
        REFERENCES cp_doc_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_created_by FOREIGN KEY (created_by)
        REFERENCES cp_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_docs_approved_by FOREIGN KEY (approved_by)
        REFERENCES cp_users(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_rejected_by FOREIGN KEY (rejected_by)
        REFERENCES cp_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_document_tags (pivot)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cp_document_tags (
    document_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (document_id, tag_id),
    CONSTRAINT fk_dtag_doc FOREIGN KEY (document_id)
        REFERENCES cp_documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_dtag_tag FOREIGN KEY (tag_id)
        REFERENCES cp_doc_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_document_versions
-- ---------------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_doc_uploads
-- ---------------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cp_document_shares
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- cp_document_favorites
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- cp_notifications
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- cp_comments
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- cp_shared_links
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- cp_attachments
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- cp_document_subscribers
-- ---------------------------------------------------------------------
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
