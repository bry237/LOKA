<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';

$auth = Authorization::requireRole('Proprietaire');
$pdo = Database::connection();

/*
 * =========================================================
 * UTILISATEUR CONNECTÉ
 * =========================================================
 */
$idUtilisateur = (int) ($auth['id_utilisateur'] ?? 0);

if ($idUtilisateur <= 0) {
    http_response_code(403);
    exit('Utilisateur non authentifié.');
}

/*
 * =========================================================
 * DOCUMENT / ACTION
 * =========================================================
 */
$idDocument = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$action = strtolower(
    trim((string) ($_GET['action'] ?? 'voir'))
);

if (!$idDocument || $idDocument <= 0) {
    http_response_code(400);
    exit('Document invalide.');
}

if (!in_array($action, ['voir', 'telecharger'], true)) {
    $action = 'voir';
}

/*
 * =========================================================
 * PROPRIÉTAIRE CONNECTÉ
 * =========================================================
 */
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

/*
 * =========================================================
 * DOCUMENT
 *
 * Le propriétaire peut accéder au document si :
 *
 * 1. document directement rattaché au propriétaire
 * 2. document rattaché à un bien du propriétaire
 * 3. document rattaché à un contrat d'un bien du propriétaire
 * 4. document rattaché à un locataire d'un contrat
 *    d'un bien du propriétaire
 * =========================================================
 */
$stmt = $pdo->prepare("
    SELECT
        d.id_document,
        d.id_bien,
        d.id_contrat,
        d.id_proprietaire,
        d.id_locataire,
        d.nom,
        d.nom_original,
        d.mime_type,
        d.taille_octets,
        d.chemin_stockage,
        d.version

    FROM document d

    WHERE d.id_document = ?

      AND (
            d.id_proprietaire = ?

            OR EXISTS (
                SELECT 1
                FROM bien_proprietaire bp
                INNER JOIN bien b
                    ON b.id_bien = bp.id_bien
                WHERE bp.id_bien = d.id_bien
                  AND bp.id_proprietaire = ?
                  AND b.deleted_at IS NULL
            )

            OR EXISTS (
                SELECT 1
                FROM contrat c
                INNER JOIN bien b
                    ON b.id_bien = c.id_bien
                INNER JOIN bien_proprietaire bp
                    ON bp.id_bien = b.id_bien
                WHERE c.id_contrat = d.id_contrat
                  AND bp.id_proprietaire = ?
                  AND c.deleted_at IS NULL
                  AND b.deleted_at IS NULL
            )

            OR EXISTS (
                SELECT 1
                FROM contrat c
                INNER JOIN bien b
                    ON b.id_bien = c.id_bien
                INNER JOIN bien_proprietaire bp
                    ON bp.id_bien = b.id_bien
                INNER JOIN contrat_locataire cl
                    ON cl.id_contrat = c.id_contrat
                WHERE cl.id_locataire = d.id_locataire
                  AND bp.id_proprietaire = ?
                  AND c.deleted_at IS NULL
                  AND b.deleted_at IS NULL
            )
      )

    LIMIT 1
");

$stmt->execute([
    $idDocument,
    $idProprietaire,
    $idProprietaire,
    $idProprietaire,
    $idProprietaire
]);

$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable ou accès non autorisé.');
}

/*
 * =========================================================
 * CHEMIN DU FICHIER
 * =========================================================
 */
$cheminStockage = trim(
    (string) ($document['chemin_stockage'] ?? '')
);

if ($cheminStockage === '') {
    http_response_code(404);
    exit('Fichier du document introuvable.');
}

/*
 * Protection contre les chemins dangereux.
 */
if (
    str_contains($cheminStockage, "\0") ||
    str_contains($cheminStockage, '..')
) {
    http_response_code(400);
    exit('Chemin de fichier invalide.');
}

/*
 * =========================================================
 * RACINE LOKA / UPLOADS
 * =========================================================
 */
$racineLoka = realpath(dirname(__DIR__, 3));

if ($racineLoka === false) {
    http_response_code(500);
    exit('Répertoire LOKA introuvable.');
}

$racineUploads = realpath(
    $racineLoka . DIRECTORY_SEPARATOR . 'uploads'
);

if ($racineUploads === false) {
    http_response_code(500);
    exit('Répertoire uploads introuvable.');
}

/*
 * Exemple en base :
 *
 * uploads/documents/bail_ctr-test-002.pdf
 */
$cheminRelatif = ltrim(
    str_replace(
        ['/', '\\'],
        DIRECTORY_SEPARATOR,
        $cheminStockage
    ),
    DIRECTORY_SEPARATOR
);

$cheminFichier = realpath(
    $racineLoka . DIRECTORY_SEPARATOR . $cheminRelatif
);

if (
    $cheminFichier === false ||
    !is_file($cheminFichier)
) {
    http_response_code(404);
    exit('Fichier du document introuvable.');
}

/*
 * =========================================================
 * SÉCURITÉ : LE FICHIER DOIT ÊTRE DANS uploads/
 * =========================================================
 */
$prefixUploads = rtrim(
    $racineUploads,
    DIRECTORY_SEPARATOR
) . DIRECTORY_SEPARATOR;

if (
    $cheminFichier !== $racineUploads &&
    !str_starts_with($cheminFichier, $prefixUploads)
) {
    http_response_code(403);
    exit('Accès au fichier refusé.');
}

/*
 * =========================================================
 * TYPE MIME
 * =========================================================
 */
$mimeType = trim(
    (string) ($document['mime_type'] ?? '')
);

if (
    $mimeType === '' &&
    function_exists('finfo_open')
) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if ($finfo !== false) {
        $detectedMime = finfo_file(
            $finfo,
            $cheminFichier
        );

        if (
            is_string($detectedMime) &&
            $detectedMime !== ''
        ) {
            $mimeType = $detectedMime;
        }

        finfo_close($finfo);
    }
}

if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}

/*
 * =========================================================
 * NOM DU FICHIER
 * =========================================================
 */
$nomFichier = trim(
    (string) ($document['nom_original'] ?? '')
);

if ($nomFichier === '') {
    $nomFichier = trim(
        (string) ($document['nom'] ?? '')
    );
}

if ($nomFichier === '') {
    $nomFichier = basename($cheminFichier);
}

$nomFichier = basename($nomFichier);

/*
 * =========================================================
 * ENVOI DU FICHIER
 * =========================================================
 */
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mimeType);

header(
    'Content-Length: ' .
    (string) filesize($cheminFichier)
);

header('X-Content-Type-Options: nosniff');

$nomFichierHeader = str_replace(
    ['"', "\r", "\n"],
    '',
    $nomFichier
);

if ($action === 'telecharger') {

    header(
        'Content-Disposition: attachment; filename="' .
        $nomFichierHeader .
        '"'
    );

} else {

    header(
        'Content-Disposition: inline; filename="' .
        $nomFichierHeader .
        '"'
    );
}

readfile($cheminFichier);
exit;