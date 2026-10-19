<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';

require_once __DIR__ . '/DocumentController.php';
require_once __DIR__ . '/DocumentValidator.php';


/* =========================================================
   AUTHENTIFICATION
   ========================================================= */

$controller = new DocumentController();

$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}


/* =========================================================
   VARIABLES
   ========================================================= */

$errors = [];

$success = null;


/* =========================================================
   FONCTIONS
   ========================================================= */

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   TRAITEMENT DU FORMULAIRE
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* ==============================================
       Vérification CSRF minimale par session
       ============================================== */

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }


    if (
        empty($_POST['csrf_token'])
        || empty($_SESSION['document_csrf_token'])
        || !hash_equals(
            $_SESSION['document_csrf_token'],
            $_POST['csrf_token']
        )
    ) {
        $errors[] =
            'La session du formulaire a expiré. '
            . 'Veuillez réessayer.';
    }


    /* ==============================================
       Vérification du fichier
       ============================================== */

    if (empty($errors)) {

        $validation = DocumentValidator::validate(
            $_FILES['document'] ?? []
        );

        if (!$validation['valid']) {
            $errors = $validation['errors'];
        }
    }


    /* ==============================================
       Enregistrement
       ============================================== */

    if (empty($errors)) {

        $file = $_FILES['document'];

        $tmpName = $file['tmp_name'];

        $originalName = basename(
            (string) $file['name']
        );

        $size = (int) $file['size'];


        /* ------------------------------------------
           MIME réel
           ------------------------------------------ */

        $finfo = new finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType = $finfo->file(
            $tmpName
        );

        if ($mimeType === false) {

            $errors[] =
                'Impossible de déterminer le type du fichier.';

        } else {


            /* --------------------------------------
               Répertoire de stockage
               -------------------------------------- */

            $storageDirectory =
                dirname(__DIR__, 2)
                . DIRECTORY_SEPARATOR
                . 'storage'
                . DIRECTORY_SEPARATOR
                . 'documents';


            if (
                !is_dir($storageDirectory)
                && !mkdir(
                    $storageDirectory,
                    0755,
                    true
                )
            ) {
                $errors[] =
                    'Impossible de créer le dossier de stockage.';
            }


            /* --------------------------------------
               Nom sécurisé du fichier
               -------------------------------------- */

            if (empty($errors)) {

                $extension = strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );


                /*
                 * On ne réutilise jamais directement
                 * le nom envoyé par le navigateur.
                 */

                $storedName =
                    bin2hex(random_bytes(16));

                if ($extension !== '') {
                    $storedName .= '.' . $extension;
                }


                $destination =
                    $storageDirectory
                    . DIRECTORY_SEPARATOR
                    . $storedName;


                /* ----------------------------------
                   Déplacement du fichier
                   ---------------------------------- */

                if (
                    !move_uploaded_file(
                        $tmpName,
                        $destination
                    )
                ) {

                    $errors[] =
                        'Impossible d’enregistrer le fichier.';

                } else {


                    /* ==============================
                       INSERTION BDD
                       ============================== */

                    try {

                        $db = new PDO(
                            'mysql:host=localhost;dbname=loka;charset=utf8mb4',
                            'root',
                            '',
                            [
                                PDO::ATTR_ERRMODE =>
                                    PDO::ERRMODE_EXCEPTION,

                                PDO::ATTR_DEFAULT_FETCH_MODE =>
                                    PDO::FETCH_ASSOC
                            ]
                        );


                        /*
                         * Nom affiché dans LOKA.
                         */
                        $nom = $originalName;


                        /*
                         * Chemin enregistré dans la BDD.
                         *
                         * On stocke un chemin relatif
                         * au projet.
                         */

                        $cheminStockage =
                            'storage/documents/'
                            . $storedName;


                        $sql = "
                            INSERT INTO document
                            (
                                id_locataire,
                                nom,
                                nom_original,
                                mime_type,
                                taille_octets,
                                chemin_stockage,
                                version,
                                created_at,
                                updated_at
                            )
                            VALUES
                            (
                                :id_locataire,
                                :nom,
                                :nom_original,
                                :mime_type,
                                :taille_octets,
                                :chemin_stockage,
                                1,
                                NOW(),
                                NOW()
                            )
                        ";


                        $stmt = $db->prepare($sql);


                        $stmt->execute([
                            'id_locataire' =>
                                (int) $locataire['id_locataire'],

                            'nom' =>
                                $nom,

                            'nom_original' =>
                                $originalName,

                            'mime_type' =>
                                $mimeType,

                            'taille_octets' =>
                                $size,

                            'chemin_stockage' =>
                                $cheminStockage
                        ]);


                        $success =
                            'Le document a été ajouté avec succès.';


                    } catch (Throwable $e) {


                        /*
                         * Si l'insertion BDD échoue,
                         * on supprime le fichier déjà copié.
                         */

                        if (is_file($destination)) {
                            unlink($destination);
                        }


                        $errors[] =
                            'Impossible d’enregistrer '
                            . 'le document dans la base de données.';
                    }
                }
            }
        }
    }
}


