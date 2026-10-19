<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];


/*
|--------------------------------------------------------------------------
| Récupération du propriétaire connecté
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT p.id_proprietaire
    FROM proprietaire p
    INNER JOIN utilisateur u ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$idProprietaire = (int) $stmt->fetchColumn();

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
    die('Bien invalide.');
}


/*
|--------------------------------------------------------------------------
| Fonction d'échappement HTML
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

function imageSrc(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (
        str_starts_with($value, 'http://')
        || str_starts_with($value, 'https://')
        || str_starts_with($value, 'data:')
        || str_starts_with($value, '/')
    ) {
        return $value;
    }

    return '/LOKA/' . ltrim($value, '/');
}


/*
|--------------------------------------------------------------------------
| Récupération des types de biens
|--------------------------------------------------------------------------
*/

$types = $pdo->query("
    SELECT id_type_bien, nom
    FROM type_bien
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Récupération du bien
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        b.*,
        a.ligne1,
        a.ville,
        a.code_postal,
        a.region,
        a.pays

    FROM bien b

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    LEFT JOIN adresse a
        ON a.id_adresse = b.id_adresse

    WHERE b.id_bien = ?
      AND bp.id_proprietaire = ?
      AND b.deleted_at IS NULL

    LIMIT 1
");

$stmt->execute([
    $idBien,
    $idProprietaire
]);

$bien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bien) {
    http_response_code(403);
    exit('Accès refusé.');
}


/*
|--------------------------------------------------------------------------
| Récupération des images du bien
|--------------------------------------------------------------------------
*/

