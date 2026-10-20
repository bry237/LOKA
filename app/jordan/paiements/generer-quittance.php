<?php

declare(strict_types=1);

ob_start();

try {
    require_once dirname(__DIR__, 3) . '/core/Authorization.php';
    require_once dirname(__DIR__, 3) . '/core/Database.php';
    require_once dirname(__DIR__, 3) . '/core/QuittancePdf.php';

    $user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
    $agencyId = (int) ($user['id_agence'] ?? 0);

    if ($agencyId < 1) {
        throw new RuntimeException('Aucune agence n’est associée à ce compte.');
    }

    $idQuittance = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$idQuittance) {
        throw new RuntimeException('Quittance invalide (id manquant).');
    }

    $pdo = Database::connection();
    $stmt = $pdo->prepare("
        SELECT
            q.numero AS numero_quittance, q.date_emission, q.periode_debut, q.periode_fin,
            p.montant, p.date_paiement, p.mode_paiement, p.reference_transaction,
            e.montant_loyer, e.montant_charges, e.montant_total,
            c.numero AS numero_contrat,
            b.titre AS bien_titre,
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
        LEFT JOIN agence ag ON ag.id_agence = b.id_agence
        LEFT JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat AND cl.titulaire_principal = 1
        LEFT JOIN locataire l ON l.id_locataire = cl.id_locataire
        WHERE q.id_quittance = :id_quittance
          AND b.id_agence = :agency_id
          AND c.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['id_quittance' => $idQuittance, 'agency_id' => $agencyId]);
    $donnees = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donnees) {
        throw new RuntimeException('Quittance introuvable ou non accessible.');
    }

    $pdf = QuittancePdf::generate($donnees);
    $nom = $donnees['numero_quittance'] . '.pdf';

    ob_end_clean();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nom . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erreur : ' . $e->getMessage();
    exit;
}
