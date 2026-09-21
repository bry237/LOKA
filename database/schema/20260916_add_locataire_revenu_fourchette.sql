ALTER TABLE locataire
	MODIFY COLUMN id_agence BIGINT(20) UNSIGNED NULL,
	ADD COLUMN IF NOT EXISTS id_utilisateur BIGINT(20) UNSIGNED NULL AFTER id_locataire,
	ADD COLUMN IF NOT EXISTS revenu_mensuel_fourchette VARCHAR(50) DEFAULT NULL AFTER revenu_mensuel,
	ADD CONSTRAINT fk_locataire_utilisateur FOREIGN KEY (id_utilisateur)
		REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE proprietaire
	MODIFY COLUMN id_agence BIGINT(20) UNSIGNED NULL,
	ADD COLUMN IF NOT EXISTS id_utilisateur BIGINT(20) UNSIGNED NULL AFTER id_proprietaire,
	ADD COLUMN IF NOT EXISTS projet ENUM('rent', 'sell', 'rent-sell') DEFAULT NULL AFTER date_naissance,
	ADD CONSTRAINT fk_proprietaire_utilisateur FOREIGN KEY (id_utilisateur)
		REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL ON UPDATE CASCADE;
