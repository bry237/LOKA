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

$messageSucces = '';
$messageErreur = '';

/*
|--------------------------------------------------------------------------
| Récupération du propriétaire
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        u.id_utilisateur,
        u.email AS email_utilisateur,
        u.id_role,
        pr.id_proprietaire,
        pr.nom,
        pr.prenom,
        pr.email,
        pr.telephone,
        pr.adresse,
        pr.code_postal,
        pr.ville,
        pr.pays
    FROM utilisateur u
    INNER JOIN proprietaire pr
        ON pr.email = u.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$proprietaire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proprietaire) {
    http_response_code(404);
    exit('Profil propriétaire introuvable.');
}

$idProprietaire = (int) $proprietaire['id_proprietaire'];

/*
|--------------------------------------------------------------------------
| Modification du profil
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'modifier_profil') {

    $nom = trim((string) ($_POST['nom'] ?? ''));
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telephone = trim((string) ($_POST['telephone'] ?? ''));
    $adresse = trim((string) ($_POST['adresse'] ?? ''));
    $codePostal = trim((string) ($_POST['code_postal'] ?? ''));
    $ville = trim((string) ($_POST['ville'] ?? ''));
    $pays = trim((string) ($_POST['pays'] ?? ''));

    if ($nom === '' || $prenom === '') {

        $messageErreur =
            'Le nom et le prénom sont obligatoires.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $messageErreur =
            'Veuillez saisir une adresse email valide.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Vérification de l'email
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id_utilisateur
            FROM utilisateur
            WHERE email = ?
              AND id_utilisateur <> ?
            LIMIT 1
        ");

        $stmt->execute([
            $email,
            $idUtilisateur
        ]);

        if ($stmt->fetch()) {

            $messageErreur =
                'Cette adresse email est déjà utilisée par un autre utilisateur.';

        } else {

            try {

                $pdo->beginTransaction();

                /*
                |--------------------------------------------------------------------------
                | 1. Mise à jour du propriétaire
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    UPDATE proprietaire
                    SET
                        nom = ?,
                        prenom = ?,
                        email = ?,
                        telephone = ?,
                        adresse = ?,
                        code_postal = ?,
                        ville = ?,
                        pays = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id_proprietaire = ?
                ");

                $stmt->execute([
                    $nom,
                    $prenom,
                    $email,
                    $telephone !== '' ? $telephone : null,
                    $adresse !== '' ? $adresse : null,
                    $codePostal !== '' ? $codePostal : null,
                    $ville !== '' ? $ville : null,
                    $pays !== '' ? $pays : null,
                    $idProprietaire
                ]);


                /*
                |--------------------------------------------------------------------------
                | 2. Mise à jour du compte utilisateur
                |--------------------------------------------------------------------------
                |
                | Les informations communes doivent être identiques
                | dans utilisateur et proprietaire.
                |
                */

                $stmt = $pdo->prepare("
                    UPDATE utilisateur
                    SET
                        nom = ?,
                        prenom = ?,
                        email = ?,
                        telephone = ?
                    WHERE id_utilisateur = ?
                ");

                $stmt->execute([
                    $nom,
                    $prenom,
                    $email,
                    $telephone !== '' ? $telephone : null,
                    $idUtilisateur
                ]);


                /*
                |--------------------------------------------------------------------------
                | 3. Validation des deux modifications
                |--------------------------------------------------------------------------
                */

                $pdo->commit();


                $messageSucces =
                    'Vos informations ont été mises à jour avec succès.';


                /*
                |--------------------------------------------------------------------------
                | 4. Rechargement des données
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT
                        u.id_utilisateur,
                        u.email AS email_utilisateur,
                        u.id_role,
                        pr.id_proprietaire,
                        pr.nom,
                        pr.prenom,
                        pr.email,
                        pr.telephone,
                        pr.adresse,
                        pr.code_postal,
                        pr.ville,
                        pr.pays
                    FROM utilisateur u
                    INNER JOIN proprietaire pr
                        ON pr.email = u.email
                    WHERE u.id_utilisateur = ?
                    LIMIT 1
                ");

                $stmt->execute([$idUtilisateur]);

                $proprietaire =
                    $stmt->fetch(PDO::FETCH_ASSOC);

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $messageErreur =
                    'Une erreur est survenue lors de la mise à jour du profil.';
            }
        }
    }
}


    /*
    |--------------------------------------------------------------------------
    | Modification du mot de passe
    |--------------------------------------------------------------------------
    */
    if ($action === 'modifier_mot_de_passe') {

        $ancienMotDePasse =
            (string) ($_POST['ancien_mot_de_passe'] ?? '');

        $nouveauMotDePasse =
            (string) ($_POST['nouveau_mot_de_passe'] ?? '');

        $confirmationMotDePasse =
            (string) ($_POST['confirmation_mot_de_passe'] ?? '');

        if (
            $ancienMotDePasse === '' ||
            $nouveauMotDePasse === '' ||
            $confirmationMotDePasse === ''
        ) {

            $messageErreur =
                'Veuillez remplir tous les champs du mot de passe.';

        } elseif (strlen($nouveauMotDePasse) < 8) {

            $messageErreur =
                'Le nouveau mot de passe doit contenir au moins 8 caractères.';

        } elseif ($nouveauMotDePasse !== $confirmationMotDePasse) {

            $messageErreur =
                'La confirmation du nouveau mot de passe ne correspond pas.';

        } else {

            /*
            | Récupération du mot de passe actuel.
            */
            $stmt = $pdo->prepare("
                SELECT mot_de_passe
                FROM utilisateur
                WHERE id_utilisateur = ?
                LIMIT 1
            ");

            $stmt->execute([$idUtilisateur]);

            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$utilisateur) {

                $messageErreur =
                    'Utilisateur introuvable.';

            } elseif (
                !password_verify(
                    $ancienMotDePasse,
                    (string) $utilisateur['mot_de_passe']
                )
            ) {

                $messageErreur =
                    'L’ancien mot de passe est incorrect.';

            } else {

                $nouveauHash = password_hash(
                    $nouveauMotDePasse,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    UPDATE utilisateur
                    SET mot_de_passe = ?
                    WHERE id_utilisateur = ?
                ");

                $stmt->execute([
                    $nouveauHash,
                    $idUtilisateur
                ]);

                $messageSucces =
                    'Votre mot de passe a été modifié avec succès.';
            }
        }
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

    <title>Paramètres - LOKA</title>

    <!-- CSS PRINCIPAL DU PROPRIETAIRE -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >


    <!-- CSS SPECIFIQUE AUX parametres -->

  
 <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Parametres/parametres.css"
    >

    <!-- POLICE -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
   
</head>

<body>


    <aside class="sidebar">

        <div class="logo">

            <h2>LOKA</h2>

            <p>Gestion immobilière</p>

        </div>


        <nav>


            <a
                href="/LOKA/app/proprietaires/index.php"
            >

                <i class="fa-solid fa-chart-line"></i>

                Dashboard

            </a>


            <a
                href="/LOKA/app/proprietaires/biens.php"
            >

                <i class="fa-solid fa-building"></i>

                Mes biens

            </a>


            <a
                href="/LOKA/app/proprietaires/Locataires/index.php"
               
            >

                <i class="fa-solid fa-users"></i>

                Mes locataires

            </a>


            <a
                href="/LOKA/app/proprietaires/Contrat/contrat.php"
            >

                <i class="fa-solid fa-file-contract"></i>

                Mes contrats

            </a>


            <a
                href="/LOKA/app/proprietaires/Revenus/revenus.php"
            >

                <i class="fa-solid fa-wallet"></i>

                Mes revenus

            </a>


            <a
                href="/LOKA/app/proprietaires/Paiements/index.php"
            >

                <i class="fa-solid fa-credit-card"></i>

                Paiements

            </a>


            <a
                href="/LOKA/app/proprietaires/Quittances/quittances.php"
            >

                <i class="fa-solid fa-receipt"></i>

                Quittances

            </a>

 <a
                href="/LOKA/app/proprietaires/Documents/documents.php"
            >

                <i class="fa-solid fa-folder-open"></i>

                Documents

            </a>


            <a
                href="/LOKA/app/proprietaires/Parametres/parametres.php"
                 class="active"
            >

                <i class="fa-solid fa-gear"></i>

                Paramètres

            </a>


        </nav>

    </aside>


<main class="parametres-page">

    <div class="page-header">

        <div>
            <span class="page-kicker">
                MON COMPTE
            </span>

            <h1>
                Paramètres
            </h1>

            <p>
                Gérez vos informations personnelles et la sécurité de votre compte.
            </p>
        </div>

    </div>


    <?php if ($messageSucces !== ''): ?>

        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>

            <span>
                <?= htmlspecialchars($messageSucces) ?>
            </span>
        </div>

    <?php endif; ?>


    <?php if ($messageErreur !== ''): ?>

        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= htmlspecialchars($messageErreur) ?>
            </span>
        </div>

    <?php endif; ?>


    <div class="settings-grid">

        <!-- =====================================================
             PROFIL
        ====================================================== -->

        <section class="settings-card profile-card">

            <div class="card-header">

                <div class="card-icon">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>
                    <h2>
                        Informations personnelles
                    </h2>

                    <p>
                        Modifiez les informations associées à votre profil.
                    </p>
                </div>

            </div>


            <form
                method="POST"
                action=""
                class="settings-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="modifier_profil"
                >


                <div class="form-row">

                    <div class="form-group">

                        <label for="prenom">
                            Prénom
                        </label>

                        <input
                            type="text"
                            id="prenom"
                            name="prenom"
                            value="<?= htmlspecialchars((string) ($proprietaire['prenom'] ?? '')) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="nom">
                            Nom
                        </label>

                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            value="<?= htmlspecialchars((string) ($proprietaire['nom'] ?? '')) ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="email">
                            Adresse email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars((string) ($proprietaire['email'] ?? '')) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="telephone">
                            Téléphone
                        </label>

                        <input
                            type="text"
                            id="telephone"
                            name="telephone"
                            value="<?= htmlspecialchars((string) ($proprietaire['telephone'] ?? '')) ?>"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="adresse">
                        Adresse
                    </label>

                    <input
                        type="text"
                        id="adresse"
                        name="adresse"
                        value="<?= htmlspecialchars((string) ($proprietaire['adresse'] ?? '')) ?>"
                    >

                </div>


                <div class="form-row three-columns">

                    <div class="form-group">

                        <label for="code_postal">
                            Code postal
                        </label>

                        <input
                            type="text"
                            id="code_postal"
                            name="code_postal"
                            value="<?= htmlspecialchars((string) ($proprietaire['code_postal'] ?? '')) ?>"
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
                            value="<?= htmlspecialchars((string) ($proprietaire['ville'] ?? '')) ?>"
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
                            value="<?= htmlspecialchars((string) ($proprietaire['pays'] ?? '')) ?>"
                        >

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>

                        Enregistrer les modifications
                    </button>

                </div>

            </form>

        </section>


        <!-- =====================================================
             SÉCURITÉ
        ====================================================== -->

        <section class="settings-card security-card">

            <div class="card-header">

                <div class="card-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>

                <div>
                    <h2>
                        Sécurité
                    </h2>

                    <p>
                        Modifiez le mot de passe de votre compte.
                    </p>
                </div>

            </div>


            <form
                method="POST"
                action=""
                class="settings-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="modifier_mot_de_passe"
                >


                <div class="form-group">

                    <label for="ancien_mot_de_passe">
                        Ancien mot de passe
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="ancien_mot_de_passe"
                            name="ancien_mot_de_passe"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="ancien_mot_de_passe"
                            aria-label="Afficher le mot de passe"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>

                    </div>

                </div>


                <div class="form-group">

                    <label for="nouveau_mot_de_passe">
                        Nouveau mot de passe
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="nouveau_mot_de_passe"
                            name="nouveau_mot_de_passe"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="nouveau_mot_de_passe"
                            aria-label="Afficher le mot de passe"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>

                    </div>

                    <small>
                        Minimum 8 caractères.
                    </small>

                </div>


                <div class="form-group">

                    <label for="confirmation_mot_de_passe">
                        Confirmer le nouveau mot de passe
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="confirmation_mot_de_passe"
                            name="confirmation_mot_de_passe"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="confirmation_mot_de_passe"
                            aria-label="Afficher le mot de passe"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        <i class="fa-solid fa-key"></i>

                        Modifier le mot de passe
                    </button>

                </div>

            </form>

        </section>


        <!-- =====================================================
             COMPTE
        ====================================================== -->

        <section class="settings-card account-card">

            <div class="card-header">

                <div class="card-icon">
                    <i class="fa-solid fa-id-card"></i>
                </div>

                <div>
                    <h2>
                        Informations du compte
                    </h2>

                    <p>
                        Informations générales de votre compte LOKA.
                    </p>
                </div>

            </div>


            <div class="account-info">

                <div class="info-item">

                    <span class="info-label">
                        Nom
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            trim(
                                (string) ($proprietaire['prenom'] ?? '') .
                                ' ' .
                                (string) ($proprietaire['nom'] ?? '')
                            )
                        ) ?>
                    </strong>

                </div>


                <div class="info-item">

                    <span class="info-label">
                        Email
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            (string) ($proprietaire['email'] ?? '')
                        ) ?>
                    </strong>

                </div>


                <div class="info-item">

                    <span class="info-label">
                        Rôle
                    </span>

                    <span class="role-badge">
                        Propriétaire
                    </span>

                </div>

            </div>

        </section>


        <!-- =====================================================
             PRÉFÉRENCES
        ====================================================== -->

        <section class="settings-card preferences-card">

            <div class="card-header">

                <div class="card-icon">
                    <i class="fa-solid fa-sliders"></i>
                </div>

                <div>
                    <h2>
                        Préférences
                    </h2>

                    <p>
                        Options disponibles pour votre espace personnel.
                    </p>
                </div>

            </div>


            <div class="preference-list">

                <div class="preference-item">

                    <div class="preference-icon">
                        <i class="fa-solid fa-bell"></i>
                    </div>

                    <div class="preference-content">

                        <strong>
                            Notifications
                        </strong>

                        <span>
                            Les notifications pourront être configurées ici.
                        </span>

                    </div>

                    <span class="coming-badge">
                        Bientôt
                    </span>

                </div>


                <div class="preference-item">

                    <div class="preference-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>

                    <div class="preference-content">

                        <strong>
                            Notifications par email
                        </strong>

                        <span>
                            La gestion des emails pourra être configurée ici.
                        </span>

                    </div>

                    <span class="coming-badge">
                        Bientôt
                    </span>

                </div>

            </div>

        </section>

    </div>

</main>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const buttons = document.querySelectorAll('.password-toggle');

    buttons.forEach(function (button) {

        button.addEventListener('click', function () {

            const targetId = button.dataset.target;
            const input = document.getElementById(targetId);

            if (!input) {
                return;
            }

            const icon = button.querySelector('i');

            if (input.type === 'password') {

                input.type = 'text';

                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }

            } else {

                input.type = 'password';

                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });

    });

});
</script>

</body>
</html>