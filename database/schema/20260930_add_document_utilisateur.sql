CREATE TABLE IF NOT EXISTS document_utilisateur (
	id_document_utilisateur BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	id_utilisateur BIGINT(20) UNSIGNED NOT NULL,
	type ENUM('PIECE_IDENTITE','JUSTIFICATIF_DOMICILE','AUTRE') NOT NULL,
	nom_original VARCHAR(255) NOT NULL,
	mime_type VARCHAR(150) DEFAULT NULL,
	taille_octets BIGINT(20) UNSIGNED DEFAULT NULL,
	chemin_stockage VARCHAR(500) NOT NULL,
	statut_verification ENUM('EN_ATTENTE','VALIDE','REJETE') NOT NULL DEFAULT 'EN_ATTENTE',
	analyse_ia_resume TEXT DEFAULT NULL,
	analyse_ia_alertes TEXT DEFAULT NULL CHECK (analyse_ia_alertes IS NULL OR json_valid(analyse_ia_alertes)),
	analyse_ia_date DATETIME DEFAULT NULL,
	id_utilisateur_validateur BIGINT(20) UNSIGNED DEFAULT NULL,
	date_validation DATETIME DEFAULT NULL,
	motif_rejet TEXT DEFAULT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id_document_utilisateur),
	KEY idx_document_utilisateur_utilisateur (id_utilisateur),
	CONSTRAINT fk_document_utilisateur_utilisateur FOREIGN KEY (id_utilisateur)
		REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_document_utilisateur_validateur FOREIGN KEY (id_utilisateur_validateur)
		REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
