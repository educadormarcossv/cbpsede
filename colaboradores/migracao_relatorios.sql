-- Migração: campo sexo em membros, para os relatórios de Mulheres/Homens.
-- Rode isso uma vez no phpMyAdmin (aba SQL, dentro do banco u758900106_cbp_lideres).

ALTER TABLE membros
  ADD COLUMN sexo ENUM('M','F') NULL AFTER data_nascimento;
