-- Módulo Dashboard: templates, relatórios e auditoria

CREATE TABLE IF NOT EXISTS `dashboard_templates` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `descricao` TEXT NULL,
    `contrato_id` INT NULL COMMENT 'NULL = disponível para todos (master)',
    `layout_json` JSON NOT NULL,
    `criado_por` INT NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_dt_contrato` (`contrato_id`),
    KEY `idx_dt_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dashboard_relatorios` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT NOT NULL,
    `evento_id` INT NOT NULL,
    `nome` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `chave_hash` VARCHAR(255) NOT NULL,
    `chave_prefixo` VARCHAR(8) NULL COMMENT 'Prefixo visível da chave',
    `criado_por` INT NULL,
    `ultimo_acesso_externo` DATETIME NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `idx_dr_token` (`token`),
    KEY `idx_dr_template` (`template_id`),
    KEY `idx_dr_evento` (`evento_id`),
    KEY `idx_dr_ativo` (`ativo`),
    CONSTRAINT `fk_dr_template` FOREIGN KEY (`template_id`) REFERENCES `dashboard_templates` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_dr_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sistema_auditoria` (
    `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `modulo` VARCHAR(50) NOT NULL,
    `acao` VARCHAR(50) NOT NULL,
    `entidade_id` INT NULL,
    `usuario_id` INT NULL,
    `ip` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `detalhes_json` JSON NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sa_modulo` (`modulo`, `entidade_id`),
    KEY `idx_sa_usuario` (`usuario_id`),
    KEY `idx_sa_criado` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