/* =========================================================
   TOKEN CSRF
   ========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['document_csrf_token'])) {

    $_SESSION['document_csrf_token'] =
        bin2hex(random_bytes(32));
}


/* =========================================================
   UTILISATEUR
   ========================================================= */

$prenom = e(
    $locataire['prenom'] ?? ''
);

$nom = e(
    $locataire['nom'] ?? ''
);

$initiales = '';

if ($prenom !== '') {
    $initiales .= strtoupper(
        substr($prenom, 0, 1)
    );
}

if ($nom !== '') {
    $initiales .= strtoupper(
        substr($nom, 0, 1)
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
        Ajouter un document | LOKA
    </title>

    <link
        rel="stylesheet"
        href="documents.css"
    >

</head>


<body>

<div class="page-container">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>


        <nav>

            <a href="../locataires/index.php">
                <span class="nav-icon">⌂</span>
                <span>Accueil</span>
            </a>

            <a href="../locataires/detail.php">
                <span class="nav-icon">👤</span>
                <span>Mon profil</span>
            </a>

            <a href="../locataires/logement.php">
                <span class="nav-icon">🏠</span>
                <span>Mon logement</span>
            </a>

            <a href="../locataires/recherche.php">
                <span class="nav-icon">🔎</span>
                <span>Recherche</span>
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>

            <a href="/LOKA/app/locataires/paiements/paiement.php">
                <span class="nav-icon">💳</span>
                <span>Mes paiements</span>
            </a>

            <a href="../maintenance/index.php">
                <span class="nav-icon">🔧</span>
                <span>Maintenance</span>
            </a>

            <a
                href="index.php"
                class="active"
            >
                <span class="nav-icon">📁</span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="../locataires/notifications/notification.php">
                <span class="nav-icon">🔔</span>
                <span>Notifications</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon">↪</span>
                <span>Déconnexion</span>
            </a>

        </div>

    </aside>


    <!-- =====================================================
         CONTENU
         ===================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Ajouter un document
                </h2>

                <p>
                    Ajoutez un document à votre espace personnel.
                </p>

            </div>


            <div class="user-info">

                <div class="avatar">
                    <?= e($initiales) ?>
                </div>

                <div class="user-details">

                    <strong>
                        <?= $prenom . ' ' . $nom ?>
                    </strong>

                    <span>
                        Locataire
                    </span>

                </div>

            </div>

        </header>


        <!-- =================================================
             FORMULAIRE
             ================================================= -->

        <section class="documents-page">


            <div class="document-detail-card">


                <div class="document-detail-header">

                    <div class="document-detail-icon">
                        📁
                    </div>

                    <div class="document-detail-title">

                        <h2>
                            Ajouter un document
                        </h2>

                        <p>
                            Sélectionnez le fichier que vous
                            souhaitez enregistrer.
                        </p>

                    </div>

                </div>


                <!-- Messages -->

                <?php if (!empty($errors)): ?>

                    <div
                        class="documents-alert documents-alert-error"
                        style="margin: 20px 32px 0;"
                    >

                        <?php foreach ($errors as $error): ?>

                            <div>
                                <?= e($error) ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <?php if ($success !== null): ?>

                    <div
                        class="documents-alert documents-alert-success"
                        style="margin: 20px 32px 0;"
                    >
                        <?= e($success) ?>
                    </div>

                <?php endif; ?>


                <!-- Formulaire -->

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="document-form"
                >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(
                            $_SESSION['document_csrf_token']
                        ) ?>"
                    >


                    <div class="document-form-group">

                        <label for="document">
                            Document
                        </label>

                        <input
                            type="file"
                            id="document"
                            name="document"
                            required
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt"
                        >

                    </div>


                    <p
                        style="
                            margin-bottom: 25px;
                            color: #687878;
                            font-size: 13px;
                            line-height: 1.5;
                        "
                    >
                        Formats autorisés :
                        PDF, JPG, JPEG, PNG, DOC, DOCX,
                        XLS, XLSX et TXT.
                        <br>
                        Taille maximale : 10 Mo.
                    </p>


                    <div class="document-detail-footer">

                        <a
                            href="index.php"
                            class="btn-back"
                        >
                            ← Annuler
                        </a>


                        <button
                            type="submit"
                            class="document-btn document-btn-primary"
                            style="border: none;"
                        >

                                Ajouter le document

                        </button>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

</body>

</html>