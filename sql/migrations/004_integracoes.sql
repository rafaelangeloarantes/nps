-- Integrações externas (credenciais e URLs centralizadas)
CREATE TABLE IF NOT EXISTS `integracoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) NOT NULL COMMENT 'Identificador interno: inteegra, etc.',
    `nome` VARCHAR(255) NOT NULL,
    `descricao` TEXT NULL,
    `url_auth_base` VARCHAR(500) NULL COMMENT 'Base para autenticação/token',
    `url_api_base` VARCHAR(500) NULL COMMENT 'Base da API de dados',
    `url_documentacao` VARCHAR(500) NULL COMMENT 'Link da documentação da API',
    `usuario_acesso` VARCHAR(255) NULL,
    `senha_acesso` TEXT NULL COMMENT 'Criptografada',
    `config_json` JSON NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_integracoes_codigo` (`codigo`),
    KEY `idx_integracoes_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `integracoes` (`codigo`, `nome`, `descricao`, `url_auth_base`, `url_api_base`, `url_documentacao`, `ativo`)
SELECT 'inteegra', 'Inteegra API', 'Integração com a API externa Inteegra para importação de participantes (guests).',
       'https://api-externa.inteegra.com.br/security/security',
       'https://api-externa.inteegra.com.br/public',
       'https://documenter.getpostman.com/view/25025078/2s93XtzjTG#intro', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `integracoes` WHERE `codigo` = 'inteegra' LIMIT 1);
