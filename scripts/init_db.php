<?php
require __DIR__ . '/../config/config.php';

$db = getDB();

$statements = [
    <<<SQL
CREATE TABLE IF NOT EXISTS clienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(150),
    note TEXT,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome_cognome (nome, cognome),
    INDEX idx_telefono (telefono),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS pratiche (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    data_apertura DATE NOT NULL,
    stato ENUM('Aperta','In corso','Completata','Annullata') DEFAULT 'Aperta',
    tipo_pratica ENUM('Patente entro 12 miglia','Patente oltre 12 miglia','Patente D1','Rinnovo','Duplicato','Altro') NOT NULL,
    tipo_altro_dettaglio VARCHAR(255),
    totale_previsto DECIMAL(10,2) DEFAULT 0.00,
    totale_pagato DECIMAL(10,2) DEFAULT 0.00,
    residuo DECIMAL(10,2) GENERATED ALWAYS AS (totale_previsto - totale_pagato) STORED,
    data_esame DATE,
    esito_esame ENUM('Superato','Non superato','In attesa'),
    data_conseguimento DATE,
    numero_patente VARCHAR(50),
    allegati TEXT,
    data_richiesta_rinnovo DATE,
    data_completamento_rinnovo DATE,
    note_rinnovo TEXT,
    motivo_duplicato ENUM('Smarrimento','Deterioramento','Altro'),
    motivo_duplicato_dettaglio VARCHAR(255),
    data_richiesta_duplicato DATE,
    data_chiusura_duplicato DATE,
    note TEXT,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_data_apertura (data_apertura),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo_pratica),
    INDEX idx_data_esame (data_esame)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS pagamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pratica_id INT NOT NULL,
    cliente_id INT NOT NULL,
    tipo_pagamento ENUM('Acconto','Saldo','Rata','Pagamento unico') NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    metodo_pagamento ENUM('Contanti','POS') NOT NULL,
    data_pagamento DATE NOT NULL,
    note TEXT,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pratica_id) REFERENCES pratiche(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE CASCADE,
    INDEX idx_pratica (pratica_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_data (data_pagamento),
    INDEX idx_metodo (metodo_pagamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS agenda_guide (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    pratica_id INT,
    data_guida DATE NOT NULL,
    orario_inizio TIME NOT NULL,
    orario_fine TIME NOT NULL,
    tipo_lezione VARCHAR(100),
    note TEXT,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE CASCADE,
    FOREIGN KEY (pratica_id) REFERENCES pratiche(id) ON DELETE SET NULL,
    INDEX idx_cliente (cliente_id),
    INDEX idx_pratica (pratica_id),
    INDEX idx_data (data_guida),
    INDEX idx_orario (orario_inizio, orario_fine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS spese (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_spesa DATE NOT NULL,
    categoria ENUM('Vincenzo','Luigi','Affitto barca','Benzina','Altro') NOT NULL,
    categoria_altro VARCHAR(100),
    importo DECIMAL(10,2) NOT NULL,
    descrizione TEXT,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_spesa),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS pratiche_allegati (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pratica_id INT NOT NULL,
    uploaded_by INT,
    filename_original VARCHAR(255) NOT NULL,
    filename_stored VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pratica_id) REFERENCES pratiche(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_pratica (pratica_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity, entity_id),
    INDEX idx_action (action),
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS auth_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_attempt TIMESTAMP NULL DEFAULT NULL,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_user_ip (username, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector VARCHAR(32) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    <<<SQL
CREATE TABLE IF NOT EXISTS scheduled_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_key VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    interval_minutes INT NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    last_run TIMESTAMP NULL DEFAULT NULL,
    last_status ENUM('ok','error') DEFAULT NULL,
    last_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    // Triggers (single statement each)
    "DROP TRIGGER IF EXISTS after_pagamento_insert;",
    "DROP TRIGGER IF EXISTS after_pagamento_update;",
    "DROP TRIGGER IF EXISTS after_pagamento_delete;",
    "CREATE TRIGGER after_pagamento_insert AFTER INSERT ON pagamenti FOR EACH ROW UPDATE pratiche SET totale_pagato = (SELECT COALESCE(SUM(importo),0) FROM pagamenti WHERE pratica_id = NEW.pratica_id) WHERE id = NEW.pratica_id;",
    "CREATE TRIGGER after_pagamento_update AFTER UPDATE ON pagamenti FOR EACH ROW UPDATE pratiche SET totale_pagato = (SELECT COALESCE(SUM(importo),0) FROM pagamenti WHERE pratica_id = NEW.pratica_id) WHERE id = NEW.pratica_id;",
    "CREATE TRIGGER after_pagamento_delete AFTER DELETE ON pagamenti FOR EACH ROW UPDATE pratiche SET totale_pagato = (SELECT COALESCE(SUM(importo),0) FROM pagamenti WHERE pratica_id = OLD.pratica_id) WHERE id = OLD.pratica_id;"
];

try {
    foreach ($statements as $sql) {
        $db->exec($sql);
    }
    echo "Schema creato/aggiornato con successo.\n";
} catch (PDOException $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
