<?php

declare(strict_types=1);

require_once __DIR__ . '/ContratController.php';

$controller = new ContratController();
$locataire = $controller->getLocataire();

$prenom = (string) ($locataire['prenom'] ?? '');
$nom = (string) ($locataire['nom'] ?? '');
$initiales = mb_strtoupper(
    mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)
);


try {
    $contrats = $controller->getContrats();
} catch (Throwable $e) {
    error_log(
        'Erreur contrats LOKA : ' .
        $e->getMessage() .
        PHP_EOL .
        $e->getTraceAsString()
    );

    http_response_code(500);

    echo '<h2>Erreur de chargement des contrats</h2>';
    echo '<pre>';
    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</pre>';

    exit;
}


function h(?string $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y', $timestamp) : '—';
}

function statutSignature(?string $statut): array
{
    return match ($statut) {
        'SIGNE_SIMULATION' => ['Signé (simulation)', 'success'],
        'DEPOSE' => ['En attente de vérification', 'pending'],
        'VALIDE' => ['Validé par l’agence', 'success'],
        'REFUSE' => ['À corriger', 'danger'],
        default => ['En attente de signature', 'pending'],
    };
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes contrats | LOKA</title>

    <link rel="stylesheet" href="../locataire.css">
    <link rel="stylesheet" href="contrats.css">
</head>

<body>

<div class="layout contrats-layout">

    <!-- MENU LATÉRAL -->
    <aside class="sidebar">

        <div class="logo">LOKA</div>

        <nav>
            <a href="/LOKA/app/locataires/index.php">
                <span class="nav-icon">⌂</span>
                <span>Accueil</span>
            </a>

            <a href="/LOKA/app/locataires/detail.php">
                <span class="nav-icon">👤</span>
                <span>Mon profil</span>
            </a>

            <a href="/LOKA/app/locataires/logement.php">
                <span class="nav-icon">🏠</span>
                <span>Mon logement</span>
            </a>

            <a href="/LOKA/app/locataires/recherche.php">
                <span class="nav-icon">🔎</span>
                <span>Recherche</span>
            </a>

            <a class="active"
               href="/LOKA/app/locataires/contrats/index.php">
                <span class="nav-icon">📄</span>
                <span>Mon contrat</span>
            </a>

            <a href="/LOKA/app/locataires/paiements/paiement.php">
                <span class="nav-icon">💳</span>
                <span>Mes paiements</span>
            </a>

            <a href="/LOKA/app/maintenance/index.php">
                <span class="nav-icon">🔧</span>
                <span>Maintenance</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php">
                <span class="nav-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="/LOKA/app/locataires/notifications/notification.php">
                <span class="nav-icon">🔔</span>
                <span>Notifications</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="nav-icon">↪</span>
                <span>Déconnexion</span>
            </a>
        </div>

    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <header class="topbar">

            <div class="topbar-left">
                <h2>Mes contrats</h2>
                <p>Gérez votre contrat de location</p>
            </div>

            <div class="user-info">

                <div class="user-details">
                    <strong><?= h($prenom . ' ' . $nom) ?></strong>
                    <span>Locataire</span>
                </div>

                <div class="avatar">
                    <?= h($initiales ?: 'L') ?>
                </div>

            </div>

        </header>

        <section class="housing-page contrats-page">

            <?php if (empty($contrats)): ?>

                <!-- AFFICHAGE SI AUCUN CONTRAT -->
                <div class="housing-card-empty">

                    <div class="housing-card-header">
                        <span class="section-label">MES CONTRATS</span>

                        <h2>Mes contrats</h2>

                        <p>
                            Retrouvez ici les contrats de location
                            associés à votre profil.
                        </p>
                    </div>

                    <div class="housing-card-divider"></div>

                    <div class="housing-empty-content">

                        <div class="housing-empty-icon"
                             aria-hidden="true">

                            <svg
                                viewBox="0 0 64 64"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">

                                <path
                                    d="M18 7H38L50 19V55C50 57.2 48.2 59 46 59H18C15.8 59 14 57.2 14 55V11C14 8.8 15.8 7 18 7Z"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linejoin="round"/>

                                <path
                                    d="M38 7V20H50M23 32H41M23 41H41M23 50H35"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linecap="round"/>

                            </svg>

                        </div>

                        <h3>Aucun contrat pour le moment</h3>

                        <p>
                            Vous n'avez pas encore de contrat associé
                            à votre profil. Lorsqu'un contrat sera mis
                            à votre disposition par l'agence, vous
                            pourrez le consulter, le télécharger,
                            simuler sa signature ou déposer le PDF signé
                            depuis cette page.
                        </p>

                    </div>

                </div>

            <?php else: ?>

                <!-- AFFICHAGE SI DES CONTRATS EXISTENT -->
                <div class="contracts-list-heading">

                    <div>
                        <span class="section-label">ESPACE LOCATAIRE</span>

                        <h2>Mes contrats</h2>

                        <p>
                            Consultez vos contrats, simulez votre
                            signature et déposez un PDF signé.
                        </p>
                    </div>

                    <span class="count">
                        <?= count($contrats) ?>
                        contrat(s)
                    </span>

                </div>


                <div class="contract-list">

                    <?php foreach ($contrats as $c): ?>

                        <?php
                        [$label, $class] = statutSignature(
                            $c['statut_signature'] ?? null
                        );
                        ?>

                        <article class="contract-card">

                            <div class="contract-symbol">PDF</div>

                            <div class="contract-info">

                                <div class="contract-title">

                                    <h2>
                                        Contrat <?= h($c['numero'] ?? '') ?>
                                    </h2>

                                    <span class="badge <?= h($class) ?>">
                                        <?= h($label) ?>
                                    </span>

                                </div>

                                <p>
                                    <?= h(
                                        $c['titre_bien']
                                        ?: $c['reference_bien']
                                        ?: 'Logement'
                                    ) ?>
                                </p>

                                <div class="meta">
                                    <span>
                                        Début :
                                        <strong>
                                            <?= dateFr($c['date_debut'] ?? null) ?>
                                        </strong>
                                    </span>

                                    <span>
                                        Fin :
                                        <strong>
                                            <?= dateFr($c['date_fin'] ?? null) ?>
                                        </strong>
                                    </span>
                                </div>

                                <div class="meta">
                                    <span>
                                        Loyer :
                                        <strong>
                                            <?= number_format(
                                                (float) ($c['loyer'] ?? 0),
                                                2,
                                                ',',
                                                ' '
                                            ) ?> €
                                        </strong>
                                    </span>

                                    <span>
                                        Charges :
                                        <strong>
                                            <?= number_format(
                                                (float) ($c['charges'] ?? 0),
                                                2,
                                                ',',
                                                ' '
                                            ) ?> €
                                        </strong>
                                    </span>
                                </div>

                            </div>

                            <a class="button"
                               href="detail.php?id=<?= (int) $c['id_contrat'] ?>">
                                Voir le contrat
                                <span aria-hidden="true">→</span>
                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
