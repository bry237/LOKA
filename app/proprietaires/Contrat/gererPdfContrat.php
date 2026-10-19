<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];

/*
|--------------------------------------------------------------------------
| FONCTIONS
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatDateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y', $timestamp);
}

/*
|--------------------------------------------------------------------------
| PROPRIÉTAIRE CONNECTÉ
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id_proprietaire,
        p.id_agence
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
| ID CONTRAT
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
        c.date_debut,
        c.date_fin,
        c.statut,
        c.chemin_fichier_pdf,

        b.id_bien,
        b.reference AS reference_bien,
        b.titre AS titre_bien

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

    exit(
        'Contrat introuvable ou inaccessible.'
    );
}

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$erreur = null;
$succes = null;

/*
|--------------------------------------------------------------------------
| DOSSIER DES PDF
|--------------------------------------------------------------------------
*/

$uploadDir =
    dirname(__DIR__, 3)
    . '/uploads/contrats/';

if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0755, true)) {

        $erreur =
            'Impossible de créer le dossier des contrats.';
    }
}

/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $erreur === null
) {

    try {

        /*
        |--------------------------------------------------------------------------
        | Vérification du fichier
        |--------------------------------------------------------------------------
        */

        if (
            !isset($_FILES['contrat_pdf'])
            || !is_array($_FILES['contrat_pdf'])
        ) {

            throw new Exception(
                'Veuillez sélectionner un fichier PDF.'
            );
        }

        $fichier = $_FILES['contrat_pdf'];

        if (
            !isset($fichier['error'])
            || $fichier['error'] !== UPLOAD_ERR_OK
        ) {

            throw new Exception(
                'Le fichier PDF n’a pas pu être téléversé.'
            );
        }

        $tmpName = $fichier['tmp_name'];

        /*
        |--------------------------------------------------------------------------
        | Vérification upload réel
        |--------------------------------------------------------------------------
        */

        if (!is_uploaded_file($tmpName)) {

            throw new Exception(
                'Fichier PDF invalide.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Taille maximale : 10 Mo
        |--------------------------------------------------------------------------
        */

        $taille = (int) (
            $fichier['size'] ?? 0
        );

        if ($taille <= 0) {

            throw new Exception(
                'Le fichier PDF est vide.'
            );
        }

        if ($taille > 10 * 1024 * 1024) {

            throw new Exception(
                'Le PDF dépasse la taille maximale de 10 Mo.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Détection MIME réelle
        |--------------------------------------------------------------------------
        */

        $finfo = new finfo(
            FILEINFO_MIME_TYPE
        );

        $mime = $finfo->file($tmpName);

        if ($mime !== 'application/pdf') {

            throw new Exception(
                'Format de fichier non autorisé. '
                . 'Veuillez sélectionner un PDF.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nom unique
        |--------------------------------------------------------------------------
        */

        $nomFichier =
            'contrat_'
            . $idContrat
            . '_'
            . bin2hex(
                random_bytes(8)
            )
            . '.pdf';

        $destination =
            $uploadDir
            . $nomFichier;

        /*
        |--------------------------------------------------------------------------
        | Déplacement physique
        |--------------------------------------------------------------------------
        */

        if (!move_uploaded_file(
            $tmpName,
            $destination
        )) {

            throw new Exception(
                'Impossible d’enregistrer le PDF.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Chemin enregistré en base
        |--------------------------------------------------------------------------
        */

        $cheminPdf =
            'uploads/contrats/'
            . $nomFichier;

        /*
        |--------------------------------------------------------------------------
        | Ancien fichier
        |--------------------------------------------------------------------------
        */

        $ancienChemin =
            $contrat['chemin_fichier_pdf'];

        /*
        |--------------------------------------------------------------------------
        | Mise à jour du contrat
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE contrat
            SET
                chemin_fichier_pdf = ?,
                updated_at = NOW()
            WHERE id_contrat = ?
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            $cheminPdf,
            $idContrat
        ]);

        /*
        |--------------------------------------------------------------------------
        | Suppression de l'ancien fichier local
        |--------------------------------------------------------------------------
        |
        | On ne supprime que les anciens fichiers appartenant
        | à notre dossier uploads/contrats.
        |
        */

        if (
            $ancienChemin
            && str_starts_with(
                $ancienChemin,
                'uploads/contrats/'
            )
        ) {

            $ancienFichier =
                dirname(__DIR__, 2)
                . '/'
                . ltrim(
                    $ancienChemin,
                    '/'
                );

            if (
                is_file($ancienFichier)
                && $ancienFichier !== $destination
            ) {

                @unlink($ancienFichier);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Mise à jour de la variable affichée
        |--------------------------------------------------------------------------
        */

        $contrat['chemin_fichier_pdf'] =
            $cheminPdf;

        $succes =
            'Le PDF du contrat a été enregistré avec succès.';

    } catch (Throwable $e) {

        $erreur = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| URL DU PDF ACTUEL
|--------------------------------------------------------------------------
*/

$pdfUrl = null;

if (!empty($contrat['chemin_fichier_pdf'])) {

    $pdfUrl =
        '/LOKA/'
        . ltrim(
            $contrat['chemin_fichier_pdf'],
            '/'
        );
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Gestion du PDF - LOKA
    </title>

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >

    <style>

        .pdf-contrat-page {
            padding: 30px;
        }

        .pdf-contrat-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            text-decoration: none;
            color: #555;
        }

        .pdf-contrat-header {
            margin-bottom: 25px;
        }

        .pdf-contrat-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .pdf-contrat-header p {
            margin: 0;
            color: #666;
        }

        .pdf-contrat-card {
            max-width: 900px;
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        }

        .pdf-contrat-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .contrat-resume {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .contrat-resume-item {
            padding: 15px;
            border-radius: 10px;
            background: #f7f7f7;
        }

        .contrat-resume-label {
            display: block;
            margin-bottom: 6px;
            color: #777;
            font-size: 13px;
        }

        .contrat-resume-value {
            font-weight: 600;
        }

        .pdf-actuel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px;
            margin-bottom: 25px;
            border-radius: 12px;
            background: #f7fafb;
            border: 1px solid #e3e8ea;
        }

        .pdf-actuel-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .pdf-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #eee;
            font-size: 24px;
        }

        .pdf-actuel-info strong {
            display: block;
            margin-bottom: 4px;
        }

        .pdf-actuel-info span {
            color: #777;
            font-size: 13px;
        }

        .pdf-upload-box {
            padding: 25px;
            border: 2px dashed #d9e1e4;
            border-radius: 14px;
            text-align: center;
        }

        .pdf-upload-box input[type="file"] {
            display: block;
            width: 100%;
            margin: 15px 0;
        }

        .pdf-upload-box small {
            display: block;
            color: #777;
        }

        .pdf-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }

        .pdf-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 9px;
            border: 0;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        .pdf-button-secondary {
            background: #eee;
            color: #333;
        }

        .pdf-button-primary {
            background: #222;
            color: #fff;
        }

        .pdf-alert {
            max-width: 900px;
            padding: 15px 18px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .pdf-alert-error {
            background: #fff1f1;
            color: #a40000;
        }

        .pdf-alert-success {
            background: #effaf2;
            color: #176b2c;
        }

        @media (max-width: 800px) {

            .pdf-contrat-page {
                padding: 20px;
            }

            .contrat-resume {
                grid-template-columns: 1fr;
            }

            .pdf-actuel {
                flex-direction: column;
                align-items: flex-start;
            }

            .pdf-actions {
                flex-direction: column;
            }

            .pdf-button {
                width: 100%;
                box-sizing: border-box;
            }
        }

    </style>

</head>

<body>

<div class="pdf-contrat-page">

    <a
        href="/LOKA/app/proprietaires/Contrat/detailContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
        class="pdf-contrat-back"
    >
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><polyline points="15 18 9 12 15 6"/></svg>Retour au contrat
    </a>

    <div class="pdf-contrat-header">

        <h1>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px;"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>Gestion du PDF
        </h1>

        <p>
            Ajouter ou remplacer le document PDF de ce contrat.
        </p>

    </div>

    <?php if ($erreur): ?>

        <div class="pdf-alert pdf-alert-error">
            <?= e($erreur) ?>
        </div>

    <?php endif; ?>

    <?php if ($succes): ?>

        <div class="pdf-alert pdf-alert-success">
            <?= e($succes) ?>
        </div>

    <?php endif; ?>

    <section class="pdf-contrat-card">

        <h2>
            Contrat
        </h2>

        <div class="contrat-resume">

            <div class="contrat-resume-item">

                <span class="contrat-resume-label">
                    Numéro
                </span>

                <span class="contrat-resume-value">
                    <?= e($contrat['numero']) ?>
                </span>

            </div>

            <div class="contrat-resume-item">

                <span class="contrat-resume-label">
                    Bien
                </span>

                <span class="contrat-resume-value">
                    <?= e($contrat['reference_bien']) ?>
                </span>

            </div>

            <div class="contrat-resume-item">

                <span class="contrat-resume-label">
                    Période
                </span>

                <span class="contrat-resume-value">
                    <?= formatDateFr($contrat['date_debut']) ?>
                    →
                    <?= formatDateFr($contrat['date_fin']) ?>
                </span>

            </div>

        </div>

        <?php if ($pdfUrl): ?>

            <div class="pdf-actuel">

                <div class="pdf-actuel-info">

                    <span class="pdf-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                    </span>

                    <div>

                        <strong>
                            PDF actuellement enregistré
                        </strong>

                        <span>
                            Tu peux le consulter ou le remplacer.
                        </span>

                    </div>

                </div>

                <a
                    href="<?= e($pdfUrl) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pdf-button pdf-button-secondary"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>Voir le PDF
                </a>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="pdf-upload-box">

                <strong>
                    <?= $pdfUrl
                        ? 'Remplacer le PDF'
                        : 'Ajouter le PDF'
                    ?>
                </strong>

                <input
                    type="file"
                    name="contrat_pdf"
                    accept="application/pdf"
                    required
                >

                <small>
                    PDF uniquement — 10 Mo maximum.
                </small>

            </div>

            <div class="pdf-actions">

                <a
                    href="/LOKA/app/proprietaires/Contrat/detailContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
                    class="pdf-button pdf-button-secondary"
                >
                    Annuler
                </a>

                <button
                    type="submit"
                    class="pdf-button pdf-button-primary"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><path d="M12 21V9"/><path d="m7 14 5-5 5 5"/><path d="M4 5h16"/></svg>
                    <?= $pdfUrl
                        ? 'Remplacer le PDF'
                        : 'Enregistrer le PDF'
                    ?>
                </button>

            </div>

        </form>

    </section>

</div>

</body>

</html>