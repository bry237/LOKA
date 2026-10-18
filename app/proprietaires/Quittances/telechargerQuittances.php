<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';

$auth = Authorization::requireRole('Proprietaire');
$pdo = Database::connection();

$idUtilisateur = (int) ($auth['id_utilisateur'] ?? 0);

if ($idUtilisateur <= 0) {
    http_response_code(403);
    exit('Accès refusé.');
}


/* =========================================================
   ID QUITTANCE
   ========================================================= */

$idQuittance = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idQuittance || $idQuittance <= 0) {
    http_response_code(400);
    exit('Quittance invalide.');
}


/* =========================================================
   ACTION
   ========================================================= */

$action = $_GET['action'] ?? 'telecharger';

if (!in_array($action, ['voir', 'telecharger'], true)) {
    $action = 'telecharger';
}


/* =========================================================
   PROPRIÉTAIRE CONNECTÉ
   ========================================================= */

$stmt = $pdo->prepare("
    SELECT pr.id_proprietaire
    FROM proprietaire pr

    INNER JOIN utilisateur u
        ON u.email = pr.email

    WHERE u.id_utilisateur = ?

    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$idProprietaire = (int) ($stmt->fetchColumn() ?: 0);

if ($idProprietaire <= 0) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}


/* =========================================================
   RÉCUPÉRATION DE LA QUITTANCE
   ========================================================= */

$stmt = $pdo->prepare("
    SELECT
        q.id_quittance,
        q.numero,
        q.chemin_fichier_pdf

    FROM quittance q

    INNER JOIN paiement p
        ON p.id_paiement = q.id_paiement

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE q.id_quittance = ?
      AND bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
      AND c.deleted_at IS NULL

    LIMIT 1
");

$stmt->execute([
    $idQuittance,
    $idProprietaire
]);

$quittance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quittance) {
    http_response_code(404);
    exit('Quittance introuvable ou accès non autorisé.');
}


/* =========================================================
   VÉRIFICATION DU PDF
   ========================================================= */

$cheminPdf = trim(
    (string) ($quittance['chemin_fichier_pdf'] ?? '')
);

if ($cheminPdf === '') {
    http_response_code(404);
    exit('Aucun PDF n’est associé à cette quittance.');
}


/* =========================================================
   NETTOYAGE DU CHEMIN
   ========================================================= */

$cheminPdf = str_replace('\\', '/', $cheminPdf);
$cheminPdf = ltrim($cheminPdf, '/');


/* =========================================================
   SÉCURITÉ CONTRE LES CHEMINS INTERDITS
   ========================================================= */

if (
    str_contains($cheminPdf, '..') ||
    str_contains($cheminPdf, "\0")
) {
    http_response_code(400);
    exit('Chemin de fichier invalide.');
}


/* =========================================================
   CHEMIN PHYSIQUE
   ========================================================= */

$racineProjet = dirname(__DIR__, 3);

$fichier = $racineProjet . '/' . $cheminPdf;

$fichierReel = realpath($fichier);

if ($fichierReel === false || !is_file($fichierReel)) {
    http_response_code(404);
    exit('Le fichier PDF est introuvable.');
}


/* =========================================================
   VÉRIFICATION DU DOSSIER UPLOADS
   ========================================================= */

$dossierUploads = realpath(
    $racineProjet . '/uploads'
);

if ($dossierUploads === false) {
    http_response_code(500);
    exit('Le dossier des fichiers est introuvable.');
}

$dossierUploadsAvecSlash =
    rtrim($dossierUploads, DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR;


/*
 * Le PDF doit obligatoirement être dans uploads.
 */
if (
    !str_starts_with(
        $fichierReel,
        $dossierUploadsAvecSlash
    )
) {
    http_response_code(403);
    exit('Accès au fichier refusé.');
}


/* =========================================================
   NOM DU FICHIER
   ========================================================= */

$numero = (string) (
    $quittance['numero'] ?? ('quittance_' . $idQuittance)
);

$numeroNettoye = preg_replace(
    '/[^a-zA-Z0-9_-]/',
    '_',
    $numero
);

if (!$numeroNettoye) {
    $numeroNettoye = 'quittance_' . $idQuittance;
}

$nomFichier = 'quittance_' . $numeroNettoye . '.pdf';


/* =========================================================
   EN-TÊTES PDF
   ========================================================= */

header('Content-Type: application/pdf');

header(
    'Content-Length: ' . filesize($fichierReel)
);

header('X-Content-Type-Options: nosniff');

header(
    'Cache-Control: private, max-age=0, must-revalidate'
);

header('Pragma: public');


/* =========================================================
   VOIR OU TÉLÉCHARGER
   ========================================================= */

if ($action === 'voir') {

    /*
     * Le navigateur affiche le PDF.
     */
    header(
        'Content-Disposition: inline; filename="' .
        $nomFichier .
        '"'
    );

} else {

    /*
     * Le navigateur force le téléchargement.
     */
    header(
        'Content-Disposition: attachment; filename="' .
        $nomFichier .
        '"'
    );
}


/* =========================================================
   ENVOI DU PDF
   ========================================================= */

readfile($fichierReel);

exit;