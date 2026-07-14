-- CleanTeam Salemanager - Datenbankschema (MySQL 5.7+/MariaDB 10.3+)
-- Einspielen z. B. per phpMyAdmin im All-Inklusive-Hosting-Paket.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
  id VARCHAR(64) NOT NULL,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(60) NOT NULL,
  salutation VARCHAR(20) NOT NULL,
  contact_last_name VARCHAR(120) NOT NULL,
  address VARCHAR(190) NOT NULL,
  house_number VARCHAR(20) NOT NULL,
  zip VARCHAR(20) NOT NULL,
  city VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_visits (
  id VARCHAR(64) NOT NULL,
  customer_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(60) NOT NULL,
  address VARCHAR(255) NOT NULL,
  onsite_contact VARCHAR(190) NOT NULL,
  square_meters INT UNSIGNED NOT NULL,
  floors_json LONGTEXT NOT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS offers (
  id VARCHAR(64) NOT NULL,
  customer_id VARCHAR(64) NOT NULL,
  square_meters INT UNSIGNED NOT NULL,
  interval_label VARCHAR(40) NOT NULL,
  service VARCHAR(80) NOT NULL,
  start_date DATE NULL,
  notes TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  token VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  sent_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_offers_token (token),
  KEY idx_offers_customer (customer_id),
  -- Bewusst kein ON DELETE CASCADE: ein Kunde mit bestehenden Kostenvoranschlaegen/Vertraegen
  -- darf nicht geloescht werden, damit Vertragshistorie nicht verloren geht.
  CONSTRAINT fk_offers_customer FOREIGN KEY (customer_id) REFERENCES customers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contracts (
  id VARCHAR(64) NOT NULL,
  offer_id VARCHAR(64) NOT NULL,
  customer_id VARCHAR(64) NOT NULL,
  number VARCHAR(40) NOT NULL,
  -- status: entwurf | daten_abgelehnt | intervall_abgelehnt | signiert
  status VARCHAR(30) NOT NULL DEFAULT 'entwurf',
  -- current_step: daten | intervall | vollmacht | vertragspartner | leistung | bedingungen | signatur | fertig
  current_step VARCHAR(30) NOT NULL DEFAULT 'daten',
  data_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  interval_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  authorized TINYINT(1) NULL,
  representation_note TEXT NULL,
  terms_accepted_at DATETIME NULL,
  signed_at DATETIME NULL,
  signature_data LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_contracts_offer (offer_id),
  KEY idx_contracts_customer (customer_id),
  CONSTRAINT fk_contracts_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE,
  CONSTRAINT fk_contracts_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contract_documents (
  id VARCHAR(64) NOT NULL,
  contract_id VARCHAR(64) NOT NULL,
  audience VARCHAR(20) NOT NULL,
  filename VARCHAR(160) NOT NULL,
  mime_type VARCHAR(80) NOT NULL DEFAULT 'application/pdf',
  content LONGBLOB NOT NULL,
  sha256 CHAR(64) NOT NULL,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_contract_documents_contract_audience (contract_id, audience),
  KEY idx_contract_documents_contract (contract_id),
  CONSTRAINT fk_contract_documents_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS smtp_settings (
  id TINYINT UNSIGNED NOT NULL,
  host VARCHAR(190) NOT NULL DEFAULT '',
  port SMALLINT UNSIGNED NOT NULL DEFAULT 587,
  -- encryption: none | ssl | tls
  encryption VARCHAR(10) NOT NULL DEFAULT 'tls',
  username VARCHAR(190) NOT NULL DEFAULT '',
  password_encrypted TEXT NULL,
  from_name VARCHAR(190) NOT NULL DEFAULT 'CleanTeam',
  from_email VARCHAR(190) NOT NULL DEFAULT '',
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO smtp_settings (id, host, port, encryption, username, from_name, from_email)
VALUES (1, '', 587, 'tls', '', 'CleanTeam', '');

CREATE TABLE IF NOT EXISTS mailbox_settings (
  id TINYINT UNSIGNED NOT NULL,
  host VARCHAR(190) NOT NULL DEFAULT '',
  imap_port SMALLINT UNSIGNED NOT NULL DEFAULT 993,
  imap_encryption VARCHAR(10) NOT NULL DEFAULT 'ssl',
  smtp_port SMALLINT UNSIGNED NOT NULL DEFAULT 587,
  smtp_encryption VARCHAR(10) NOT NULL DEFAULT 'tls',
  username VARCHAR(190) NOT NULL DEFAULT '',
  password_encrypted TEXT NULL,
  from_name VARCHAR(190) NOT NULL DEFAULT 'CleanTeam',
  signature TEXT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO mailbox_settings (id, host, imap_port, imap_encryption, smtp_port, smtp_encryption, username, from_name)
VALUES (1, '', 993, 'ssl', 587, 'tls', '', 'CleanTeam');

CREATE TABLE IF NOT EXISTS contract_notifications (
  id TINYINT UNSIGNED NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  recipients TEXT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO contract_notifications (id, enabled, recipients)
VALUES (1, 0, '');

CREATE TABLE IF NOT EXISTS branding_settings (
  id TINYINT UNSIGNED NOT NULL,
  logo_filename VARCHAR(190) NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO branding_settings (id, logo_filename) VALUES (1, NULL);

-- Demo-Kunden, damit das Dashboard nach der Installation nicht leer ist.
-- Koennen jederzeit im Dashboard geloescht werden.
INSERT IGNORE INTO customers (id, name, email, phone, salutation, contact_last_name, address, house_number, zip, city, created_at)
VALUES
  ('customer-1', 'Musterbau GmbH', 'kontakt@musterbau.de', '+49 711 245678', 'Frau', 'Schneider', 'Königstraße', '18', '70173', 'Stuttgart', '2026-07-01 10:10:00'),
  ('customer-2', 'Praxis am Park', 'verwaltung@praxis-park.de', '+49 7153 808080', 'Herr', 'Weber', 'Parkallee', '7', '73728', 'Esslingen', '2026-07-02 08:30:00');

-- Hinweis zum Login: Beim allerersten Login (users-Tabelle ist leer) werden die
-- eingegebene E-Mail-Adresse und das Passwort automatisch als erstes
-- Admin-Konto angelegt (siehe api/login.php). Es muss hier kein Nutzer
-- manuell per SQL angelegt werden.
