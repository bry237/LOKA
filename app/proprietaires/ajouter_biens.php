<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';


/*
|--------------------------------------------------------------------------
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];


/*
|--------------------------------------------------------------------------
| FONCTION D'ÉCHAPPEMENT HTML
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


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DU PROPRIÉTAIRE CONNECTÉ
|--------------------------------------------------------------------------
|
| On récupère également son agence.
| Cela permet de renseigner automatiquement bien.id_agence.
|
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
$idAgence = (int) $proprietaire['id_agence'];

if (!$idAgence) {

    http_response_code(403);

    exit('Agence du propriétaire introuvable.');
}


/*
|--------------------------------------------------------------------------
| TYPES DE BIENS
|--------------------------------------------------------------------------
*/

$types = $pdo->query("
    SELECT
        id_type_bien,
        nom
    FROM type_bien
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ÉQUIPEMENTS DISPONIBLES
|--------------------------------------------------------------------------
|
| On utilise les équipements demandés dans ton écran :
|
| - Balcon
| - Terrasse
| - Jardin
| - Piscine
| - Ascenseur
| - Climatisation
|
*/

$equipementsDemandes = [
    'Balcon',
    'Terrasse',
    'Jardin',
    'Piscine',
    'Ascenseur',
    'Climatisation'
];


/*
|--------------------------------------------------------------------------
| VARIABLES DU FORMULAIRE
|--------------------------------------------------------------------------
*/

$erreur = null;


/*
|--------------------------------------------------------------------------
| VALEURS PAR DÉFAUT
|--------------------------------------------------------------------------
*/

$titre = '';
$reference = 'BIEN-' . date('Ymd-His') . '-' . strtoupper(
    bin2hex(random_bytes(2))
);

$idTypeBien = '';
$surface = '';
$nombrePieces = '0';
$etage = '';

$ligne1 = '';
$ville = '';
$codePostal = '';
$region = '';
$pays = 'France';

$loyer = '0';
$charges = '0';
$caution = '0';

$statut = 'CREATED';

$description = '';

$equipementsSelectionnes = [];


/*
|--------------------------------------------------------------------------
| DOSSIER DES PHOTOS
|--------------------------------------------------------------------------
*/

$uploadDir = dirname(__DIR__, 2) . '/uploads/biens/';

if (!is_dir($uploadDir)) {

    mkdir(
        $uploadDir,
        0755,
        true
    );
}


/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | RÉCUPÉRATION DES DONNÉES
    |--------------------------------------------------------------------------
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

    $surface = (string) (
        $_POST['surface'] ?? '0'
    );

    $nombrePieces = (string) (
        $_POST['nombre_pieces'] ?? '0'
    );

    $etage = trim(
        $_POST['etage'] ?? ''
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
        $_POST['pays'] ?? 'France'
    );

    $loyer = (string) (
        $_POST['loyer'] ?? '0'
    );

    $charges = (string) (
        $_POST['charges'] ?? '0'
    );

    $caution = (string) (
        $_POST['caution'] ?? '0'
    );

    $statut = $_POST['statut'] ?? 'CREATED';

    $description = trim(
        $_POST['description'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | ÉQUIPEMENTS SÉLECTIONNÉS
    |--------------------------------------------------------------------------
    */

    $equipementsSelectionnes = $_POST['equipements'] ?? [];

    if (!is_array($equipementsSelectionnes)) {

        $equipementsSelectionnes = [];
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALISATION DES VALEURS
    |--------------------------------------------------------------------------
    */

    $surfaceValue = (float) $surface;

    $nombrePiecesValue = max(
        0,
        (int) $nombrePieces
    );

    $etageValue = (
        $etage === ''
    )
        ? null
        : (int) $etage;

    $loyerValue = max(
        0,
        (float) $loyer
    );

    $chargesValue = max(
        0,
        (float) $charges
    );

    $cautionValue = max(
        0,
        (float) $caution
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    try {

        if ($titre === '') {

            throw new Exception(
                'Le titre du bien est obligatoire.'
            );
        }


        if ($reference === '') {

            throw new Exception(
                'La référence est obligatoire.'
            );
        }


        if (!$idTypeBien) {

            throw new Exception(
                'Le type de bien est obligatoire.'
            );
        }


        if ($surfaceValue <= 0) {

            throw new Exception(
                'La surface doit être supérieure à 0.'
            );
        }


        if ($nombrePiecesValue < 0) {

            throw new Exception(
                'Le nombre de pièces est invalide.'
            );
        }


        if ($ligne1 === '') {

            throw new Exception(
                'L’adresse est obligatoire.'
            );
        }


        if ($ville === '') {

            throw new Exception(
                'La ville est obligatoire.'
            );
        }


        if ($pays === '') {

            throw new Exception(
                'Le pays est obligatoire.'
            );
        }


        $statutsAutorises = [
            'CREATED',
            'AVAILABLE',
            'OCCUPIED',
            'MAINTENANCE',
            'ARCHIVED'
        ];

        if (!in_array(
            $statut,
            $statutsAutorises,
            true
        )) {

            throw new Exception(
                'Statut du bien invalide.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | NETTOYAGE DES ÉQUIPEMENTS
        |--------------------------------------------------------------------------
        */

        $equipementsSelectionnes = array_values(
            array_intersect(
                $equipementsSelectionnes,
                $equipementsDemandes
            )
        );


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | VÉRIFICATION DE LA RÉFÉRENCE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM bien
            WHERE reference = ?
        ");

        $stmt->execute([
            $reference
        ]);

        if ((int) $stmt->fetchColumn() > 0) {

            throw new Exception(
                'Cette référence existe déjà. Veuillez en choisir une autre.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CRÉATION DE L'ADRESSE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO adresse (
                ligne1,
                ligne2,
                code_postal,
                ville,
                region,
                pays
            )
            VALUES (?, NULL, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $ligne1,
            $codePostal !== '' ? $codePostal : null,
            $ville,
            $region !== '' ? $region : null,
            $pays
        ]);

        $idAdresse = (int) $pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | PREMIÈRE IMAGE
        |--------------------------------------------------------------------------
        */

        $imagePrincipale = null;


        /*
        |--------------------------------------------------------------------------
        | CRÉATION DU BIEN
        |--------------------------------------------------------------------------
        |
        | Les informations financières et générales sont enregistrées ici.
        |
        */

        $stmt = $pdo->prepare("
            INSERT INTO bien (
                id_agence,
                id_type_bien,
                id_adresse,
                reference,
                titre,
                surface,
                nombre_pieces,
                etage,
                loyer,
                charges,
                caution,
                statut,
                description,
                image_url
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NULL
            )
        ");

        $stmt->execute([
            $idAgence,
            $idTypeBien,
            $idAdresse,
            $reference,
            $titre,
            $surfaceValue,
            $nombrePiecesValue,
            $etageValue,
            $loyerValue,
            $chargesValue,
            $cautionValue,
            $statut,
            $description !== ''
                ? $description
                : null
        ]);

        $idBien = (int) $pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | ASSOCIATION PROPRIÉTAIRE / BIEN
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO bien_proprietaire (
                id_bien,
                id_proprietaire,
                quote_part,
                date_debut,
                date_fin
            )
            VALUES (?, ?, 100.00, CURDATE(), NULL)
        ");

        $stmt->execute([
            $idBien,
            $idProprietaire
        ]);


        /*
        |--------------------------------------------------------------------------
        | ÉQUIPEMENTS
        |--------------------------------------------------------------------------
        |
        | Pour chaque équipement sélectionné :
        |
        | 1. On cherche l'équipement.
        | 2. S'il n'existe pas, on le crée.
        | 3. On l'associe au nouveau bien.
        |
        */

        foreach (
            $equipementsSelectionnes
            as $nomEquipement
        ) {

            /*
            | Recherche
            */

            $stmt = $pdo->prepare("
                SELECT id_equipement
                FROM equipement
                WHERE nom = ?
                LIMIT 1
            ");

            $stmt->execute([
                $nomEquipement
            ]);

            $idEquipement = $stmt->fetchColumn();


            /*
            | Création automatique si nécessaire
            */

            if (!$idEquipement) {

                $stmt = $pdo->prepare("
                    INSERT INTO equipement (
                        nom,
                        description
                    )
                    VALUES (?, ?)
                ");

                $stmt->execute([
                    $nomEquipement,
                    $nomEquipement . '.'
                ]);

                $idEquipement = (int) $pdo->lastInsertId();

            } else {

                $idEquipement = (int) $idEquipement;
            }


            /*
            | Association avec le bien
            */

            $stmt = $pdo->prepare("
                INSERT INTO bien_equipement (
                    id_bien,
                    id_equipement,
                    quantite
                )
                VALUES (?, ?, 1)
            ");

            $stmt->execute([
                $idBien,
                $idEquipement
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | PHOTOS
        |--------------------------------------------------------------------------
        */

        $ordre = 1;

        if (
            isset($_FILES['photos'])
            && is_array($_FILES['photos']['name'] ?? null)
        ) {

            $formatsAutorises = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = new finfo(
                FILEINFO_MIME_TYPE
            );


            foreach (
                $_FILES['photos']['tmp_name']
                as $index => $tmpName
            ) {

                $error =
                    $_FILES['photos']['error'][$index]
                    ?? UPLOAD_ERR_NO_FILE;


                /*
                | Pas de fichier
                */

                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }


                /*
                | Erreur d'upload
                */

                if ($error !== UPLOAD_ERR_OK) {

                    throw new Exception(
                        'Une photo n’a pas pu être téléversée.'
                    );
                }


                /*
                | Vérification du fichier
                */

                if (!is_uploaded_file($tmpName)) {

                    throw new Exception(
                        'Fichier photo invalide.'
                    );
                }


                /*
                | Taille maximale : 5 Mo
                */

                $taille =
                    (int) (
                        $_FILES['photos']['size'][$index]
                        ?? 0
                    );

                if ($taille > 5 * 1024 * 1024) {

                    throw new Exception(
                        'Une photo dépasse la taille maximale de 5 Mo.'
                    );
                }


                /*
                | Détection MIME réelle
                */

                $mime = $finfo->file(
                    $tmpName
                );


                if (!isset(
                    $formatsAutorises[$mime]
                )) {

                    throw new Exception(
                        'Format de photo non autorisé. Utilisez JPG, PNG ou WEBP.'
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
                    . bin2hex(
                        random_bytes(8)
                    )
                    . '.'
                    . $extension;


                $destination =
                    $uploadDir
                    . $nomFichier;


                /*
                | Déplacement physique
                */

                if (!move_uploaded_file(
                    $tmpName,
                    $destination
                )) {

                    throw new Exception(
                        'Impossible d’enregistrer une photo.'
                    );
                }


                /*
                | Chemin enregistré en base
                */

                $cheminImage =
                    'uploads/biens/'
                    . $nomFichier;


                /*
                | Première photo = photo principale
                */

                $estPrincipale =
                    ($ordre === 1)
                        ? 1
                        : 0;


                /*
                | Enregistrement dans bien_image
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
                | Première photo du bien
                */

                if ($estPrincipale === 1) {

                    $imagePrincipale =
                        $cheminImage;
                }


                $ordre++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PHOTOS DEPUIS INTERNET
        |--------------------------------------------------------------------------
        |
        | Une URL par ligne. Les URL HTTP/HTTPS sont enregistrées directement
        | dans bien_image. La première photo ajoutée (locale ou Internet)
        | devient la photo principale.
        |
        */

        $photoUrls = trim(
            $_POST['photo_urls'] ?? ''
        );

        if ($photoUrls !== '') {

            $urls = preg_split(
                '/\r\n|\r|\n/',
                $photoUrls
            );

            foreach ($urls as $url) {

                $url = trim($url);

                if ($url === '') {
                    continue;
                }

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new Exception(
                        'Une URL de photo est invalide : ' . $url
                    );
                }

                $scheme = strtolower(
                    (string) (parse_url($url, PHP_URL_SCHEME) ?? '')
                );

                if ($scheme !== 'http' && $scheme !== 'https') {
                    throw new Exception(
                        'Les photos Internet doivent utiliser une URL http:// ou https://.'
                    );
                }

                $estPrincipale = ($ordre === 1) ? 1 : 0;

                $stmt = $pdo->prepare("
                    INSERT INTO bien_image (
                        id_bien,
                        image_url,
                        est_principale,
                        ordre
                    )
                    VALUES (?, ?, ?, ?)
                " );

                $stmt->execute([
                    $idBien,
                    $url,
                    $estPrincipale,
                    $ordre
                ]);

                if ($estPrincipale === 1) {
                    $imagePrincipale = $url;
                }

                $ordre++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SYNCHRONISATION DE bien.image_url
        |--------------------------------------------------------------------------
        */

        if ($imagePrincipale !== null) {

            $stmt = $pdo->prepare("
                UPDATE bien
                SET
                    image_url = ?,
                    updated_at = NOW()
                WHERE id_bien = ?
            ");

            $stmt->execute([
                $imagePrincipale,
                $idBien
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | FIN DE TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->commit();


        /*
        |--------------------------------------------------------------------------
        | REDIRECTION VERS LE DÉTAIL DU BIEN
        |--------------------------------------------------------------------------
        */

        header(
            'Location: detail.php?id='
            . $idBien
            . '&success=created'
        );

        exit;


    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | ANNULATION DE LA TRANSACTION
        |--------------------------------------------------------------------------
        */

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }


        $erreur = $e->getMessage();
    }
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
        Ajouter un bien | LOKA
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

        /*
        |--------------------------------------------------------------------------
        | SECTIONS
        |--------------------------------------------------------------------------
        */

        .form-section {

            margin-top: 30px;

            padding: 25px;

            background: #ffffff;

            border-radius: 18px;

            box-shadow: 0 8px 25px rgba(
                0,
                0,
                0,
                0.05
            );
        }


        .form-section h2 {

            margin: 0 0 20px;

            font-size: 20px;
        }


        .form-section h2 i {

            margin-right: 8px;

            color: #0f8b8d;
        }


        /*
        |--------------------------------------------------------------------------
        | ÉQUIPEMENTS
        |--------------------------------------------------------------------------
        */

        .equipment-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(
                        180px,
                        1fr
                    )
                );

            gap: 14px;
        }


        .equipment-item {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 15px;

            border: 1px solid #e4e8eb;

            border-radius: 12px;

            cursor: pointer;

            transition: 0.2s;
        }


        .equipment-item:hover {

            border-color: #0f8b8d;

            background: #f7ffff;
        }


        .equipment-item input {

            width: 18px;

            height: 18px;

            accent-color: #0f8b8d;
        }


        .equipment-icon {

            color: #0f8b8d;

            width: 22px;

            text-align: center;
        }


        /*
        |--------------------------------------------------------------------------
        | PHOTOS
        |--------------------------------------------------------------------------
        */

        .photos-box {

            border: 2px dashed #d9e1e4;

            border-radius: 15px;

            padding: 25px;

            text-align: center;
        }


        .photos-box i {

            font-size: 32px;

            color: #0f8b8d;

            margin-bottom: 10px;
        }


        .photos-box label {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 12px 18px;

            background: #0f8b8d;

            color: white;

            border-radius: 10px;

            cursor: pointer;
        }


        .photos-box small {

            display: block;

            margin-top: 10px;

            color: #718096;
        }


        .photo-url-box {

            margin-top: 25px;

            padding-top: 22px;

            border-top: 1px solid #e4e8eb;

            text-align: left;
        }


        .photo-url-label {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 10px;

            font-weight: 600;

            color: #263238;
        }


        .photo-url-label i {

            color: #0f8b8d;

            margin: 0;

            font-size: 18px;
        }


        .photo-url-box textarea {

            width: 100%;

            min-height: 110px;

            padding: 12px 14px;

            border: 1px solid #d9e1e4;

            border-radius: 10px;

            resize: vertical;

            font-family: inherit;

            box-sizing: border-box;
        }


        .photo-url-box textarea:focus {

            outline: none;

            border-color: #0f8b8d;

            box-shadow: 0 0 0 3px rgba(15, 139, 141, 0.08);
        }


        .photo-url-box small {

            display: block;

            margin-top: 8px;

            color: #718096;

            line-height: 1.5;
        }


        .url-preview {

            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));

            gap: 12px;

            margin-top: 15px;
        }


        .url-preview-item {

            overflow: hidden;

            border-radius: 10px;

            border: 1px solid #e4e8eb;

            background: #f8fafb;
        }


        .url-preview-item img {

            width: 100%;

            height: 100px;

            object-fit: cover;

            display: block;
        }


        .url-preview-item span {

            display: block;

            padding: 7px 8px;

            font-size: 12px;

            color: #718096;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL MENSUEL
        |--------------------------------------------------------------------------
        */

        .total-preview {

            margin-top: 15px;

            padding: 15px 18px;

            background: #f2fbfb;

            border-radius: 12px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .total-preview strong {

            font-size: 22px;

            color: #0f8b8d;
        }


        /*
        |--------------------------------------------------------------------------
        | BOUTONS
        |--------------------------------------------------------------------------
        */

        .form-actions {

            margin-top: 30px;

            display: flex;

            justify-content: flex-end;

            gap: 12px;

            flex-wrap: wrap;
        }

    </style>

</head>


<body>


<div class="content">


    <!-- =====================================================
         RETOUR
    ====================================================== -->

    <a
        href="biens.php"
        class="back-link"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Retour à mes biens

    </a>


    <h1>
        Ajouter un bien
    </h1>


    <!-- =====================================================
         MESSAGE D'ERREUR
    ====================================================== -->

    <?php if ($erreur): ?>

        <div class="alert-error">

            <?= e($erreur) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FORMULAIRE
    ====================================================== -->

    <form
        method="POST"
        enctype="multipart/form-data"
        class="property-form"
    >


        <!-- =================================================
             INFORMATIONS GÉNÉRALES
        ================================================== -->

        <section class="form-section">

            <h2>

                <i class="fa-solid fa-house"></i>

                Informations du bien

            </h2>


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
                        maxlength="200"
                        value="<?= e($titre) ?>"
                        placeholder="Ex : Appartement T3 lumineux"
                    >

                </div>


                <!-- RÉFÉRENCE -->

                <div class="form-group">

                    <label for="reference">
                        Référence
                    </label>

                    <input
                        type="text"
                        id="reference"
                        name="reference"
                        required
                        maxlength="100"
                        value="<?= e($reference) ?>"
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

                        <option value="">
                            -- Choisir un type --
                        </option>


                        <?php foreach ($types as $type): ?>

                            <option
                                value="<?= (int) $type['id_type_bien'] ?>"
                                <?= (
                                    (int) $idTypeBien
                                    ===
                                    (int) $type['id_type_bien']
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
                        required
                        min="0.01"
                        step="0.01"
                        value="<?= e($surface) ?>"
                        placeholder="Ex : 65"
                    >

                </div>


                <!-- NOMBRE DE PIÈCES -->

                <div class="form-group">

                    <label for="nombre_pieces">
                        Nombre de pièces
                    </label>

                    <input
                        type="number"
                        id="nombre_pieces"
                        name="nombre_pieces"
                        min="0"
                        step="1"
                        value="<?= e($nombrePieces) ?>"
                    >

                </div>


                <!-- ÉTAGE -->

                <div class="form-group">

                    <label for="etage">
                        Étage
                    </label>

                    <input
                        type="number"
                        id="etage"
                        name="etage"
                        step="1"
                        value="<?= e($etage) ?>"
                        placeholder="Ex : 3"
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
                            <?= $statut === 'CREATED'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Créé
                        </option>


                        <option
                            value="AVAILABLE"
                            <?= $statut === 'AVAILABLE'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Disponible
                        </option>


                        <option
                            value="OCCUPIED"
                            <?= $statut === 'OCCUPIED'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Occupé
                        </option>


                        <option
                            value="MAINTENANCE"
                            <?= $statut === 'MAINTENANCE'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Maintenance
                        </option>


                        <option
                            value="ARCHIVED"
                            <?= $statut === 'ARCHIVED'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Archivé
                        </option>

                    </select>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="Décrivez le bien..."
                    ><?= e($description) ?></textarea>

                </div>


            </div>

        </section>


        <!-- =================================================
             ADRESSE
        ================================================== -->

        <section class="form-section">

            <h2>

                <i class="fa-solid fa-location-dot"></i>

                Adresse du bien

            </h2>


            <div class="form-grid">


                <div class="form-group full">

                    <label for="ligne1">
                        Adresse
                    </label>

                    <input
                        type="text"
                        id="ligne1"
                        name="ligne1"
                        required
                        maxlength="255"
                        value="<?= e($ligne1) ?>"
                        placeholder="Ex : 15 rue de Paris"
                    >

                </div>


                <div class="form-group">

                    <label for="ville">
                        Ville
                    </label>

                    <input
                        type="text"
                        id="ville"
                        name="ville"
                        required
                        maxlength="100"
                        value="<?= e($ville) ?>"
                        placeholder="Ex : Limoges"
                    >

                </div>


                <div class="form-group">

                    <label for="code_postal">
                        Code postal
                    </label>

                    <input
                        type="text"
                        id="code_postal"
                        name="code_postal"
                        maxlength="20"
                        value="<?= e($codePostal) ?>"
                        placeholder="Ex : 87000"
                    >

                </div>


                <div class="form-group">

                    <label for="region">
                        Région
                    </label>

                    <input
                        type="text"
                        id="region"
                        name="region"
                        maxlength="100"
                        value="<?= e($region) ?>"
                        placeholder="Ex : Nouvelle-Aquitaine"
                    >

                </div>


                <div class="form-group">

                    <label for="pays">
                        Pays
                    </label>

                    <input
                        type="text"
                        id="pays"
                        name="pays"
                        maxlength="100"
                        value="<?= e($pays) ?>"
                    >

                </div>


            </div>

        </section>


        <!-- =================================================
             INFORMATIONS FINANCIÈRES
        ================================================== -->

        <section class="form-section">

            <h2>

                <i class="fa-solid fa-coins"></i>

                Informations financières

            </h2>


            <div class="form-grid">


                <!-- LOYER -->

                <div class="form-group">

                    <label for="loyer">
                        Loyer
                    </label>

                    <input
                        type="number"
                        id="loyer"
                        name="loyer"
                        min="0"
                        step="0.01"
                        value="<?= e($loyer) ?>"
                        placeholder="Ex : 450"
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
                        min="0"
                        step="0.01"
                        value="<?= e($charges) ?>"
                        placeholder="Ex : 75"
                    >

                </div>


                <!-- CAUTION -->

                <div class="form-group">

                    <label for="caution">
                        Caution
                    </label>

                    <input
                        type="number"
                        id="caution"
                        name="caution"
                        min="0"
                        step="0.01"
                        value="<?= e($caution) ?>"
                        placeholder="Ex : 450"
                    >

                </div>


            </div>


            <!-- TOTAL -->

            <div class="total-preview">

                <span>
                    Total mensuel
                </span>

                <strong>

                    <span id="totalMensuel">
                        0.00
                    </span>

                    €

                </strong>

            </div>

        </section>


        <!-- =================================================
             ÉQUIPEMENTS
        ================================================== -->

        <section class="form-section">

            <h2>

                <i class="fa-solid fa-list-check"></i>

                Équipements

            </h2>


            <div class="equipment-grid">


                <!-- BALCON -->

                <label class="equipment-item">

                    <input
                        type="checkbox"
                        name="equipements[]"
                        value="Balcon"
                        <?= in_array(
                            'Balcon',
                            $equipementsSelectionnes,
                            true
                        )
                            ? 'checked'
                            : ''
                        ?>
                    >

                    <span class="equipment-icon">

                        <i class="fa-solid fa-building"></i>

                    </span>

                    <span>
                        Balcon
                    </span>

                </label>


                <!-- TERRASSE -->

                <label class="equipment-item">

                    <input
                        type="checkbox"
                        name="equipements[]"
                        value="Terrasse"
                        <?= in_array(
                            'Terrasse',
                            $equipementsSelectionnes,
                            true
                        )
                            ? 'checked'
                            : ''
                        ?>
                    >

                    <span class="equipment-icon">

                        <i class="fa-solid fa-sun"></i>

                    </span>

                    <span>
                        Terrasse
                    </span>

                </label>


                <!-- JARDIN -->

                <label class="equipment-item">

                    <input
                        type="checkbox"
                        name="equipements[]"
                        value="Jardin"
                        <?= in_array(
                            'Jardin',
                            $equipementsSelectionnes,
                            true
                        )
                            ? 'checked'
                            : ''
                        ?>
                    >

                    <span class="equipment-icon">

                        <i class="fa-solid fa-tree"></i>

                    </span>

                    <span>
                        Jardin
                    </span>

                </label>


                <!-- PISCINE -->

                <label class="equipment-item">

                    <input
                        type="checkbox"
                        name="equipements[]"
                        value="Piscine"
                        <?= in_array(
                            'Piscine',
                            $equipementsSelectionnes,
                            true
                        )
                            ? 'checked'
                            : ''
                        ?>
                    >

                    <span class="equipment-icon">

                        <i class="fa-solid fa-water"></i>

                    </span>

                    <span>
                        Piscine
                    </span>

                </label>


                <!-- ASCENSEUR -->

                <label class="equipment-item">

                    <input
                        type="checkbox"
                        name="equipements[]"
                        value="Ascenseur"
                        <?= in_array(
                            'Ascenseur',
                            $equipementsSelectionnes,
                            true
                        )
                            ? 'checked'
                            : ''
                        ?>
                    >

                    <span class="equipment-icon">

                        <i class="fa-solid fa-elevator"></i>

                    </span>

                    <span>
                        Ascenseur
                    </span>

                </label>


                <!-- CLIMATISATION -->

                <label class="equipment-item">

                    <input
                        type="checkbox"
                        name="equipements[]"
                        value="Climatisation"
                        <?= in_array(
                            'Climatisation',
                            $equipementsSelectionnes,
                            true
                        )
                            ? 'checked'
                            : ''
                        ?>
                    >

                    <span class="equipment-icon">

                        <i class="fa-solid fa-snowflake"></i>

                    </span>

                    <span>
                        Climatisation
                    </span>

                </label>


            </div>

        </section>


        <!-- =================================================
             PHOTOS
        ================================================== -->

        <section class="form-section">

            <h2>

                <i class="fa-solid fa-images"></i>

                Photos du bien

            </h2>


            <div class="photos-box">

                <i class="fa-regular fa-image"></i>


                <div>

                    <strong>
                        Ajouter des photos
                    </strong>

                </div>


                <label for="photos">

                    <i class="fa-solid fa-plus"></i>

                    Sélectionner les photos

                </label>


                <input
                    type="file"
                    id="photos"
                    name="photos[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    hidden
                >


                <small id="photosInfo">

                    JPG, PNG ou WEBP — 5 Mo maximum par photo

                </small>


                <div class="photo-url-box">

                    <label for="photo_urls" class="photo-url-label">

                        <i class="fa-solid fa-globe"></i>

                        Ajouter des photos depuis Internet

                    </label>

                    <textarea
                        id="photo_urls"
                        name="photo_urls"
                        rows="4"
                        placeholder="Collez une URL d'image par ligne..."
                    ></textarea>

                    <small>
                        Utilisez l'adresse directe de l'image, par exemple une URL
                        qui commence par https://. Vous pouvez mélanger photos locales
                        et photos Internet.
                    </small>

                    <div id="urlPreview" class="url-preview"></div>

                </div>

            </div>

        </section>


        <!-- =================================================
             BOUTONS
        ================================================== -->

        <div class="form-actions">


            <button
                type="submit"
                class="btn-primary"
            >

                <i class="fa-solid fa-floppy-disk"></i>

                Enregistrer le bien

            </button>


            <a
                href="biens.php"
                class="btn-secondary"
            >

                Annuler

            </a>


        </div>


    </form>


</div>


<script>

/*
|--------------------------------------------------------------------------
| CALCUL DU TOTAL MENSUEL
|--------------------------------------------------------------------------
*/

const loyerInput =
    document.getElementById('loyer');

const chargesInput =
    document.getElementById('charges');

const totalMensuel =
    document.getElementById('totalMensuel');


function calculerTotalMensuel()
{
    const loyer =
        parseFloat(loyerInput.value) || 0;

    const charges =
        parseFloat(chargesInput.value) || 0;

    const total =
        loyer + charges;

    totalMensuel.textContent =
        total.toFixed(2);
}


loyerInput.addEventListener(
    'input',
    calculerTotalMensuel
);


chargesInput.addEventListener(
    'input',
    calculerTotalMensuel
);


calculerTotalMensuel();


/*
|--------------------------------------------------------------------------
| PHOTOS
|--------------------------------------------------------------------------
*/

const photosInput =
    document.getElementById('photos');

const photosInfo =
    document.getElementById('photosInfo');


photosInput.addEventListener(
    'change',
    function ()
    {

        const nombre =
            this.files.length;


        if (nombre === 0) {

            photosInfo.textContent =
                'JPG, PNG ou WEBP — 5 Mo maximum par photo';

            return;
        }


        photosInfo.textContent =
            nombre
            + ' photo'
            + (nombre > 1 ? 's' : '')
            + ' sélectionnée'
            + (nombre > 1 ? 's' : '');

    }
);


/*
|--------------------------------------------------------------------------
| APERÇU DES PHOTOS INTERNET
|--------------------------------------------------------------------------
*/

const photoUrlsInput =
    document.getElementById('photo_urls');

const urlPreview =
    document.getElementById('urlPreview');


function afficherApercuUrls()
{

    if (!photoUrlsInput || !urlPreview) {
        return;
    }

    urlPreview.innerHTML = '';

    const urls = photoUrlsInput.value
        .split(/\r\n|\r|\n/)
        .map(url => url.trim())
        .filter(url => url !== '');

    urls.forEach(function (url) {

        const item = document.createElement('div');
        item.className = 'url-preview-item';

        const image = document.createElement('img');
        image.src = url;
        image.alt = 'Aperçu';
        image.loading = 'lazy';

        image.onerror = function () {
            item.remove();
        };

        const text = document.createElement('span');
        text.textContent = url;

        item.appendChild(image);
        item.appendChild(text);
        urlPreview.appendChild(item);
    });
}


if (photoUrlsInput) {

    photoUrlsInput.addEventListener(
        'input',
        afficherApercuUrls
    );

}

</script>


</body>

</html>