<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';

$auth = Authorization::requireRole('Proprietaire');
$pdo = Database::connection();

/**
 * Échappement HTML
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format monétaire
 */
function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Format date française
 */
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


/* =========================================================
   UTILISATEUR CONNECTÉ
   ========================================================= */

$idUtilisateur = (int) ($auth['id_utilisateur'] ?? 0);

if ($idUtilisateur <= 0) {
    http_response_code(403);
    exit('Utilisateur non identifié.');
}


/* =========================================================
   PROPRIÉTAIRE CONNECTÉ
   ========================================================= */

$stmt = $pdo->prepare("
    SELECT pr.id_proprietaire
    FROM proprietaire pr
    INNER JOIN utilisateur u
        ON u.email = pr.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$idProprietaire = (int) ($stmt->fetchColumn() ?: 0);

if ($idProprietaire <= 0) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}


/* =========================================================
   FILTRES
   ========================================================= */

$annee = filter_input(
    INPUT_GET,
    'annee',
    FILTER_VALIDATE_INT
);

if (
    $annee === false ||
    $annee === null ||
    $annee < 2000 ||
    $annee > 2100
) {
    $annee = 0;
}


$mois = filter_input(
    INPUT_GET,
    'mois',
    FILTER_VALIDATE_INT
);

if (
    $mois === false ||
    $mois === null ||
    $mois < 0 ||
    $mois > 12
) {
    $mois = 0;
}


$recherche = trim(
    (string) ($_GET['recherche'] ?? '')
);


$moisNoms = [
    1  => 'Janvier',
    2  => 'Février',
    3  => 'Mars',
    4  => 'Avril',
    5  => 'Mai',
    6  => 'Juin',
    7  => 'Juillet',
    8  => 'Août',
    9  => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Décembre',
];


/* =========================================================
   ANNÉES DISPONIBLES
   ========================================================= */

