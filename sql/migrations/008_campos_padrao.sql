-- Estrutura de Campos Padrão NPS + vínculo De/Para nas importações

CREATE TABLE IF NOT EXISTS `nps_campos_padrao` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `categoria` ENUM('participante','evento','pesquisa','credenciamento') NOT NULL DEFAULT 'evento',
    `tipo_dado` ENUM('texto','numero','data','nps','email','telefone') NOT NULL DEFAULT 'texto',
    `tipo_grafico_sugerido` ENUM('none','pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'bar',
    `mapeia_participante` VARCHAR(100) NULL COMMENT 'Coluna em participantes quando aplicável',
    `sistema` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = campo nativo, não excluível',
    `ordem` INT NOT NULL DEFAULT 0,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `idx_ncp_slug` (`slug`),
    KEY `idx_ncp_categoria` (`categoria`),
    KEY `idx_ncp_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
