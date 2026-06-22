-- URL da documentação oficial por integração
ALTER TABLE `integracoes`
    ADD COLUMN `url_documentacao` VARCHAR(500) NULL COMMENT 'Link da documentação da API' AFTER `url_api_base`;

UPDATE `integracoes`
SET `url_documentacao` = 'https://documenter.getpostman.com/view/25025078/2s93XtzjTG#intro'
WHERE `codigo` = 'inteegra'
  AND (`url_documentacao` IS NULL OR `url_documentacao` = '');