function getBienImages(PDO $pdo, int $idBien): array
{
    $stmt = $pdo->prepare("
        SELECT
            id_image,
            id_bien,
            image_url,
            est_principale,
            ordre,
            created_at

        FROM bien_image

        WHERE id_bien = ?

        ORDER BY
            est_principale DESC,
            ordre ASC,
            id_image ASC
    ");

    $stmt->execute([$idBien]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$erreur = null;
$succes = null;


/*
|--------------------------------------------------------------------------
| Dossier des images
|--------------------------------------------------------------------------
*/

$uploadDir = dirname(__DIR__, 2) . '/uploads/biens/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


/*
|--------------------------------------------------------------------------
| TRAITEMENT POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'update';


    /*
    |--------------------------------------------------------------------------
    | SUPPRIMER UNE IMAGE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete_image') {

        $idImage = filter_var(
            $_POST['id_image'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$idImage) {

            $erreur = "Image invalide.";

        } else {

            try {

                $pdo->beginTransaction();


                /*
                | Vérifier que l'image appartient
                | bien au propriétaire connecté
                */

                $stmt = $pdo->prepare("
                    SELECT
                        bi.id_image,
                        bi.image_url,
                        bi.est_principale

                    FROM bien_image bi

                    INNER JOIN bien_proprietaire bp
                        ON bp.id_bien = bi.id_bien

                    WHERE bi.id_image = ?
                      AND bi.id_bien = ?
                      AND bp.id_proprietaire = ?

                    LIMIT 1
                ");

                $stmt->execute([
                    $idImage,
                    $idBien,
                    $idProprietaire
                ]);

                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$image) {
                    throw new Exception(
                        "Image introuvable ou accès refusé."
                    );
                }


                /*
                | Suppression de la ligne
                */

                $stmt = $pdo->prepare("
                    DELETE FROM bien_image
                    WHERE id_image = ?
                ");

                $stmt->execute([$idImage]);


                /*
                | Suppression du fichier physique
                */

                $imageUrl = (string) $image['image_url'];

                if (str_starts_with($imageUrl, 'uploads/biens/')) {

                    $filePath =
                        dirname(__DIR__, 2)
                        . '/'
                        . $imageUrl;

                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Si l'image supprimée était principale
                |--------------------------------------------------------------------------
                */

                if ((int) $image['est_principale'] === 1) {

                    $stmt = $pdo->prepare("
                        SELECT
                            id_image,
                            image_url

                        FROM bien_image

                        WHERE id_bien = ?

                        ORDER BY ordre ASC, id_image ASC

                        LIMIT 1
                    ");

                    $stmt->execute([$idBien]);

                    $nouvellePrincipale = $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                    if ($nouvellePrincipale) {

                        /*
                        | Toutes les images à 0
                        */

                        $stmt = $pdo->prepare("
                            UPDATE bien_image
                            SET est_principale = 0
                            WHERE id_bien = ?
                        ");

                        $stmt->execute([$idBien]);


                        /*
                        | Nouvelle principale
                        */

                        $stmt = $pdo->prepare("
                            UPDATE bien_image
                            SET est_principale = 1
                            WHERE id_image = ?
                        ");

                        $stmt->execute([
                            $nouvellePrincipale['id_image']
                        ]);


                        /*
                        | Synchronisation avec bien.image_url
                        */

                        $stmt = $pdo->prepare("
                            UPDATE bien
                            SET
                                image_url = ?,
                                updated_at = NOW()

                            WHERE id_bien = ?
                        ");

                        $stmt->execute([
                            $nouvellePrincipale['image_url'],
                            $idBien
                        ]);

                    } else {

                        /*
                        | Plus aucune image
                        */

                        $stmt = $pdo->prepare("
                            UPDATE bien
                            SET
                                image_url = NULL,
                                updated_at = NOW()

                            WHERE id_bien = ?
                        ");

                        $stmt->execute([$idBien]);
                    }
                }


                $pdo->commit();


                header(
                    "Location: modifier.php?id="
                    . $idBien
                    . "&success=image_deleted"
                );

                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $erreur = "Impossible de supprimer cette image.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DÉFINIR UNE IMAGE COMME PRINCIPALE
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'set_primary') {

        $idImage = filter_var(
            $_POST['id_image'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$idImage) {

            $erreur = "Image invalide.";

        } else {

            try {

                $pdo->beginTransaction();


                /*
                | Vérification de l'image
                */

                $stmt = $pdo->prepare("
                    SELECT
                        bi.id_image,
                        bi.image_url

                    FROM bien_image bi

                    INNER JOIN bien_proprietaire bp
                        ON bp.id_bien = bi.id_bien

                    WHERE bi.id_image = ?
                      AND bi.id_bien = ?
                      AND bp.id_proprietaire = ?

                    LIMIT 1
                ");

                $stmt->execute([
                    $idImage,
                    $idBien,
                    $idProprietaire
                ]);

                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$image) {
                    throw new Exception(
                        "Image introuvable ou accès refusé."
                    );
                }


                /*
                | Toutes les images deviennent secondaires
                */

                $stmt = $pdo->prepare("
                    UPDATE bien_image
                    SET est_principale = 0
                    WHERE id_bien = ?
                ");

                $stmt->execute([$idBien]);


                /*
                | Image sélectionnée = principale
                */

                $stmt = $pdo->prepare("
                    UPDATE bien_image
                    SET est_principale = 1
                    WHERE id_image = ?
                      AND id_bien = ?
                ");

                $stmt->execute([
                    $idImage,
                    $idBien
                ]);


                /*
                | Synchronisation bien.image_url
                */

                $stmt = $pdo->prepare("
                    UPDATE bien
                    SET
                        image_url = ?,
                        updated_at = NOW()

                    WHERE id_bien = ?
                ");

                $stmt->execute([
                    $image['image_url'],
                    $idBien
                ]);


                $pdo->commit();


                header(
                    "Location: modifier.php?id="
                    . $idBien
                    . "&success=image_primary"
                );

                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $erreur = "Impossible de définir cette image comme principale.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MODIFICATION DU BIEN
    |--------------------------------------------------------------------------
    */

    else {

        try {

            $pdo->beginTransaction();


            /*
            | Données du formulaire
            */

            $titre = trim(
                $_POST['titre'] ?? ''
            );

            $reference = trim(
                $_POST['reference'] ?? ''
            );

            $idTypeBien = filter_var(
                $_POST['id_type_bien'] ?? null,
                FILTER_VALIDATE_INT
            );

            $surface = (float) (
                $_POST['surface'] ?? 0
            );

            $ligne1 = trim(
                $_POST['ligne1'] ?? ''
            );

            $ville = trim(
                $_POST['ville'] ?? ''
            );

            $codePostal = trim(
                $_POST['code_postal'] ?? ''
            );

            $region = trim(
                $_POST['region'] ?? ''
            );

            $pays = trim(
                $_POST['pays'] ?? ''
            );

            $loyer = (float) (
                $_POST['loyer'] ?? 0
            );

            $charges = (float) (
                $_POST['charges'] ?? 0
            );

            $statut = $_POST['statut'] ?? 'CREATED';


            /*
            | Vérifications
            */

            if ($titre === '') {
                throw new Exception(
                    "Le titre est obligatoire."
                );
            }

            if ($reference === '') {
                throw new Exception(
                    "La référence est obligatoire."
                );
            }

            if (!$idTypeBien) {
                throw new Exception(
                    "Le type de bien est obligatoire."
                );
            }

            if ($surface <= 0) {
                throw new Exception(
                    "La surface doit être supérieure à 0."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Mise à jour de l'adresse
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE adresse

                SET
                    ligne1 = ?,
                    ville = ?,
                    code_postal = ?,
                    region = ?,
                    pays = ?

                WHERE id_adresse = ?
            ");

            $stmt->execute([
                $ligne1,
                $ville,
                $codePostal,
                $region,
                $pays,
                $bien['id_adresse']
            ]);


            /*
            |--------------------------------------------------------------------------
            | Mise à jour du bien
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE bien

                SET
                    titre = ?,
                    reference = ?,
                    id_type_bien = ?,
                    surface = ?,
                    loyer = ?,
                    charges = ?,
                    statut = ?,
                    updated_at = NOW()

                WHERE id_bien = ?
            ");

            $stmt->execute([
                $titre,
                $reference,
                $idTypeBien,
                $surface,
                $loyer,
                $charges,
                $statut,
                $idBien
            ]);


            /*
            |--------------------------------------------------------------------------
            | AJOUT DES NOUVELLES PHOTOS
            |--------------------------------------------------------------------------
            */

            if (
                isset($_FILES['photos'])
                && is_array($_FILES['photos']['name'])
            ) {

                /*
                | Nombre actuel d'images
                */

                $stmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM bien_image
                    WHERE id_bien = ?
                ");

                $stmt->execute([$idBien]);

                $nombreImages = (int) $stmt->fetchColumn();


                /*
                | Ordre de départ
                */

                $ordre = $nombreImages + 1;


                /*
                | Formats autorisés
                */

                $formatsAutorises = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];


                $finfo = new finfo(
                    FILEINFO_MIME_TYPE
                );


                /*
                | Parcours des photos
                */

                foreach (
                    $_FILES['photos']['tmp_name']
                    as $index => $tmpName
                ) {

                    $error =
                        $_FILES['photos']['error'][$index]
                        ?? UPLOAD_ERR_NO_FILE;


                    if ($error === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }


                    if ($error !== UPLOAD_ERR_OK) {
                        throw new Exception(
                            "Une photo n'a pas pu être téléversée."
                        );
                    }


                    if (!is_uploaded_file($tmpName)) {
                        throw new Exception(
                            "Fichier photo invalide."
                        );
                    }


                    /*
                    | Taille maximale : 5 Mo
                    */

                    $taille =
                        (int) $_FILES['photos']['size'][$index];


                    if ($taille > 5 * 1024 * 1024) {

                        throw new Exception(
                            "Une photo dépasse la taille maximale de 5 Mo."
                        );
                    }


                    /*
                    | Vérification du véritable type MIME
                    */

                    $mime = $finfo->file($tmpName);


                    if (!isset($formatsAutorises[$mime])) {

                        throw new Exception(
                            "Format non autorisé. Utilisez JPG, PNG ou WEBP."
                        );
                    }


                    $extension =
                        $formatsAutorises[$mime];


                    /*
                    | Nom unique
                    */

                    $nomFichier =
                        'bien_'
                        . $idBien
                        . '_'
                        . bin2hex(random_bytes(8))
                        . '.'
                        . $extension;


                    $destination =
                        $uploadDir
                        . $nomFichier;


                    /*
                    | Déplacement du fichier
                    */

                    if (!move_uploaded_file(
                        $tmpName,
                        $destination
                    )) {

                        throw new Exception(
                            "Impossible d'enregistrer une photo."
                        );
                    }


                    /*
                    | Chemin enregistré en base
                    */

                    $cheminImage =
                        'uploads/biens/'
                        . $nomFichier;


                    /*
                    | Si le bien n'a aucune photo,
                    | la première devient principale.
                    */

                    $estPrincipale =
                        ($nombreImages === 0)
                        ? 1
                        : 0;


                    /*
                    | Insertion dans bien_image
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO bien_image (
                            id_bien,
                            image_url,
                            est_principale,
                            ordre
                        )

                        VALUES (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $idBien,
                        $cheminImage,
                        $estPrincipale,
                        $ordre
                    ]);


                    /*
                    | Synchronisation de bien.image_url
                    */

                    if ($estPrincipale === 1) {

                        $stmt = $pdo->prepare("
                            UPDATE bien

                            SET
                                image_url = ?,
                                updated_at = NOW()

                            WHERE id_bien = ?
                        ");

                        $stmt->execute([
                            $cheminImage,
                            $idBien
                        ]);
                    }


                    $nombreImages++;
                    $ordre++;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            header(
                "Location: detail.php?id="
                . $idBien
                . "&success=1"
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erreur = $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Rechargement des images
|--------------------------------------------------------------------------
*/

$images = getBienImages(
    $pdo,
    $idBien
);


/*
|--------------------------------------------------------------------------
| Messages après redirection
|--------------------------------------------------------------------------
*/

$successParam = $_GET['success'] ?? null;

if ($successParam === 'image_deleted') {

    $succes = "La photo a été supprimée.";
}

if ($successParam === 'image_primary') {

    $succes = "La photo principale a été modifiée.";
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
        Modifier le bien | LOKA
    </title>


    <link
        rel="stylesheet"
        href="proprietaire.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>
        .photos-section{
            background:#fff;
            border-radius:22px;
            padding:24px;
            margin-top:25px;
            margin-bottom:30px;
            box-shadow:0 8px 24px rgba(0,0,0,.06);
        }

        .photos-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            margin-bottom:22px;
        }

        .photos-header > div:first-child{
            display:flex;
            align-items:center;
            gap:15px;
        }

        .photos-header h2{
            color:#172525;
            font-size:22px;
            margin:0;
        }

        .photos-header h2 i{
            color:#0F8B8D;
            margin-right:8px;
        }

        .photos-header span{
            background:#E8F7F7;
            color:#0F8B8D;
            padding:7px 12px;
            border-radius:999px;
            font-size:13px;
            font-weight:700;
        }

        .btn-add-photos{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:12px 17px;
            border-radius:12px;
            background:#0F8B8D;
            color:#fff;
            font-weight:700;
            cursor:pointer;
            text-decoration:none;
        }

        .photos-list{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
            gap:18px;
        }

        .photo-item{
            position:relative;
            overflow:hidden;
            min-width:0;
            border:1px solid #D9E2E2;
            border-radius:18px;
            background:#F6F9F9;
        }

        .photo-image-wrap{
            width:100%;
            height:190px;
            overflow:hidden;
            background:#E8F7F7;
        }

        .photo-image-wrap img{
            display:block;
            width:100%;
            height:100%;
            object-fit:cover;
        }

        .photo-main-badge{
            position:absolute;
            top:12px;
            left:12px;
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:7px 10px;
            border-radius:999px;
            background:#fff;
            color:#0F8B8D;
            font-size:12px;
            font-weight:800;
            box-shadow:0 4px 12px rgba(0,0,0,.12);
        }

        .photo-actions{
            display:flex;
            justify-content:flex-end;
            gap:8px;
            padding:10px;
            background:#fff;
        }

        .photo-actions form{
            margin:0;
        }

        .photo-btn{
            width:38px;
            height:38px;
            border:0;
            border-radius:10px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
        }

        .photo-btn-primary{
            background:#E8F7F7;
            color:#0F8B8D;
        }

        .photo-btn-delete{
            background:#FEE2E2;
            color:#DC2626;
        }

        .photos-empty{
            min-height:150px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:10px;
            border:2px dashed #D9E2E2;
            border-radius:16px;
            color:#64748B;
        }

        .photos-empty i{
            font-size:38px;
            color:#0F8B8D;
        }

        .property-form{
            margin-top:0;
        }

        @media(max-width:700px){
            .photos-header{
                align-items:flex-start;
                flex-direction:column;
            }

            .photos-header > div:first-child{
                flex-wrap:wrap;
            }

            .photos-list{
                grid-template-columns:1fr;
            }

            .photo-image-wrap{
                height:220px;
            }
        }
    </style>

</head>


<body>


<div class="content">


    <!-- =====================================================
         RETOUR
    ====================================================== -->

    <a
        href="detail.php?id=<?= $idBien ?>"
        class="back-link"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Retour au bien

    </a>


    <h1>
        Modifier le bien
    </h1>


    <!-- =====================================================
         MESSAGES
    ====================================================== -->

    <?php if ($erreur): ?>

        <div class="alert-error">

            <?= e($erreur) ?>

        </div>

    <?php endif; ?>


    <?php if ($succes): ?>

        <div class="alert-success">

            <?= e($succes) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FORMULAIRE
    ====================================================== -->

        <!-- =====================================================
         PHOTOS DU BIEN
         Les photos sont affichées avant le formulaire principal.
    ====================================================== -->

<section class="photos-section">


            <div class="photos-header">


                <div>

                    <h2>

                        <i class="fa-solid fa-images"></i>

                        Photos du bien

                    </h2>


                    <span>

                        <?= count($images) ?>

                        photo<?= count($images) > 1 ? 's' : '' ?>

                    </span>

                </div>


                <label
                    for="photos"
                    class="btn-add-photos"
                >

                    <i class="fa-solid fa-plus"></i>

                    Ajouter des photos

                </label>


            </div>


            <?php if (!empty($images)): ?>


                <div class="photos-list">


                    <?php foreach ($images as $image): ?>


                        <div class="photo-item">


                            <div class="photo-image-wrap">
                                <img
                                    src="<?= e(imageSrc($image['image_url'])) ?>"
                                    alt="Photo du bien"
                                    loading="lazy"
                                >
                            </div>


                            <?php if (
                                (int)$image['est_principale'] === 1
                            ): ?>


                                <span class="photo-main-badge">

                                    <i class="fa-solid fa-star"></i>

                                    Principale

                                </span>


                            <?php endif; ?>


                            <div class="photo-actions">


                                <?php if (
                                    (int)$image['est_principale'] !== 1
                                ): ?>


                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="set_primary"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_image"
                                            value="<?= (int)$image['id_image'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="photo-btn photo-btn-primary"
                                            title="Définir comme principale"
                                        >

                                            <i class="fa-solid fa-star"></i>

                                        </button>

                                    </form>


                                <?php endif; ?>


                                <form
                                    method="POST"
                                    onsubmit="return confirm('Supprimer cette photo ?');"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete_image"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_image"
                                        value="<?= (int)$image['id_image'] ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="photo-btn photo-btn-delete"
                                        title="Supprimer"
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                    </button>

                                </form>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="photos-empty">

                    <i class="fa-regular fa-image"></i>

                    <span>Aucune photo</span>

                </div>


            <?php endif; ?>


        </section>


<form
        method="POST"
        enctype="multipart/form-data"
        class="property-form"
    >


        <input
            type="hidden"
            name="action"
            value="update"
        >


        <!-- =================================================
             CHAMP PHOTOS INVISIBLE
        ================================================== -->

        <input
            type="file"
            id="photos"
            name="photos[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
            hidden
        >


        <!-- =================================================
             INFORMATIONS DU BIEN
        ================================================== -->

        <div class="form-grid">


            <!-- TITRE -->

            <div class="form-group">

                <label for="titre">
                    Titre
                </label>

                <input
                    type="text"
                    id="titre"
                    name="titre"
                    required
                    value="<?= e($bien['titre']) ?>"
                >

            </div>


            <!-- REFERENCE -->

            <div class="form-group">

                <label for="reference">
                    Référence
                </label>

                <input
                    type="text"
                    id="reference"
                    name="reference"
                    required
                    value="<?= e($bien['reference']) ?>"
                >

            </div>


            <!-- TYPE -->

            <div class="form-group">

                <label for="id_type_bien">
                    Type de bien
                </label>

                <select
                    id="id_type_bien"
                    name="id_type_bien"
                    required
                >

                    <?php foreach ($types as $type): ?>

                        <option
                            value="<?= (int)$type['id_type_bien'] ?>"
                            <?= (
                                (int)$type['id_type_bien']
                                ===
                                (int)$bien['id_type_bien']
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= e($type['nom']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- SURFACE -->

            <div class="form-group">

                <label for="surface">
                    Surface (m²)
                </label>

                <input
                    type="number"
                    id="surface"
                    name="surface"
                    step="0.01"
                    min="0"
                    value="<?= e($bien['surface']) ?>"
                >

            </div>


            <!-- ADRESSE -->

            <div class="form-group full">

                <label for="ligne1">
                    Adresse
                </label>

                <input
                    type="text"
                    id="ligne1"
                    name="ligne1"
                    value="<?= e($bien['ligne1']) ?>"
                >

            </div>


            <!-- VILLE -->

            <div class="form-group">

                <label for="ville">
                    Ville
                </label>

                <input
                    type="text"
                    id="ville"
                    name="ville"
                    value="<?= e($bien['ville']) ?>"
                >

            </div>


            <!-- CODE POSTAL -->

            <div class="form-group">

                <label for="code_postal">
                    Code postal
                </label>

                <input
                    type="text"
                    id="code_postal"
                    name="code_postal"
                    value="<?= e($bien['code_postal']) ?>"
                >

            </div>


            <!-- REGION -->

            <div class="form-group">

                <label for="region">
                    Région
                </label>

                <input
                    type="text"
                    id="region"
                    name="region"
                    value="<?= e($bien['region']) ?>"
                >

            </div>


            <!-- PAYS -->

            <div class="form-group">

                <label for="pays">
                    Pays
                </label>

                <input
                    type="text"
                    id="pays"
                    name="pays"
                    value="<?= e($bien['pays']) ?>"
                >

            </div>


            <!-- LOYER -->

            <div class="form-group">

                <label for="loyer">
                    Loyer
                </label>

                <input
                    type="number"
                    id="loyer"
                    name="loyer"
                    step="0.01"
                    min="0"
                    value="<?= e($bien['loyer']) ?>"
                >

            </div>


            <!-- CHARGES -->

            <div class="form-group">

                <label for="charges">
                    Charges
                </label>

                <input
                    type="number"
                    id="charges"
                    name="charges"
                    step="0.01"
                    min="0"
                    value="<?= e($bien['charges']) ?>"
                >

            </div>


            <!-- STATUT -->

            <div class="form-group">

                <label for="statut">
                    Statut
                </label>

                <select
                    id="statut"
                    name="statut"
                >

                    <option
                        value="CREATED"
                        <?= $bien['statut'] === 'CREATED'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Créé
                    </option>


                    <option
                        value="AVAILABLE"
                        <?= $bien['statut'] === 'AVAILABLE'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Disponible
                    </option>


                    <option
                        value="OCCUPIED"
                        <?= $bien['statut'] === 'OCCUPIED'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Occupé
                    </option>


                    <option
                        value="MAINTENANCE"
                        <?= $bien['statut'] === 'MAINTENANCE'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Maintenance
                    </option>


                    <option
                        value="ARCHIVED"
                        <?= $bien['statut'] === 'ARCHIVED'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Archivé
                    </option>

                </select>

            </div>


            <!-- =================================================
                 BOUTONS
            ================================================== -->

            <div class="form-actions">


                <button
                    type="submit"
                    class="btn-primary"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Enregistrer

                </button>


                <a
                    href="detail.php?id=<?= $idBien ?>"
                    class="btn-secondary"
                >

                    Annuler

                </a>


            </div>


        </div>


    </form>


        <!-- =================================================
             PHOTOS
        ================================================== -->

        



</div>


<script>

/*
|--------------------------------------------------------------------------
| Sélection des photos
|--------------------------------------------------------------------------
*/

const photoInput = document.getElementById('photos');

const addPhotosButton = document.querySelector(
    '.btn-add-photos'
);


if (photoInput && addPhotosButton) {

    photoInput.addEventListener('change', function () {

        const nombre = this.files.length;

        if (nombre > 0) {

            addPhotosButton.innerHTML =
                '<i class="fa-solid fa-check"></i> '
                + nombre
                + ' photo'
                + (nombre > 1 ? 's' : '')
                + ' sélectionnée'
                + (nombre > 1 ? 's' : '');

        } else {

            addPhotosButton.innerHTML =
                '<i class="fa-solid fa-plus"></i> '
                + 'Ajouter des photos';
        }

    });

}

</script>


</body>

</html>