-- Mapeamento de atributos Guests (API Inteegra) por evento
CREATE TABLE IF NOT EXISTS `evento_atributo_mapeamento` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `evento_id` INT NOT NULL,
    `atributo_nome` VARCHAR(255) NOT NULL,
    `atributo_id_api` INT NULL,
    `campo_destino` ENUM('extra','nome_completo','email','telefone','cargo','empresa','estado','cidade','data_nascimento','linkedin') NOT NULL DEFAULT 'extra',
    `importar` TINYINT(1) NOT NULL DEFAULT 1,
    `tipo_grafico` ENUM('none','pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'none',
    `titulo_grafico` VARCHAR(255) NULL,
    `ordem` INT NOT NULL DEFAULT 0,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_eam_evento_atributo` (`evento_id`, `atributo_nome`),
    KEY `idx_eam_evento` (`evento_id`),
    CONSTRAINT `fk_eam_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `participante_eventos`
    ADD COLUMN `guest_id_api` INT NULL COMMENT 'Id do guest na API Inteegra' AFTER `evento_id`;
