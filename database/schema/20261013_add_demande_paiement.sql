-- Demande de paiement en ligne (Stripe Checkout, mode paiement unique) envoyée à un locataire
-- pour une échéance donnée. Cf. plan "Demande de paiement en ligne" (2026-10-12).
CREATE TABLE IF NOT EXISTS demande_paiement (
	id_demande BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	id_agence BIGINT(20) UNSIGNED NOT NULL,
	id_echeance BIGINT(20) UNSIGNED NOT NULL,
	id_locataire BIGINT(20) UNSIGNED DEFAULT NULL,
	id_paiement BIGINT(20) UNSIGNED DEFAULT NULL,
	montant DECIMAL(12,2) NOT NULL,
	stripe_checkout_session_id VARCHAR(255) DEFAULT NULL,
	checkout_url VARCHAR(500) DEFAULT NULL,
	statut ENUM('PENDING','PAID','EXPIRED','CANCELLED') NOT NULL DEFAULT 'PENDING',
	id_utilisateur_createur BIGINT(20) UNSIGNED DEFAULT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	paid_at DATETIME DEFAULT NULL,
	PRIMARY KEY (id_demande),
	KEY idx_demande_paiement_echeance (id_echeance),
	KEY idx_demande_paiement_session (stripe_checkout_session_id),
	CONSTRAINT fk_demande_paiement_agence FOREIGN KEY (id_agence) REFERENCES agence (id_agence) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_demande_paiement_echeance FOREIGN KEY (id_echeance) REFERENCES echeance (id_echeance) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
