-- Schema del database "Conti Famiglia"
-- Viene eseguito automaticamente al primo avvio del container MySQL
-- (montato in /docker-entrypoint-initdb.d)

CREATE TABLE IF NOT EXISTS utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    anno INT NULL,                       -- valorizzato solo se è un conto principale (parent_conto_id IS NULL)
    user_id INT NOT NULL,
    parent_conto_id INT NULL,            -- NULL = conto principale, altrimenti sotto-conto (livelli infiniti)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conti_user FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    CONSTRAINT fk_conti_parent FOREIGN KEY (parent_conto_id) REFERENCES conti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS record (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conto_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,          -- categoria: "Cibo", "Benzina", "Stipendio"...
    soldi DECIMAL(10,2) NOT NULL,        -- negativo se costo, positivo se entrata
    tipo ENUM('Contanti', 'Bancomat') NOT NULL,
    data DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_record_conto FOREIGN KEY (conto_id) REFERENCES conti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_conti_user ON conti(user_id);
CREATE INDEX idx_conti_parent ON conti(parent_conto_id);
CREATE INDEX idx_record_conto ON record(conto_id);
CREATE INDEX idx_record_data ON record(data);
CREATE INDEX idx_record_nome ON record(nome);
