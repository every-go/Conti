-- Schema del database "Conti Famiglia"
-- Viene eseguito automaticamente al primo avvio del container MySQL
-- (montato in /docker-entrypoint-initdb.d)

CREATE TABLE IF NOT EXISTS utenti (
    username VARCHAR(16) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    anno INT NULL,                       -- valorizzato solo se è un conto principale (parent_conto_id IS NULL)
    username VARCHAR(16) NOT NULL,
    parent_conto_id INT NULL,            -- NULL = conto principale, altrimenti sotto-conto (livelli infiniti)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conti_user FOREIGN KEY (username) REFERENCES utenti(username) ON DELETE CASCADE,
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

CREATE INDEX idx_conti_user ON conti(username);
CREATE INDEX idx_conti_parent ON conti(parent_conto_id);
CREATE INDEX idx_record_conto ON record(conto_id);
CREATE INDEX idx_record_data ON record(data);
CREATE INDEX idx_record_nome ON record(nome);
