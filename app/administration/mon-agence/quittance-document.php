<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/QuittancePdf.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence', 'Comptable');
$idAgence = (int) $currentUser['id_agence'];

$idQuittance = (int) ($_GET['id'] ?? 0);

// Le PDF est généré à la volée à partir des données de la quittance plutôt que relu depuis un
// fichier stocké : plus simple, et ça évite toute dépendance à un fichier pré-généré qui pourrait
// manquer (ex. données de démo insérées directement en base, sans passer par le flux normal).
$stmt = Database::connection()->prepare(
	"SELECT
		q.numero AS numero_quittance, q.date_emission, q.periode_debut, q.periode_fin,
		p.montant, p.date_paiement, p.mode_paiement, p.reference_transaction,
		e.montant_loyer, e.montant_charges, e.montant_total,
		c.numero AS numero_contrat,
		b.titre AS bien_titre,
		ad.ligne1 AS adresse_ligne1, ad.ligne2 AS adresse_ligne2,
		ad.code_postal AS adresse_cp, ad.ville AS adresse_ville,
		ag.nom AS agence_nom, ag.adresse AS agence_adresse,
		ag.code_postal AS agence_code_postal, ag.ville AS agence_ville,
		l.nom AS locataire_nom, l.prenom AS locataire_prenom
	 FROM quittance q
	 INNER JOIN paiement p ON p.id_paiement = q.id_paiement
	 INNER JOIN echeance e ON e.id_echeance = p.id_echeance
	 INNER JOIN contrat c ON c.id_contrat = p.id_contrat
	 INNER JOIN bien b ON b.id_bien = c.id_bien
	 LEFT JOIN adresse ad ON ad.id_adresse = b.id_adresse
	 LEFT JOIN agence ag ON ag.id_agence = c.id_agence
	 LEFT JOIN locataire l ON l.id_locataire = p.id_locataire
	 WHERE q.id_quittance = :id_quittance AND c.id_agence = :id_agence
	 LIMIT 1"
);
$stmt->execute(['id_quittance' => $idQuittance, 'id_agence' => $idAgence]);
$donnees = $stmt->fetch();

if (!$donnees) {
	http_response_code(404);
	exit('Quittance introuvable.');
}

$pdf = QuittancePdf::generate($donnees);
$download = ($_GET['download'] ?? '') === '1';
$filename = 'quittance-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $donnees['numero_quittance']) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdf;
exit;
