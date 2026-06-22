-- Migration 006: Governança de usuários, perfis e recuperação de senha

-- Novos campos e perfis na tabela usuarios
ALTER TABLE `usuarios`
    MODIFY COLUMN `perfil` ENUM('admin_master','usuario','admin','editor','viewer') NOT NULL DEFAULT 'admin_master';

ALTER TABLE `usuarios`
    ADD COLUMN `contrato_id` INT NULL AFTER `perfil`,
    ADD COLUMN `perm_editar_evento` TINYINT(1) NOT NULL DEFAULT 0 AFTER `contrato_id`,
    ADD COLUMN `perm_sincronizar_evento` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_editar_evento`,
    ADD COLUMN `perm_editar_participante` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_sincronizar_evento`,
    ADD COLUMN `perm_editar_pesquisa` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_editar_participante`,
    ADD COLUMN `perm_sincronizar_pesquisa` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_editar_pesquisa`,
    ADD COLUMN `token_reset` VARCHAR(64) NULL AFTER `bloqueado_ate`,
    ADD COLUMN `token_reset_expira` DATETIME NULL AFTER `token_reset`,
    ADD KEY `idx_usuarios_contrato` (`contrato_id`),
    ADD KEY `idx_usuarios_token_reset` (`token_reset`),
    ADD CONSTRAINT `fk_usuarios_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL;

-- Migrar perfis legados
UPDATE `usuarios` SET `perfil` = 'admin_master' WHERE `perfil` IN ('admin', 'editor');
UPDATE `usuarios` SET `perfil` = 'usuario' WHERE `perfil` = 'viewer';

-- Ajustar ENUM final (remove valores legados)
ALTER TABLE `usuarios`
    MODIFY COLUMN `perfil` ENUM('admin_master','usuario') NOT NULL DEFAULT 'admin_master';
