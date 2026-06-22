-- Migration incremental — módulo Relatórios (compatível com schema existente)
SET NAMES utf8mb4;

-- Contratos: credenciais e link
ALTER TABLE `contratos`
    ADD COLUMN `link` VARCHAR(500) NULL AFTER `nome`,
    ADD COLUMN `usuario_acesso` VARCHAR(255) NULL AFTER `link`,
    ADD COLUMN `senha_acesso` TEXT NULL AFTER `usuario_acesso`;

-- Eventos: dados operacionais e integração
ALTER TABLE `eventos`
    ADD COLUMN `link` VARCHAR(500) NULL AFTER `nome`,
    ADD COLUMN `usuario_acesso` VARCHAR(255) NULL AFTER `link`,
    ADD COLUMN `senha_acesso` TEXT NULL AFTER `usuario_acesso`,
    ADD COLUMN `data_inicio` DATETIME NULL AFTER `senha_acesso`,
    ADD COLUMN `data_fim` DATETIME NULL AFTER `data_inicio`,
    ADD COLUMN `endereco` VARCHAR(500) NULL AFTER `data_fim`,
    ADD COLUMN `clima` VARCHAR(100) NULL AFTER `endereco`,
    ADD COLUMN `id_integracao` VARCHAR(100) NULL AFTER `clima`,
    ADD COLUMN `ultima_sincronizacao` DATETIME NULL AFTER `id_integracao`;

-- Pesquisas: identificador externo
ALTER TABLE `pesquisas`
    ADD COLUMN `identificador_integracao` VARCHAR(100) NULL AFTER `titulo`,
    ADD COLUMN `ultima_sincronizacao` DATETIME NULL AFTER `identificador_integracao`;

-- Participantes
CREATE TABLE IF NOT EXISTS `participantes` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome_completo` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `telefone` VARCHAR(30) NULL,
    `cargo` VARCHAR(150) NULL,
    `empresa` VARCHAR(255) NULL,
    `estado` VARCHAR(2) NULL,
    `cidade` VARCHAR(150) NULL,
    `data_nascimento` DATE NULL,
    `linkedin` VARCHAR(500) NULL,
    `dado_incompleto` TINYINT(1) NOT NULL DEFAULT 0,
    `motivo_incompleto` ENUM('ok','sem_email','email_duplicado') NOT NULL DEFAULT 'ok',
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `ultima_sincronizacao` DATETIME NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_participantes_email` (`email`),
    KEY `idx_participantes_incompleto` (`dado_incompleto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `participante_eventos` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `participante_id` INT NOT NULL,
    `evento_id` INT NOT NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_pe_participante_evento` (`participante_id`, `evento_id`),
    KEY `idx_pe_evento` (`evento_id`),
    CONSTRAINT `fk_pe_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pe_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `participante_evento_dados` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `participante_id` INT NOT NULL,
    `evento_id` INT NOT NULL,
    `dados_json` JSON NULL,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_ped_participante_evento` (`participante_id`, `evento_id`),
    CONSTRAINT `fk_ped_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ped_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `participante_plotagem` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `evento_id` INT NOT NULL,
    `campo` VARCHAR(100) NOT NULL,
    `tipo_grafico` ENUM('pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'pie',
    `titulo_grafico` VARCHAR(255) NULL,
    `ordem` INT NOT NULL DEFAULT 0,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_pp_evento` (`evento_id`),
    CONSTRAINT `fk_pp_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `credenciamentos` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `evento_id` INT NOT NULL,
    `participante_id` INT NOT NULL,
    `status` ENUM('SHOW','NOSHOW') NOT NULL DEFAULT 'NOSHOW',
    `ultima_sincronizacao` DATETIME NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `idx_cred_evento_participante` (`evento_id`, `participante_id`),
    CONSTRAINT `fk_cred_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cred_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mapeamento de importação (separado de pesquisa_campos do formulário)
CREATE TABLE IF NOT EXISTS `relatorio_pesquisa_campos` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pesquisa_id` INT NOT NULL,
    `campo_origem` VARCHAR(150) NOT NULL,
    `campo_label` VARCHAR(255) NULL,
    `importar` TINYINT(1) NOT NULL DEFAULT 1,
    `tipo_grafico` ENUM('none','pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'none',
    `titulo_grafico` VARCHAR(255) NULL,
    `ordem` INT NOT NULL DEFAULT 0,
    KEY `idx_rpc_pesquisa` (`pesquisa_id`),
    CONSTRAINT `fk_rpc_pesquisa` FOREIGN KEY (`pesquisa_id`) REFERENCES `pesquisas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `relatorio_pesquisa_respostas` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pesquisa_id` INT NOT NULL,
    `email_participante` VARCHAR(255) NOT NULL,
    `participante_id` INT NULL,
    `dados_json` JSON NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_rpr_pesquisa` (`pesquisa_id`),
    KEY `idx_rpr_email` (`email_participante`),
    CONSTRAINT `fk_rpr_pesquisa` FOREIGN KEY (`pesquisa_id`) REFERENCES `pesquisas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rpr_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sincronizacao_log` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `entidade` ENUM('evento','participante','credenciamento','pesquisa') NOT NULL,
    `entidade_id` INT NOT NULL,
    `tipo` ENUM('api','arquivo','manual') NOT NULL DEFAULT 'manual',
    `status` ENUM('sucesso','erro','parcial') NOT NULL,
    `mensagem` TEXT NULL,
    `registros_processados` INT NOT NULL DEFAULT 0,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sync_entidade` (`entidade`, `entidade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