$stmtAnnees = $pdo->prepare("
    SELECT DISTINCT YEAR(q.date_emission) AS annee

    FROM quittance q

    INNER JOIN paiement p
        ON p.id_paiement = q.id_paiement

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
      AND c.deleted_at IS NULL
      AND q.date_emission IS NOT NULL

    ORDER BY annee DESC
");

$stmtAnnees->execute([$idProprietaire]);

$anneesDisponibles = $stmtAnnees->fetchAll(
    PDO::FETCH_COLUMN
);


/* =========================================================
   STATISTIQUES GÉNÉRALES
   ========================================================= */

$stmtStatsGlobales = $pdo->prepare("
    SELECT
        COUNT(*) AS nombre_quittances,

        COALESCE(
            SUM(p.montant),
            0
        ) AS montant_total,

        COALESCE(
            SUM(
                CASE
                    WHEN q.chemin_fichier_pdf IS NOT NULL
                     AND q.chemin_fichier_pdf <> ''
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS nombre_pdf,

        MAX(q.date_emission) AS derniere_quittance

    FROM quittance q

    INNER JOIN paiement p
        ON p.id_paiement = q.id_paiement

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
      AND c.deleted_at IS NULL
");

$stmtStatsGlobales->execute([$idProprietaire]);

$statsGlobales = $stmtStatsGlobales->fetch(
    PDO::FETCH_ASSOC
) ?: [];


$nombreQuittances = (int) (
    $statsGlobales['nombre_quittances'] ?? 0
);

$montantTotal = (float) (
    $statsGlobales['montant_total'] ?? 0
);

$nombrePdf = (int) (
    $statsGlobales['nombre_pdf'] ?? 0
);

$derniereQuittance =
    $statsGlobales['derniere_quittance'] ?? null;


/* =========================================================
   REQUÊTE PRINCIPALE
   ========================================================= */

$sql = "
    SELECT
        q.id_quittance,
        q.id_paiement,
        q.numero,
        q.date_emission,
        q.periode_debut,
        q.periode_fin,
        q.chemin_fichier_pdf,

        p.montant AS montant_paiement,
        p.date_paiement,
        p.mode_paiement,
        p.statut AS statut_paiement,

        c.id_contrat,
        c.numero AS numero_contrat,

        b.id_bien,
        b.reference AS reference_bien,
        b.titre AS titre_bien,

        l.id_locataire,
        l.nom AS locataire_nom,
        l.prenom AS locataire_prenom

    FROM quittance q

    INNER JOIN paiement p
        ON p.id_paiement = q.id_paiement

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    LEFT JOIN locataire l
        ON l.id_locataire = p.id_locataire

    WHERE bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
      AND c.deleted_at IS NULL
";

$params = [
    $idProprietaire
];


/* =========================================================
   FILTRE ANNÉE
   ========================================================= */

if ($annee > 0) {

    $sql .= "
        AND YEAR(q.date_emission) = ?
    ";

    $params[] = $annee;
}


/* =========================================================
   FILTRE MOIS
   ========================================================= */

if ($mois > 0) {

    $sql .= "
        AND MONTH(q.date_emission) = ?
    ";

    $params[] = $mois;
}


/* =========================================================
   RECHERCHE
   ========================================================= */

if ($recherche !== '') {

    $sql .= "
        AND (
            q.numero LIKE ?
            OR c.numero LIKE ?
            OR b.reference LIKE ?
            OR b.titre LIKE ?
            OR l.nom LIKE ?
            OR l.prenom LIKE ?
        )
    ";

    $motif = '%' . $recherche . '%';

    $params[] = $motif;
    $params[] = $motif;
    $params[] = $motif;
    $params[] = $motif;
    $params[] = $motif;
    $params[] = $motif;
}


/* =========================================================
   TRI
   ========================================================= */

$sql .= "
    ORDER BY
        q.date_emission DESC,
        q.id_quittance DESC
";


/* =========================================================
   EXÉCUTION
   ========================================================= */

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$quittances = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/* =========================================================
   STATISTIQUES FILTRÉES
   ========================================================= */

$nombreResultats = count($quittances);

$montantFiltre = 0;

foreach ($quittances as $quittance) {

    $montantFiltre += (float) (
        $quittance['montant_paiement'] ?? 0
    );
}


/* =========================================================
   SIDEBAR
   ========================================================= */

$activePage = 'quittances';

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mes quittances | LOKA</title>


    <!-- CSS PRINCIPAL -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >


    <!-- SIDEBAR -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/sidebar.css"
    >


    <!-- CSS QUITTANCES -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Quittances/quittances.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>



    <?php $pageActive = 'quittances'; require dirname(__DIR__) . '/sidebar.php'; ?>


<main class="main-content">


    <!-- =====================================================
         EN-TÊTE
         ===================================================== -->

    <div class="page-header">

        <div>

            <h1>Mes quittances</h1>

            <p>
                Consultez les quittances liées à vos biens.
            </p>

        </div>

    </div>


    <!-- =====================================================
         STATISTIQUES
         ===================================================== -->

    <section class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-receipt"></i>
            </div>

            <div>

                <span class="stat-label">
                    Total quittances
                </span>

                <strong class="stat-value">
                    <?= $nombreQuittances ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-euro-sign"></i>
            </div>

            <div>

                <span class="stat-label">
                    Montant total
                </span>

                <strong class="stat-value">
                    <?= e(formatMoney($montantTotal)) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-file-pdf"></i>
            </div>

            <div>

                <span class="stat-label">
                    Quittances PDF
                </span>

                <strong class="stat-value">
                    <?= $nombrePdf ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <div>

                <span class="stat-label">
                    Dernière émission
                </span>

                <strong class="stat-value stat-date">
                    <?= e(formatDateFr($derniereQuittance)) ?>
                </strong>

            </div>

        </div>


    </section>


    <!-- =====================================================
         RECHERCHE ET FILTRES
         ===================================================== -->

    <section class="filters-card">


        <div class="filters-header">

            <h2>
                Rechercher une quittance
            </h2>


        </div>


        <form
            method="get"
            action="/LOKA/app/proprietaires/Quittances/quittances.php"
            class="filters-form"
        >


            <!-- RECHERCHE -->

          <div class="search-group">

                <div class="search-box">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        name="recherche"
                        value="<?= e($recherche) ?>"
                        placeholder="Rechercher par numéro, bien ou locataire..."
                        autocomplete="off"
                    >

                </div>

            </div>


            <!-- FILTRES -->

            <div class="filter-options">


                <!-- ANNÉE -->

                <div class="filter-group">

                    <label for="annee">
                        Année
                    </label>

                    <select
                        id="annee"
                        name="annee"
                    >

                        <option value="0">
                            Toutes les années
                        </option>

                        <?php foreach ($anneesDisponibles as $anneeDisponible): ?>

                            <option
                                value="<?= (int) $anneeDisponible ?>"
                                <?= $annee === (int) $anneeDisponible ? 'selected' : '' ?>
                            >
                                <?= (int) $anneeDisponible ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- MOIS -->

                <div class="filter-group">

                    <label for="mois">
                        Mois
                    </label>

                    <select
                        id="mois"
                        name="mois"
                    >

                        <option value="0">
                            Tous les mois
                        </option>

                        <?php foreach ($moisNoms as $numeroMois => $nomMois): ?>

                            <option
                                value="<?= $numeroMois ?>"
                                <?= $mois === $numeroMois ? 'selected' : '' ?>
                            >
                                <?= e($nomMois) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- BOUTONS -->

                <div class="filter-actions">


                    <button
                        type="submit"
                        class="filter-button"
                    >

                        <i class="fa-solid fa-filter"></i>

                        Filtrer

                    </button>


                    <a
                        href="/LOKA/app/proprietaires/Quittances/quittances.php"
                        class="reset-button"
                    >

                        <i class="fa-solid fa-rotate-left"></i>

                        Réinitialiser

                    </a>


                </div>


            </div>


        </form>


    </section>


    <!-- =====================================================
         RÉSULTATS
         ===================================================== -->

    <section class="result-summary">


        <div>

            <strong>
                <?= $nombreResultats ?>
            </strong>

            quittance<?= $nombreResultats > 1 ? 's' : '' ?>

            affichée<?= $nombreResultats > 1 ? 's' : '' ?>

        </div>


        <?php if (
            $annee > 0 ||
            $mois > 0 ||
            $recherche !== ''
        ): ?>

            <div class="filtered-amount">

                Montant affiché :

                <strong>
                    <?= e(formatMoney($montantFiltre)) ?>
                </strong>

            </div>

        <?php endif; ?>


    </section>

    

    <!-- =====================================================
         TABLEAU
         ===================================================== -->

    <section class="quittances-card">


        <?php if (!$quittances): ?>


            <div class="empty-state">


                <div class="empty-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>

                

                <h2>
                    Aucune quittance trouvée
                </h2>


                <p>
                    Aucune quittance ne correspond aux critères sélectionnés.
                </p>

                


                <?php if (
                    $annee > 0 ||
                    $mois > 0 ||
                    $recherche !== ''
                ): ?>


                    <a
                        href="/LOKA/app/proprietaires/Quittances/quittances.php"
                        class="reset-button"
                    >

                        <i class="fa-solid fa-list"></i>

                        Voir toutes les quittances

                    </a>


                <?php endif; ?>


            </div>


        <?php else: ?>


            <div class="table-wrapper">


                <table class="quittances-table">


                    <thead>

                        <tr>

                            <th>
                                Quittance
                            </th>

                            <th>
                                Locataire
                            </th>

                            <th>
                                Bien
                            </th>

                            <th>
                                Période
                            </th>

                            <th>
                                Montant
                            </th>

                            <th>
                                Émission
                            </th>

                            <th>
                                Document
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($quittances as $quittance): ?>


                        <?php

                        $nomLocataire = trim(
                            (string) (
                                $quittance['locataire_prenom'] ?? ''
                            )
                            . ' ' .
                            (string) (
                                $quittance['locataire_nom'] ?? ''
                            )
                        );


                        if ($nomLocataire === '') {
                            $nomLocataire = 'Non renseigné';
                        }


                        $nomBien =
                            $quittance['titre_bien']
                            ?: $quittance['reference_bien']
                            ?: 'Bien non renseigné';


                        $hasPdf = !empty(
                            $quittance['chemin_fichier_pdf']
                        );

                        ?>


                        <tr>


                            <!-- QUITTANCE -->

                            <td>

                                <div class="quittance-number">

                                    <i class="fa-solid fa-receipt"></i>

                                    <div>

                                        <strong>
                                            <?= e($quittance['numero']) ?>
                                        </strong>

                                        <span>
                                            <?= e(
                                                $quittance['numero_contrat']
                                                ?? 'Contrat'
                                            ) ?>
                                        </span>

                                    </div>

                                </div>

                            </td>


                            <!-- LOCATAIRE -->

                            <td>

                                <div class="tenant-info">

                                    <span class="tenant-name">
                                        <?= e($nomLocataire) ?>
                                    </span>

                                    <?php if (
                                        !empty(
                                            $quittance['id_locataire']
                                        )
                                    ): ?>

                                        <span class="tenant-label">
                                            Locataire
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- BIEN -->

                            <td>

                                <div class="property-info">

                                    <strong>
                                        <?= e($nomBien) ?>
                                    </strong>

                                    <?php if (
                                        !empty(
                                            $quittance['reference_bien']
                                        )
                                    ): ?>

                                        <span>
                                            Réf.
                                            <?= e(
                                                $quittance['reference_bien']
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- PÉRIODE -->

                            <td>

                                <?php if (
                                    !empty(
                                        $quittance['periode_debut']
                                    )
                                    ||
                                    !empty(
                                        $quittance['periode_fin']
                                    )
                                ): ?>


                                    <div class="period-info">

                                        <span>
                                            <?= e(
                                                formatDateFr(
                                                    $quittance['periode_debut']
                                                )
                                            ) ?>
                                        </span>

                                        <i class="fa-solid fa-arrow-right"></i>

                                        <span>
                                            <?= e(
                                                formatDateFr(
                                                    $quittance['periode_fin']
                                                )
                                            ) ?>
                                        </span>

                                    </div>


                                <?php else: ?>


                                    <span class="muted">
                                        Non renseignée
                                    </span>


                                <?php endif; ?>

                            </td>


                            <!-- MONTANT -->

                            <td>

                                <strong class="amount">

                                    <?= e(
                                        formatMoney(
                                            (float) (
                                                $quittance[
                                                    'montant_paiement'
                                                ] ?? 0
                                            )
                                        )
                                    ) ?>

                                </strong>

                            </td>


                            <!-- ÉMISSION -->

                            <td>

                                <span class="date-value">

                                    <?= e(
                                        formatDateFr(
                                            $quittance[
                                                'date_emission'
                                            ]
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- DOCUMENT -->

                            <td>

                                <?php if ($hasPdf): ?>

                                    <div class="pdf-actions">

                                        <a
                                            href="/LOKA/app/proprietaires/Quittances/telechargerQuittances.php?id=<?= (int) $quittance['id_quittance'] ?>&action=voir"
                                            class="pdf-button view-pdf"
                                            target="_blank"
                                            rel="noopener"
                                            title="Voir la quittance"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            Voir
                                        </a>

                                        <a
                                            href="/LOKA/app/proprietaires/Quittances/telechargerQuittance.php?id=<?= (int) $quittance['id_quittance'] ?>&action=telecharger"
                                            class="pdf-button download-pdf"
                                            title="Télécharger la quittance"
                                        >
                                            <i class="fa-solid fa-download"></i>
                                            Télécharger
                                        </a>

                                    </div>

                                <?php else: ?>

                                    <span class="no-pdf">
                                        <i class="fa-solid fa-file-circle-xmark"></i>
                                        Aucun PDF
                                    </span>

                                <?php endif; ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>


            </div>


        <?php endif; ?>


    </section>

</main>


</body>
</html>