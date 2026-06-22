-- Módulo de Log do Sistema — extensão da auditoria centralizada

ALTER TABLE `sistema_auditoria`
    ADD COLUMN IF NOT EXISTS `tipo` ENUM('acao','integracao','erro') NOT NULL DEFAULT 'acao' AFTER `id`,
    ADD COLUMN IF NOT EXISTS `nivel` ENUM('info','aviso','erro') NOT NULL DEFAULT 'info' AFTER `tipo`,
    ADD COLUMN IF NOT EXISTS `mensagem` TEXT NULL AFTER `acao`;

ALTER TABLE `sistema_auditoria`
    ADD KEY IF NOT EXISTS `idx_sa_tipo` (`tipo`),
    ADD KEY IF NOT EXISTS `idx_sa_nivel` (`nivel`);
