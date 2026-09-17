-- Migração: acesso por ministério, agenda por ministério e escalas.
-- Rode isso uma vez no phpMyAdmin (aba SQL) do banco u758900106_cbp_lideres.

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN ministerio_id INT UNSIGNED NULL AFTER categoria,
  ADD CONSTRAINT fk_eventos_ministerio FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS escalas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ministerio_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  data_escala DATE NOT NULL,
  hora_escala TIME NULL,
  observacoes TEXT,
  criado_por INT UNSIGNED,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES membros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS escala_membros (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  escala_id INT UNSIGNED NOT NULL,
  membro_id INT UNSIGNED NOT NULL,
  funcao VARCHAR(80),
  confirmado TINYINT(1) NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (escala_id) REFERENCES escalas(id) ON DELETE CASCADE,
  FOREIGN KEY (membro_id) REFERENCES membros(id) ON DELETE CASCADE,
  UNIQUE KEY unica_pessoa_escala (escala_id, membro_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
