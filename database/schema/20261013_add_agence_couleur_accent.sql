-- Couleur dominante extraite du logo de l'agence (calculée côté client au moment de l'upload,
-- cf. app/administration/mon-agence/parametres.php), utilisée pour personnaliser les couleurs
-- de l'interface "Mon agence" de cette agence (voir layouts/agency-identity.php).
ALTER TABLE agence
	ADD COLUMN IF NOT EXISTS couleur_accent VARCHAR(7) DEFAULT NULL AFTER logo;
