-- Réglages plateforme (clé/valeur), par analogie avec parametre_agence mais à portée globale.
-- Utilisé par app/administration/parametres (Administrateur plateforme uniquement) pour : le plan
-- d'abonnement affecté par défaut à une agence créée manuellement, et l'activation du SMS de
-- notification lors de la validation d'une agence (voir ParametreModel, AgenceController).
CREATE TABLE IF NOT EXISTS parametre_plateforme (
	id_parametre BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	cle VARCHAR(150) NOT NULL,
	valeur TEXT DEFAULT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id_parametre),
	UNIQUE KEY uk_parametre_plateforme_cle (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
