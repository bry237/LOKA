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
            <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
            Accueil
        </a>

        <a href="detail.php" class="active">
            <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
            Mon profil
        </a>

        <a href="logement.php">
            <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></span>
            Mon logement
        </a>

        <a href="recherche.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                Recherche
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                </span>

                <span>
                    Mon contrat
                </span>
            </a>

            <a href="paiements/paiement.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                Mes paiements
            </a>

            <a href="../maintenance/index.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></span>
                Maintenance
            </a>

            <a href="../documents/index.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></span>
                Documents
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></span>
                <span>Quittances</span>
            </a>

            <a href="notifications/notification.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                Notifications
            </a>

    </nav>

    <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span>
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