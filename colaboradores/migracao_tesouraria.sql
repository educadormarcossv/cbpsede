-- Migração: módulo de Tesouraria.
-- Rode isso uma vez no phpMyAdmin (aba SQL, dentro do banco u758900106_cbp_lideres).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lancamentos_financeiros (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('entrada','saida') NOT NULL,
  categoria VARCHAR(80),
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  data_lancamento DATE NOT NULL,
  forma_pagamento VARCHAR(60),
  comprovante_caminho VARCHAR(255),
  observacoes TEXT,
  criado_por INT UNSIGNED,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (criado_por) REFERENCES membros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
