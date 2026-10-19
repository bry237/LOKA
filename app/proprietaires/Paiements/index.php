<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$auth = Authorization::requireRole('Proprietaire');

$config = require dirname(__DIR__, 3) . '/config/database.php';

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        ),
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erreur de connexion à la base de données.');
}


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


function formatMoney($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format(
        (float) $value,
        2,
        ',',
        ' '
    ) . ' €';
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


function statutLabel(?string $statut): string
{
    return match (strtoupper((string) $statut)) {
        'PENDING'   => 'En attente',
        'PAID'      => 'Payé',
        'LATE'      => 'En retard',
        'CANCELLED' => 'Annulé',
        'REFUND'    => 'Remboursé',
        default     => $statut ?: 'Non renseigné',
    };
}


function statutClass(?string $statut): string
{
    return match (strtoupper((string) $statut)) {
        'PAID'      => 'status-paid',
        'PENDING'   => 'status-pending',
        'LATE'      => 'status-late',
        'CANCELLED' => 'status-cancelled',
        'REFUND'    => 'status-refund',
        default     => 'status-default',
    };
}


function modePaiementLabel(?string $mode): string
{
    return match (strtoupper((string) $mode)) {
        'CASH'          => 'Espèces',
        'BANK_TRANSFER' => 'Virement',
        'CARD'          => 'Carte',
        'CHEQUE'        => 'Chèque',
        default         => $mode ?: '—',
    };
}


/* =========================================================
   UTILISATEUR CONNECTÉ
========================================================= */

$idUtilisateur = (int) ($auth['id_utilisateur'] ?? 0);

if ($idUtilisateur <= 0) {
    http_response_code(403);
    exit('Utilisateur non authentifié.');
}


/* =========================================================
   RÉCUPÉRATION DES PAIEMENTS
========================================================= */

$sql = "
    SELECT
        p.id_paiement,
        p.id_echeance,
        p.id_contrat,
        p.id_locataire,
        p.montant,
        p.date_paiement,
        p.mode_paiement,
        p.reference_transaction,
        p.statut,
        p.commentaire,

        c.numero AS numero_contrat,

        b.id_bien,
        b.reference AS reference_bien,
        b.titre AS titre_bien,

        e.periode_debut,
        e.periode_fin,
        e.montant_loyer,
        e.montant_charges,
        e.montant_total,
        e.date_echeance,
        e.statut AS statut_echeance,

        l.nom AS locataire_nom,
        l.prenom AS locataire_prenom

    FROM paiement p

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    INNER JOIN proprietaire pr
        ON pr.id_proprietaire = bp.id_proprietaire

    INNER JOIN utilisateur u
        ON u.email = pr.email

    LEFT JOIN echeance e
        ON e.id_echeance = p.id_echeance

    LEFT JOIN locataire l
        ON l.id_locataire = p.id_locataire

    WHERE u.id_utilisateur = :id_utilisateur
      AND c.deleted_at IS NULL
      AND b.deleted_at IS NULL

    ORDER BY
        p.date_paiement DESC,
        p.id_paiement DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id_utilisateur' => $idUtilisateur,
]);

$paiements = $stmt->fetchAll();


/* =========================================================
   STATISTIQUES
========================================================= */

$totalPaiements = count($paiements);

$totalPaye = 0.0;
$totalAttente = 0.0;
$totalRetard = 0.0;

$nombrePaye = 0;
$nombreAttente = 0;
$nombreRetard = 0;

foreach ($paiements as $paiement) {

    $montant = (float) $paiement['montant'];
    $statut = strtoupper((string) $paiement['statut']);

    if ($statut === 'PAID') {
        $totalPaye += $montant;
        $nombrePaye++;
    }

    if ($statut === 'PENDING') {
        $totalAttente += $montant;
        $nombreAttente++;
    }

    if ($statut === 'LATE') {
        $totalRetard += $montant;
        $nombreRetard++;
    }
}


/* =========================================================
   PAGE ACTIVE
========================================================= */

$activePage = 'paiements';

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Paiements | LOKA</title>

    <!-- CSS PRINCIPAL DU PROPRIETAIRE -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >


    <!-- CSS SPECIFIQUE AUX Paiements -->

     <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Paiements/paiements.css"
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

