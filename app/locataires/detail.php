<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil | LOKA</title>
    <link rel="stylesheet" href="locataire.css">
</head>

<body>

<aside class="sidebar">

    <div class="logo">
        LOKA
    </div>

    <nav>

        <a href="index.php">
            <span>⌂</span>
            Accueil
        </a>

        <a href="detail.php" class="active">
            <span>👤</span>
            Mon profil
        </a>

        <a href="logement.php">
            <span>🏠</span>
            Mon logement
        </a>

        <a href="recherche.php">
                <span>🔎</span>
                Recherche
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>

            <a href="paiements/paiement.php">
                <span>💳</span>
                Mes paiements
            </a>

            <a href="../maintenance/index.php">
                <span>🔧</span>
                Maintenance
            </a>

            <a href="../documents/index.php">
                <span>📁</span>
                Documents
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="notifications/notification.php">
                <span>🔔</span>
                Notifications
            </a>

    </nav>

    <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon">↪</span>
                <span>Déconnexion</span>
            </a>

        </div>

</aside>

<main class="main-content">

    <header class="topbar">
        <div>
            <h1>Mon profil</h1>
            <p>Consultez vos informations personnelles</p>
        </div>
    </header>

    <section class="profile-section">

        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($locataire['prenom'], 0, 1) . substr($locataire['nom'], 0, 1)) ?>
            </div>

            <div>
                <h2>
                    <?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']) ?>
                </h2>

                <p>
                    <?= htmlspecialchars($locataire['email']) ?>
                </p>
            </div>
        </div>

        <div class="profile-card">

            <div class="profile-card-header">
                <h2>Informations personnelles</h2>
            </div>

            <div class="profile-grid">

                <div class="profile-field">
                    <span class="profile-label">Nom</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['nom']) ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Prénom</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['prenom']) ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Email</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['email']) ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Téléphone</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['telephone'] ?? 'Non renseigné') ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Date de naissance</span>
                    <span class="profile-value">
                        <?= !empty($locataire['date_naissance']) ? htmlspecialchars($locataire['date_naissance']) : 'Non renseignée' ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Profession</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['profession'] ?? 'Non renseignée') ?>
                    </span>
                </div>

            </div>

            <div class="profile-card-footer">
                <a href="modifier.php?section=personnel" class="btn-modifier">
                    Modifier
                </a>
            </div>

        </div>

        <div class="profile-card">

            <div class="profile-card-header">
                <h2>Adresse</h2>
            </div>

            <div class="profile-grid">

                <div class="profile-field profile-field-full">
                    <span class="profile-label">Adresse</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['adresse'] ?? 'Non renseignée') ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Code postal</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['code_postal'] ?? 'Non renseigné') ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Ville</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['ville'] ?? 'Non renseignée') ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Pays</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['pays'] ?? 'Non renseigné') ?>
                    </span>
                </div>

            </div>

            <div class="profile-card-footer">
                <a href="modifier.php?section=adresse" class="btn-modifier">
                    Modifier
                </a>
            </div>

        </div>

        <div class="profile-card">

            <div class="profile-card-header">
                <h2>Situation financière</h2>
            </div>

            <div class="profile-grid">

                <div class="profile-field">
                    <span class="profile-label">Revenu mensuel</span>
                    <span class="profile-value">
                        <?= $locataire['revenu_mensuel'] !== null ? htmlspecialchars(number_format((float)$locataire['revenu_mensuel'], 2, ',', ' ') . ' €') : 'Non renseigné' ?>
                    </span>
                </div>

                <div class="profile-field">
                    <span class="profile-label">Statut</span>
                    <span class="profile-value">
                        <?= htmlspecialchars($locataire['statut'] ?? 'Non renseigné') ?>
                    </span>
                </div>

            </div>

            <div class="profile-card-footer">
                <a href="modifier.php?section=financiere" class="btn-modifier">
                    Modifier
                </a>
            </div>

        </div>

    </section>

</main>

</body>
</html>