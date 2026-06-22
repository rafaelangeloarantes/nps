-- ============================================================
-- NPS Relatórios — Schema completo
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Autenticação administrativa
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `senha_hash` VARCHAR(255) NOT NULL,
    `perfil` ENUM('admin_master','usuario') NOT NULL DEFAULT 'admin_master',
    `contrato_id` INT NULL,
    `perm_editar_evento` TINYINT(1) NOT NULL DEFAULT 0,
    `perm_sincronizar_evento` TINYINT(1) NOT NULL DEFAULT 0,
    `perm_editar_participante` TINYINT(1) NOT NULL DEFAULT 0,
    `perm_editar_pesquisa` TINYINT(1) NOT NULL DEFAULT 0,
    `perm_sincronizar_pesquisa` TINYINT(1) NOT NULL DEFAULT 0,
    `ultimo_login` DATETIME NULL,
    `tentativas_login` INT NOT NULL DEFAULT 0,
    `bloqueado_ate` DATETIME NULL,
    `token_reset` VARCHAR(64) NULL,
    `token_reset_expira` DATETIME NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `idx_usuarios_email` (`email`),
    KEY `idx_usuarios_contrato` (`contrato_id`),
    KEY `idx_usuarios_token_reset` (`token_reset`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Contratos (módulo único)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contratos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `link` VARCHAR(500) NULL,
    `usuario_acesso` VARCHAR(255) NULL,
    `senha_acesso` TEXT NULL COMMENT 'Criptografada',
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_contratos_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Eventos (1 contrato)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eventos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contrato_id` INT NOT NULL,
    `nome` VARCHAR(255) NOT NULL,
    `link` VARCHAR(500) NULL,
    `usuario_acesso` VARCHAR(255) NULL,
    `senha_acesso` TEXT NULL COMMENT 'Criptografada',
    `data_inicio` DATETIME NULL,
    `data_fim` DATETIME NULL,
    `endereco` VARCHAR(500) NULL,
    `clima` VARCHAR(100) NULL,
    `id_integracao` VARCHAR(100) NULL COMMENT 'ID no sistema de origem',
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `ultima_sincronizacao` DATETIME NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_eventos_contrato` (`contrato_id`),
    KEY `idx_eventos_ativo` (`ativo`),
    KEY `idx_eventos_id_integracao` (`id_integracao`),
    CONSTRAINT `fk_eventos_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Participantes (único na ferramenta — chave: e-mail)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `participantes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
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

-- ------------------------------------------------------------
-- Vínculo participante ↔ eventos (N eventos)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `participante_eventos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `participante_id` INT NOT NULL,
    `evento_id` INT NOT NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_pe_participante_evento` (`participante_id`, `evento_id`),
    KEY `idx_pe_evento` (`evento_id`),
    CONSTRAINT `fk_pe_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pe_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Dados de comportamento por evento (campos extras importados)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `participante_evento_dados` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `participante_id` INT NOT NULL,
    `evento_id` INT NOT NULL,
    `dados_json` JSON NULL COMMENT 'Campos dinâmicos importados por evento',
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_ped_participante_evento` (`participante_id`, `evento_id`),
    CONSTRAINT `fk_ped_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ped_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Configuração de plotagem — campos de participante por evento
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `participante_plotagem` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `evento_id` INT NOT NULL,
    `campo` VARCHAR(100) NOT NULL COMMENT 'Nome do campo (ex: estado, cargo)',
    `tipo_grafico` ENUM('pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'pie',
    `titulo_grafico` VARCHAR(255) NULL,
    `ordem` INT NOT NULL DEFAULT 0,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_pp_evento` (`evento_id`),
    CONSTRAINT `fk_pp_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Credenciamento (1 evento + 1 participante — SHOW/NOSHOW)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `credenciamentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `evento_id` INT NOT NULL,
    `participante_id` INT NOT NULL,
    `status` ENUM('SHOW','NOSHOW') NOT NULL DEFAULT 'NOSHOW',
    `ultima_sincronizacao` DATETIME NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `idx_cred_evento_participante` (`evento_id`, `participante_id`),
    KEY `idx_cred_participante` (`participante_id`),
    CONSTRAINT `fk_cred_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cred_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Pesquisas (1 evento + identificador de integração)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pesquisas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `evento_id` INT NOT NULL,
    `nome` VARCHAR(255) NOT NULL,
    `identificador_integracao` VARCHAR(100) NOT NULL COMMENT 'ID no sistema externo',
    `ultima_sincronizacao` DATETIME NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_pesquisas_evento` (`evento_id`),
    KEY `idx_pesquisas_identificador` (`identificador_integracao`),
    CONSTRAINT `fk_pesquisas_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Campos mapeados da pesquisa (colunas importadas + plotagem)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pesquisa_campos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pesquisa_id` INT NOT NULL,
    `campo_origem` VARCHAR(150) NOT NULL COMMENT 'Nome do campo no sistema externo',
    `campo_label` VARCHAR(255) NULL,
    `importar` TINYINT(1) NOT NULL DEFAULT 1,
    `tipo_grafico` ENUM('none','pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'none',
    `titulo_grafico` VARCHAR(255) NULL,
    `ordem` INT NOT NULL DEFAULT 0,
    KEY `idx_pc_pesquisa` (`pesquisa_id`),
    CONSTRAINT `fk_pc_pesquisa` FOREIGN KEY (`pesquisa_id`) REFERENCES `pesquisas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Respostas da pesquisa (vínculo via e-mail do participante)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pesquisa_respostas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pesquisa_id` INT NOT NULL,
    `email_participante` VARCHAR(255) NOT NULL,
    `participante_id` INT NULL,
    `dados_json` JSON NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_pr_pesquisa` (`pesquisa_id`),
    KEY `idx_pr_email` (`email_participante`),
    KEY `idx_pr_participante` (`participante_id`),
    CONSTRAINT `fk_pr_pesquisa` FOREIGN KEY (`pesquisa_id`) REFERENCES `pesquisas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pr_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Log de sincronizações (integração futura)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sincronizacao_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entidade` ENUM('evento','participante','credenciamento','pesquisa') NOT NULL,
    `entidade_id` INT NOT NULL,
    `tipo` ENUM('api','arquivo','manual') NOT NULL DEFAULT 'manual',
    `status` ENUM('sucesso','erro','parcial') NOT NULL,
    `mensagem` TEXT NULL,
    `registros_processados` INT NOT NULL DEFAULT 0,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sync_entidade` (`entidade`, `entidade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `usuarios`
    ADD CONSTRAINT `fk_usuarios_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
