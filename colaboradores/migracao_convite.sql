-- Migração: convite de acesso por e-mail.
-- Rode isso uma vez no phpMyAdmin (aba SQL, dentro do banco u758900106_cbp_lideres).

ALTER TABLE membros
  ADD COLUMN token_acesso VARCHAR(64) NULL,
  ADD COLUMN token_acesso_expira DATETIME NULL;
