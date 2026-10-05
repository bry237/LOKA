ALTER TABLE agence
	ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(255) DEFAULT NULL AFTER statut;

ALTER TABLE abonnement
	ADD COLUMN IF NOT EXISTS stripe_price_id VARCHAR(255) DEFAULT NULL AFTER actif;

-- Champ additif pour l'affectation d'un responsable (gestionnaire immobilier) à un bien.
-- Géré uniquement côté base de données : aucun fichier app/biens/* n'est modifié,
-- l'UI d'affectation vit dans app/administration/mon-agence/biens-assignation.php.
ALTER TABLE bien
	ADD COLUMN IF NOT EXISTS id_responsable BIGINT(20) UNSIGNED DEFAULT NULL AFTER id_agence;

CREATE TABLE IF NOT EXISTS facture_abonnement (
	id_facture BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	id_agence_abonnement BIGINT(20) UNSIGNED DEFAULT NULL,
	id_agence BIGINT(20) UNSIGNED NOT NULL,
	id_abonnement BIGINT(20) UNSIGNED NOT NULL,
	montant DECIMAL(12,2) NOT NULL,
	stripe_checkout_session_id VARCHAR(255) DEFAULT NULL,
	stripe_payment_intent_id VARCHAR(255) DEFAULT NULL,
	statut ENUM('PENDING','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	paid_at DATETIME DEFAULT NULL,
	PRIMARY KEY (id_facture),
	KEY idx_facture_abonnement_agence (id_agence),
	KEY idx_facture_abonnement_session (stripe_checkout_session_id),
	CONSTRAINT fk_facture_abonnement_agence FOREIGN KEY (id_agence) REFERENCES agence (id_agence) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_facture_abonnement_abonnement FOREIGN KEY (id_abonnement) REFERENCES abonnement (id_abonnement) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
