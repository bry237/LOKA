<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];

/*
|--------------------------------------------------------------------------
| ID DU CONTRAT
|--------------------------------------------------------------------------
*/

$idContrat = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idContrat) {
    http_response_code(400);
    exit('Contrat invalide.');
}

/*
|--------------------------------------------------------------------------
| PROPRIÉTAIRE CONNECTÉ
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id_proprietaire
    FROM proprietaire p
    INNER JOIN utilisateur u
        ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([
    $idUtilisateur
]);

$proprietaire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proprietaire) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}

$idProprietaire = (int) $proprietaire['id_proprietaire'];

/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DU CONTRAT
|--------------------------------------------------------------------------
|
| On vérifie que le contrat appartient bien
| à un bien du propriétaire connecté.
|
*/

$stmt = $pdo->prepare("
    SELECT
        c.id_contrat,
        c.numero,
        c.chemin_fichier_pdf

    FROM contrat c

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE c.id_contrat = ?
      AND bp.id_proprietaire = ?
      AND c.deleted_at IS NULL
      AND b.deleted_at IS NULL

    LIMIT 1
");

$stmt->execute([
    $idContrat,
    $idProprietaire
]);

$contrat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrat) {
    http_response_code(404);
    exit('Contrat introuvable ou inaccessible.');
}

/*
|--------------------------------------------------------------------------
| VÉRIFICATION DU PDF
|--------------------------------------------------------------------------
*/

$cheminPdf = trim(
    (string) ($contrat['chemin_fichier_pdf'] ?? '')
);

if ($cheminPdf === '') {

    header(
        'Location: /LOKA/app/proprietaires/detail_contrat.php?id='
        . $idContrat
        . '&pdf_deleted=0'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| SUPPRESSION DU FICHIER PHYSIQUE
|--------------------------------------------------------------------------
|
| On accepte uniquement les fichiers présents
| dans uploads/contrats/.
|
*/

if (
    !str_starts_with(
        $cheminPdf,
        'uploads/contrats/'
    )
) {

    http_response_code(400);

    exit(
        'Le fichier PDF associé au contrat est invalide.'
    );
}

$racineLoka =
    dirname(__DIR__, 2);

$fichierPdf =
    $racineLoka
    . '/'
    . ltrim(
        $cheminPdf,
        '/'
    );

/*
|--------------------------------------------------------------------------
| SÉCURITÉ SUPPLÉMENTAIRE
|--------------------------------------------------------------------------
|
| On vérifie que le fichier se trouve bien
| dans le dossier uploads/contrats/.
|
*/

$dossierContrats =
    realpath(
        $racineLoka
        . '/uploads/contrats'
    );

$fichierReel =
    is_file($fichierPdf)
        ? realpath($fichierPdf)
        : false;

$fichierAutorise = false;

if (
    $dossierContrats !== false
    && $fichierReel !== false
) {

    $prefixe =
        $dossierContrats
        . DIRECTORY_SEPARATOR;

    $fichierAutorise =
        str_starts_with(
            $fichierReel,
            $prefixe
        );
}

/*
|--------------------------------------------------------------------------
| TRANSACTION BDD
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Suppression du chemin dans la BDD
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE contrat
        SET
            chemin_fichier_pdf = NULL,
            updated_at = NOW()
        WHERE id_contrat = ?
          AND deleted_at IS NULL
    ");

    $stmt->execute([
        $idContrat
    ]);

    /*
    |--------------------------------------------------------------------------
    | Validation BDD
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    exit(
        'Impossible de supprimer le PDF du contrat.'
    );
}

/*
|--------------------------------------------------------------------------
| SUPPRESSION PHYSIQUE
|--------------------------------------------------------------------------
*/

if (
    $fichierAutorise
    && is_file($fichierReel)
) {

    @unlink($fichierReel);
}

/*
|--------------------------------------------------------------------------
| RETOUR
|--------------------------------------------------------------------------
*/

header(
    'Location: /LOKA/app/proprietaires/detail_contrat.php?id='
    . $idContrat
    . '&pdf_deleted=1'
);

exit;