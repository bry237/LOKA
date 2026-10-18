<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) ($user['id_utilisateur'] ?? 0);


/*
|--------------------------------------------------------------------------
| Récupération du propriétaire connecté
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT p.id_proprietaire
    FROM proprietaire p
    INNER JOIN utilisateur u
        ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$idProprietaire = (int) ($stmt->fetchColumn() ?: 0);


if (!$idProprietaire) {

    http_response_code(403);

    exit('Propriétaire introuvable.');
}


/*
|--------------------------------------------------------------------------
| Récupération de l'identifiant du bien
|--------------------------------------------------------------------------
*/

$idBien = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$idBien) {

    header('Location: /LOKA/app/proprietaires/biens.php');

    exit;
}


/*
|--------------------------------------------------------------------------
| Vérification que le bien appartient bien
| au propriétaire connecté
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT b.id_bien
    FROM bien b

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE b.id_bien = ?
      AND bp.id_proprietaire = ?
      AND b.deleted_at IS NULL

    LIMIT 1
");

$stmt->execute([
    $idBien,
    $idProprietaire
]);

$bienExiste = $stmt->fetchColumn();


if (!$bienExiste) {

    header('Location: /LOKA/app/proprietaires/biens.php');

    exit;
}


/*
|--------------------------------------------------------------------------
| Suppression logique du bien
|--------------------------------------------------------------------------
|
| Le bien n'est pas physiquement supprimé.
| On renseigne deleted_at.
|
*/

try {

    $pdo->beginTransaction();


    /*
    | Marquer le bien comme supprimé
    */

    $stmt = $pdo->prepare("
        UPDATE bien

        SET deleted_at = CURRENT_TIMESTAMP

        WHERE id_bien = ?

          AND deleted_at IS NULL
    ");

    $stmt->execute([
        $idBien
    ]);


    /*
    | Suppression du lien propriétaire / bien
    |
    | On peut conserver le lien en base pour l'historique.
    | Il n'est donc pas supprimé ici.
    */


    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Retour vers Mes biens
    |--------------------------------------------------------------------------
    */

    header(
        'Location: /LOKA/app/proprietaires/biens.php?success=suppression'
    );

    exit;


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    http_response_code(500);

    exit(
        'Une erreur est survenue lors de la suppression du bien.'
    );
}