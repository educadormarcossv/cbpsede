-- Schema do Painel de Colaboradores - CBP Sede
-- Execute este arquivo uma vez no phpMyAdmin do banco criado no hPanel da Hostinger.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS familias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome_familia VARCHAR(150) NOT NULL,
  telefone VARCHAR(30),
  endereco VARCHAR(255),
  bairro VARCHAR(100),
  cidade VARCHAR(100),
  estado CHAR(2),
  cep VARCHAR(10),
  observacoes TEXT,
  criado_por INT UNSIGNED,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS membros (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(190) UNIQUE,
  senha_hash VARCHAR(255),
  papel ENUM('admin','lider','membro') NOT NULL DEFAULT 'membro',
  foto_caminho VARCHAR(255),
  data_nascimento DATE,
  telefone VARCHAR(30),
  endereco VARCHAR(255),
  bairro VARCHAR(100),
  cidade VARCHAR(100),
  estado CHAR(2),
  cep VARCHAR(10),
  estado_civil ENUM('solteiro','casado','viuvo','divorciado') NULL,
  familia_id INT UNSIGNED NULL,
  parentesco_familia VARCHAR(60),
  membro_desde DATE NULL,
  modo_recepcao ENUM('batismo','carta','aclamacao','reconciliacao') NULL,
  batizado TINYINT(1) NOT NULL DEFAULT 0,
  data_batismo DATE NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  observacoes TEXT,
  ultimo_acesso DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE familias
  ADD CONSTRAINT fk_familias_criado_por FOREIGN KEY (criado_por) REFERENCES membros(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS criancas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id INT UNSIGNED NOT NULL,
  nome VARCHAR(150) NOT NULL,
  data_nascimento DATE NULL,
  mae_nome VARCHAR(150),
  mae_telefone VARCHAR(30),
  pai_nome VARCHAR(150),
  pai_telefone VARCHAR(30),
  tem_alergia TINYINT(1) NOT NULL DEFAULT 0,
  alergia_qual VARCHAR(255),
  usa_medicamento TINYINT(1) NOT NULL DEFAULT 0,
  medicamento_qual VARCHAR(255),
  contato_emergencia_nome VARCHAR(150),
  contato_emergencia_telefone VARCHAR(30),
  pessoas_autorizadas_retirar TEXT,
  foto_caminho VARCHAR(255),
  batizado TINYINT(1) NOT NULL DEFAULT 0,
  autorizacao_imagem_em DATE NULL,
  observacoes TEXT,
  criado_por INT UNSIGNED,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES membros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ministerios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL UNIQUE,
  descricao TEXT,
  lider_id INT UNSIGNED NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lider_id) REFERENCES membros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS membros_ministerios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  membro_id INT UNSIGNED NOT NULL,
  ministerio_id INT UNSIGNED NOT NULL,
  funcao VARCHAR(80) DEFAULT 'Voluntário(a)',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (membro_id) REFERENCES membros(id) ON DELETE CASCADE,
  FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE CASCADE,
  UNIQUE KEY unica_participacao (membro_id, ministerio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ministerios (nome, descricao) VALUES
  ('Ministério Infantil', 'Cuidado, ensino bíblico e recreação para as crianças da igreja.'),
  ('Adolescentes', 'Discipulado e comunhão voltados para a fase de transição da adolescência.'),
  ('Jovens', 'Encontros, células e eventos para jovens crescerem juntos na fé.'),
  ('Casais', 'Aconselhamento e encontros voltados ao fortalecimento do casamento e da família.'),
  ('Células', 'Pequenos grupos que se reúnem nos bairros de São Vicente durante a semana.'),
  ('Louvor e Adoração', 'Equipe responsável por conduzir a igreja em momentos de adoração nos cultos.'),
  ('Ação Social', 'Cuidado prático com famílias em vulnerabilidade dentro e fora da igreja.'),
  ('Missões', 'Envio e sustento de missionários, no Brasil e no exterior.'),
  ('Escola Bíblica', 'Ensino sistemático das Escrituras para todas as idades.')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

CREATE TABLE IF NOT EXISTS eventos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT,
  data_evento DATE NOT NULL,
  hora_evento TIME NULL,
  local VARCHAR(150),
  categoria VARCHAR(80),
  criado_por INT UNSIGNED,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (criado_por) REFERENCES membros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  categoria VARCHAR(80),
  descricao TEXT,
  arquivo_caminho VARCHAR(255) NOT NULL,
  arquivo_nome_original VARCHAR(255) NOT NULL,
  tamanho_bytes INT UNSIGNED,
  enviado_por INT UNSIGNED,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enviado_por) REFERENCES membros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Primeiro acesso: crie o admin manualmente aqui (troque o e-mail e gere o hash com o PHP abaixo)
-- php -r "echo password_hash('SUA_SENHA_AQUI', PASSWORD_DEFAULT);"
-- INSERT INTO membros (nome, email, senha_hash, papel, ativo) VALUES ('Seu Nome', 'seu@email.com', 'COLE_O_HASH_AQUI', 'admin', 1);
