-- Migração: adiciona o papel "Pastor" (mesmo nível de acesso de Líder, com nome próprio).
-- Rode isso uma vez no phpMyAdmin (aba SQL, dentro do banco u758900106_cbp_lideres).

ALTER TABLE membros
  MODIFY papel ENUM('admin','pastor','lider','membro') NOT NULL DEFAULT 'membro';
