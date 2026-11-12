<?php

function ensure_link_visits_table(PDO $pdo): void
{
    if ($pdo->query("SHOW TABLES LIKE 'offer_link_visits'")->fetch()) return;
    $pdo->exec('CREATE TABLE IF NOT EXISTS offer_link_visits (
    id VARCHAR(64) NOT NULL PRIMARY KEY,
    offer_id VARCHAR(64) NOT NULL,
    link_kind VARCHAR(20) NOT NULL,
    document_kind VARCHAR(30) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    opened_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    close_reason VARCHAR(20) NULL,
    KEY idx_link_visits_offer_opened (offer_id, opened_at),
    CONSTRAINT fk_link_visits_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