<div class="dashboard">

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
                class="active"
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
            >

                <i class="fa-solid fa-gear"></i>

                Paramètres

            </a>


        </nav>

    </aside>




    <!-- =====================================================
         CONTENU PRINCIPAL
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             EN-TÊTE
        ================================================== -->

        <header class="page-header">

            <div>

                <h1>
                    <i class="fa-solid fa-credit-card"></i>
                    Paiements
                </h1>

                <p>
                    Consultez les paiements liés à vos contrats.
                </p>

            </div>

        </header>


        <!-- =================================================
             STATISTIQUES
        ================================================== -->

        <section class="stats-grid">


            <!-- TOTAL -->

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-credit-card"></i>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Total des paiements
                    </span>

                    <strong class="stat-value">
                        <?= $totalPaiements ?>
                    </strong>

                    <span class="stat-description">
                        paiement(s)
                    </span>

                </div>

            </div>


            <!-- PAYÉ -->

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Montant payé
                    </span>

                    <strong class="stat-value">
                        <?= formatMoney($totalPaye) ?>
                    </strong>

                    <span class="stat-description">
                        <?= $nombrePaye ?> paiement(s) payé(s)
                    </span>

                </div>

            </div>


            <!-- EN ATTENTE -->

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        En attente
                    </span>

                    <strong class="stat-value">
                        <?= formatMoney($totalAttente) ?>
                    </strong>

                    <span class="stat-description">
                        <?= $nombreAttente ?> paiement(s)
                    </span>

                </div>

            </div>


            <!-- RETARD -->

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        En retard
                    </span>

                    <strong class="stat-value">
                        <?= formatMoney($totalRetard) ?>
                    </strong>

                    <span class="stat-description">
                        <?= $nombreRetard ?> paiement(s)
                    </span>

                </div>

            </div>

        </section>


        <!-- =================================================
             HISTORIQUE
        ================================================== -->

        <section class="payments-card">

            <div class="payments-header">

                <div>

                    <h2>
                        Historique des paiements
                    </h2>

                    <p>
                        Liste des paiements associés à vos contrats.
                    </p>

                </div>

            </div>


            <?php if (!$paiements): ?>


                <!-- AUCUN PAIEMENT -->

                <div class="empty-state">

                    <div class="empty-icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>

                    <h3>
                        Aucun paiement
                    </h3>

                    <p>
                        Aucun paiement n'est actuellement associé
                        à vos contrats.
                    </p>

                </div>


            <?php else: ?>


                <!-- TABLEAU -->

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Contrat
                                </th>

                                <th>
                                    Locataire
                                </th>

                                <th>
                                    Échéance
                                </th>

                                <th>
                                    Montant
                                </th>

                                <th>
                                    Date paiement
                                </th>

                                <th>
                                    Mode
                                </th>

                                <th>
                                    Statut
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($paiements as $paiement): ?>

                            <tr>


                                <!-- CONTRAT -->

                                <td>

                                    <div class="contract-number">

                                        <?= e(
                                            $paiement['numero_contrat']
                                        ) ?>

                                    </div>

                                    <div class="property-reference">

                                        <?= e(
                                            $paiement['reference_bien']
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $paiement['titre_bien']
                                            )
                                        ): ?>

                                            —
                                            <?= e(
                                                $paiement['titre_bien']
                                            ) ?>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- LOCATAIRE -->

                                <td>

                                    <?php

                                    $nomLocataire = trim(
                                        ($paiement['locataire_prenom'] ?? '')
                                        . ' '
                                        . ($paiement['locataire_nom'] ?? '')
                                    );

                                    ?>

                                    <?php if ($nomLocataire !== ''): ?>

                                        <div class="tenant-name">

                                            <?= e(
                                                $nomLocataire
                                            ) ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="muted">
                                            Non renseigné
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ÉCHÉANCE -->

                                <td>

                                    <div class="date-main">

                                        <?= formatDateFr(
                                            $paiement['date_echeance']
                                        ) ?>

                                    </div>

                                    <?php if (
                                        !empty(
                                            $paiement['periode_debut']
                                        )
                                        &&
                                        !empty(
                                            $paiement['periode_fin']
                                        )
                                    ): ?>

                                        <div class="property-reference">

                                            <?= formatDateFr(
                                                $paiement['periode_debut']
                                            ) ?>

                                            →

                                            <?= formatDateFr(
                                                $paiement['periode_fin']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </td>


                                <!-- MONTANT -->

                                <td>

                                    <span class="amount">

                                        <?= formatMoney(
                                            $paiement['montant']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- DATE PAIEMENT -->

                                <td>

                                    <?= formatDateFr(
                                        $paiement['date_paiement']
                                    ) ?>

                                </td>


                                <!-- MODE -->

                                <td>

                                    <?= e(
                                        modePaiementLabel(
                                            $paiement['mode_paiement']
                                        )
                                    ) ?>

                                </td>


                                <!-- STATUT -->

                                <td>

                                    <span
                                        class="badge <?= e(
                                            statutClass(
                                                $paiement['statut']
                                            )
                                        ) ?>"
                                    >

                                        <?= e(
                                            statutLabel(
                                                $paiement['statut']
                                            )
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


    </main>

</div>

</body>
</html>
