-- Permet de retirer un plan du catalogue (suppression logique) sans casser l'historique
-- (agence_abonnement, facture_abonnement référencent id_abonnement) ni les jointures qui
-- affichent encore le nom du plan sur des abonnements passés (voir AgenceModel::detail).
ALTER TABLE abonnement
	ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL AFTER actif;
