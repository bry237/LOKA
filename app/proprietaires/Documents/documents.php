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

/**
 * ---------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------
 */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDateFr(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y', $timestamp);
}

function formatDateTimeFr(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y à H:i', $timestamp);
}

function formatFileSize(?int $bytes): string
{
    if ($bytes === null || $bytes < 0) {
        return '-';
    }

    if ($bytes < 1024) {
        return $bytes . ' o';
    }

    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
    }

    if ($bytes < 1024 * 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1, ',', ' ') . ' Mo';
    }

    return number_format(
        $bytes / (1024 * 1024 * 1024),
        1,
        ',',
        ' '
    ) . ' Go';
}

function mimeIcon(?string $mimeType): string
{
    $mime = strtolower(trim((string) $mimeType));

    if (str_contains($mime, 'pdf')) {
        return 'fa-file-pdf';
    }

    if (str_contains($mime, 'image')) {
        return 'fa-file-image';
    }

    if (
        str_contains($mime, 'word') ||
        str_contains($mime, 'document')
    ) {
        return 'fa-file-word';
    }

    if (
        str_contains($mime, 'excel') ||
        str_contains($mime, 'spreadsheet')
    ) {
        return 'fa-file-excel';
    }

    if (
        str_contains($mime, 'zip') ||
        str_contains($mime, 'rar')
    ) {
        return 'fa-file-zipper';
    }

    return 'fa-file';
}

function typeClass(?string $type): string
{
    $type = strtolower(trim((string) $type));

    return match ($type) {
        'pièce d\'identité' => 'type-identite',
        'bail' => 'type-bail',
        'quittance' => 'type-quittance',
        'état des lieux' => 'type-etat',
        'justificatif' => 'type-justificatif',
        'facture' => 'type-facture',
        'photo' => 'type-photo',
        default => 'type-autre',
    };
}

/**
 * ---------------------------------------------------------
 * Récupération du propriétaire connecté
 * ---------------------------------------------------------
 */

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

/**
 * ---------------------------------------------------------
 * Filtres
 * ---------------------------------------------------------
 */

$recherche = trim((string) ($_GET['recherche'] ?? ''));

$idTypeDocument = filter_input(
    INPUT_GET,
    'id_type_document',
    FILTER_VALIDATE_INT
);

$idBien = filter_input(
    INPUT_GET,
    'id_bien',
    FILTER_VALIDATE_INT
);

$idContrat = filter_input(
    INPUT_GET,
    'id_contrat',
    FILTER_VALIDATE_INT
);

if (!$idTypeDocument || $idTypeDocument <= 0) {
    $idTypeDocument = 0;
}

if (!$idBien || $idBien <= 0) {
    $idBien = 0;
}

if (!$idContrat || $idContrat <= 0) {
    $idContrat = 0;
}

/**
 * ---------------------------------------------------------
 * Types de documents disponibles
 * ---------------------------------------------------------
 */

$stmt = $pdo->prepare("
    SELECT
        td.id_type_document,
        td.nom,
        td.description
    FROM type_document td
    ORDER BY td.nom ASC
");

$stmt->execute();

$typesDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ---------------------------------------------------------
 * Biens du propriétaire
 * ---------------------------------------------------------
 */

$stmt = $pdo->prepare("
    SELECT DISTINCT
        b.id_bien,
        b.reference,
        b.titre
    FROM bien b
    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien
    WHERE bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
    ORDER BY
        b.reference ASC,
        b.titre ASC
");

$stmt->execute([$idProprietaire]);

$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ---------------------------------------------------------
 * Contrats du propriétaire
 * ---------------------------------------------------------
 */

$stmt = $pdo->prepare("
    SELECT DISTINCT
        c.id_contrat,
        c.numero,
        b.reference AS reference_bien
    FROM contrat c
    INNER JOIN bien b
        ON b.id_bien = c.id_bien
    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien
    WHERE bp.id_proprietaire = ?
      AND c.deleted_at IS NULL
      AND b.deleted_at IS NULL
    ORDER BY
        c.numero ASC
");

$stmt->execute([$idProprietaire]);

$contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ---------------------------------------------------------
 * Statistiques
 * ---------------------------------------------------------
 *
 * On considère comme accessible au propriétaire :
 * - un document qui lui est directement rattaché ;
 * - ou un document rattaché à l'un de ses biens.
 *
 * EXISTS évite les doublons lorsque plusieurs propriétaires
 * sont liés au même bien.
 */

/* Total documents */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM document d
    WHERE
        d.id_proprietaire = :owner_direct
        OR EXISTS (
            SELECT 1 FROM bien_proprietaire bp
            WHERE bp.id_bien = d.id_bien AND bp.id_proprietaire = :owner_bien
        )
        OR EXISTS (
            SELECT 1 FROM contrat c
            INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
            WHERE c.id_contrat = d.id_contrat AND bp.id_proprietaire = :owner_contrat
        )
        OR EXISTS (
            SELECT 1 FROM contrat c
            INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
            INNER JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat
            WHERE cl.id_locataire = d.id_locataire AND bp.id_proprietaire = :owner_locataire
        )
");

$stmt->execute([
    ':owner_direct' => $idProprietaire,
    ':owner_bien' => $idProprietaire,
    ':owner_contrat' => $idProprietaire,
    ':owner_locataire' => $idProprietaire
]);

$totalDocuments = (int) $stmt->fetchColumn();

/* Documents liés aux biens */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM document d
    WHERE d.id_bien IS NOT NULL
      AND (
          d.id_proprietaire = ?
          OR EXISTS (
              SELECT 1
              FROM bien_proprietaire bp
              WHERE bp.id_bien = d.id_bien
                AND bp.id_proprietaire = ?
          )
      )
");

$stmt->execute([
    $idProprietaire,
    $idProprietaire
]);

$documentsBiens = (int) $stmt->fetchColumn();

/* Documents liés aux contrats */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM document d
    WHERE d.id_contrat IS NOT NULL
      AND (
          d.id_proprietaire = ?
          OR EXISTS (
              SELECT 1
              FROM contrat c
              INNER JOIN bien_proprietaire bp
                  ON bp.id_bien = c.id_bien
              WHERE c.id_contrat = d.id_contrat
                AND bp.id_proprietaire = ?
          )
      )
");

$stmt->execute([
    $idProprietaire,
    $idProprietaire
]);

$documentsContrats = (int) $stmt->fetchColumn();

/* Documents liés aux locataires */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM document d
    WHERE d.id_locataire IS NOT NULL
      AND (
          d.id_proprietaire = ?
          OR EXISTS (
              SELECT 1
              FROM contrat c
              INNER JOIN bien_proprietaire bp
                  ON bp.id_bien = c.id_bien
              INNER JOIN contrat_locataire cl
                  ON cl.id_contrat = c.id_contrat
              WHERE cl.id_locataire = d.id_locataire
                AND bp.id_proprietaire = ?
          )
      )
");

$stmt->execute([
    $idProprietaire,
    $idProprietaire
]);

$documentsLocataires = (int) $stmt->fetchColumn();

/**
 * ---------------------------------------------------------
 * Dernier document
 * ---------------------------------------------------------
 */

$stmt = $pdo->prepare("
    SELECT MAX(d.created_at)
    FROM document d
    WHERE
        d.id_proprietaire = ?
        OR EXISTS (
            SELECT 1
            FROM bien_proprietaire bp
            WHERE bp.id_bien = d.id_bien
              AND bp.id_proprietaire = ?
        )
");

$stmt->execute([
    $idProprietaire,
    $idProprietaire
]);

$dernierDocument = $stmt->fetchColumn();

/**
 * ---------------------------------------------------------
 * Liste des documents
 * ---------------------------------------------------------
 */

$sql = "
    SELECT
        d.id_document,
        d.id_type_document,
        d.id_bien,
        d.id_contrat,
        d.id_proprietaire,
        d.id_locataire,
        d.nom,
        d.nom_original,
        d.mime_type,
        d.taille_octets,
        d.chemin_stockage,
        d.version,
        d.created_at,
        d.updated_at,

        td.nom AS type_document_nom,
        td.description AS type_document_description,

        b.reference AS reference_bien,
        b.titre AS titre_bien,

        c.numero AS numero_contrat,

        l.nom AS locataire_nom,
        l.prenom AS locataire_prenom

    FROM document d

    INNER JOIN type_document td
        ON td.id_type_document = d.id_type_document

    LEFT JOIN bien b
        ON b.id_bien = d.id_bien

    LEFT JOIN contrat c
        ON c.id_contrat = d.id_contrat

    LEFT JOIN locataire l
        ON l.id_locataire = d.id_locataire

    WHERE
        (
            d.id_proprietaire = :owner_direct
            OR EXISTS (
                SELECT 1 FROM bien_proprietaire bp
                WHERE bp.id_bien = d.id_bien AND bp.id_proprietaire = :owner_bien
            )
            OR EXISTS (
                SELECT 1 FROM contrat c_owner
                INNER JOIN bien_proprietaire bp_owner ON bp_owner.id_bien = c_owner.id_bien
                WHERE c_owner.id_contrat = d.id_contrat AND bp_owner.id_proprietaire = :owner_contrat
            )
            OR EXISTS (
                SELECT 1 FROM contrat c_locataire
                INNER JOIN bien_proprietaire bp_locataire ON bp_locataire.id_bien = c_locataire.id_bien
                INNER JOIN contrat_locataire cl_owner ON cl_owner.id_contrat = c_locataire.id_contrat
                WHERE cl_owner.id_locataire = d.id_locataire AND bp_locataire.id_proprietaire = :owner_locataire
            )
        )
";

$params = [
    ':owner_direct' => $idProprietaire,
    ':owner_bien' => $idProprietaire,
    ':owner_contrat' => $idProprietaire,
    ':owner_locataire' => $idProprietaire,
];

/**
 * Filtre type
 */
if ($idTypeDocument > 0) {
    $sql .= "
        AND d.id_type_document = :id_type_document
    ";

    $params[':id_type_document'] = $idTypeDocument;
}

/**
 * Filtre bien
 */
if ($idBien > 0) {
    $sql .= "
        AND d.id_bien = :id_bien
    ";

    $params[':id_bien'] = $idBien;
}

/**
 * Filtre contrat
 */
if ($idContrat > 0) {
    $sql .= "
        AND d.id_contrat = :id_contrat
    ";

    $params[':id_contrat'] = $idContrat;
}

/**
 * Recherche
 */
if ($recherche !== '') {
    $sql .= "
        AND (
            d.nom LIKE :recherche_nom
            OR d.nom_original LIKE :recherche_original
            OR td.nom LIKE :recherche_type
            OR b.reference LIKE :recherche_reference
            OR b.titre LIKE :recherche_titre
            OR c.numero LIKE :recherche_contrat
            OR l.nom LIKE :recherche_locataire_nom
            OR l.prenom LIKE :recherche_locataire_prenom
        )
    ";

    $searchValue = '%' . $recherche . '%';

    $params[':recherche_nom'] = $searchValue;
    $params[':recherche_original'] = $searchValue;
    $params[':recherche_type'] = $searchValue;
    $params[':recherche_reference'] = $searchValue;
    $params[':recherche_titre'] = $searchValue;
    $params[':recherche_contrat'] = $searchValue;
    $params[':recherche_locataire_nom'] = $searchValue;
    $params[':recherche_locataire_prenom'] = $searchValue;
}

$sql .= "
    ORDER BY
        d.created_at DESC,
        d.id_document DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalResultats = count($documents);

/**
 * ---------------------------------------------------------
 * Sidebar
 * ---------------------------------------------------------
 */

$activePage = 'documents';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mes documents | LOKA</title>

    
    <!-- CSS PRINCIPAL DU PROPRIETAIRE -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >

      <!-- CSS SPECIFIQUE AUX Documents -->

     <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Documents/documents.css"
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
                class="active"
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


<main class="main-content">

    <!-- -------------------------------------------------
         En-tête
    -------------------------------------------------- -->

    <section class="page-header">

        <div>
            <span class="page-eyebrow">
                ESPACE PROPRIÉTAIRE
            </span>

            <h1>
                Mes documents
            </h1>

            <p>
                Consultez les documents associés à vos biens,
                contrats et locations.
            </p>
        </div>

    </section>

    <!-- -------------------------------------------------
         Statistiques
    -------------------------------------------------- -->

    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">
                    Total documents
                </span>

                <strong class="stat-value">
                    <?= $totalDocuments ?>
                </strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-building"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">
                    Liés aux biens
                </span>

                <strong class="stat-value">
                    <?= $documentsBiens ?>
                </strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-file-contract"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">
                    Liés aux contrats
                </span>

                <strong class="stat-value">
                    <?= $documentsContrats ?>
                </strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">
                    Liés aux locataires
                </span>

                <strong class="stat-value">
                    <?= $documentsLocataires ?>
                </strong>
            </div>
        </div>

    </section>

    <!-- -------------------------------------------------
         Barre de filtres
    -------------------------------------------------- -->

    <section class="filters-card">

        <div class="filters-title">
            <div>
                <i class="fa-solid fa-filter"></i>

                <div>
                    <h2>Rechercher un document</h2>

                    <p>
                        Utilisez les filtres pour retrouver rapidement
                        un document.
                    </p>
                </div>
            </div>
        </div>

        <form
            method="get"
            action="/LOKA/app/proprietaires/Documents/documents.php"
            class="filters-form"
        >

            <div class="search-group">

                <label for="recherche">
                    Recherche
                </label>

                <div class="search-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="recherche"
                        name="recherche"
                        value="<?= e($recherche) ?>"
                        placeholder="Nom, fichier, bien, contrat, locataire..."
                    >

                </div>

            </div>

            <div class="filter-group">

                <label for="id_type_document">
                    Type de document
                </label>

                <select
                    id="id_type_document"
                    name="id_type_document"
                >

                    <option value="0">
                        Tous les types
                    </option>

                    <?php foreach ($typesDocuments as $type): ?>

                        <option
                            value="<?= (int) $type['id_type_document'] ?>"
                            <?= $idTypeDocument === (int) $type['id_type_document'] ? 'selected' : '' ?>
                        >
                            <?= e($type['nom']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-group">

                <label for="id_bien">
                    Bien
                </label>

                <select
                    id="id_bien"
                    name="id_bien"
                >

                    <option value="0">
                        Tous les biens
                    </option>

                    <?php foreach ($biens as $bien): ?>

                        <option
                            value="<?= (int) $bien['id_bien'] ?>"
                            <?= $idBien === (int) $bien['id_bien'] ? 'selected' : '' ?>
                        >
                            <?= e($bien['reference']) ?>
                            <?php if (!empty($bien['titre'])): ?>
                                — <?= e($bien['titre']) ?>
                            <?php endif; ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-group">

                <label for="id_contrat">
                    Contrat
                </label>

                <select
                    id="id_contrat"
                    name="id_contrat"
                >

                    <option value="0">
                        Tous les contrats
                    </option>

                    <?php foreach ($contrats as $contrat): ?>

                        <option
                            value="<?= (int) $contrat['id_contrat'] ?>"
                            <?= $idContrat === (int) $contrat['id_contrat'] ? 'selected' : '' ?>
                        >
                            <?= e($contrat['numero']) ?>

                            <?php if (!empty($contrat['reference_bien'])): ?>
                                — <?= e($contrat['reference_bien']) ?>
                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn-filter"
                >
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Rechercher
                </button>

                <a
                    href="/LOKA/app/proprietaires/Documents/documents.php"
                    class="btn-reset"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Réinitialiser
                </a>

            </div>

        </form>

    </section>

    <!-- -------------------------------------------------
         Résultat
    -------------------------------------------------- -->

    <section class="documents-card">

        <div class="documents-card-header">

            <div>
                <h2>
                    Documents
                </h2>

                <p>
                    <?= $totalResultats ?>
                    document<?= $totalResultats > 1 ? 's' : '' ?>
                    trouvé<?= $totalResultats > 1 ? 's' : '' ?>
                </p>
            </div>

        </div>

        <?php if ($documents): ?>

            <div class="table-wrapper">

                <table class="documents-table">

                    <thead>
                        <tr>

                            <th>
                                Document
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Bien
                            </th>

                            <th>
                                Contrat
                            </th>

                            <th>
                                Locataire
                            </th>

                            <th>
                                Version
                            </th>

                            <th>
                                Taille
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($documents as $document): ?>

                        <?php
                        $typeNom = (string) (
                            $document['type_document_nom'] ?? 'Autre'
                        );

                        $hasFile =
                            trim(
                                (string) (
                                    $document['chemin_stockage'] ?? ''
                                )
                            ) !== '';

                        $locataireNom = trim(
                            (string) (
                                ($document['locataire_prenom'] ?? '')
                            ) . ' ' .
                            (string) (
                                ($document['locataire_nom'] ?? '')
                            )
                        );

                        if ($locataireNom === '') {
                            $locataireNom = '-';
                        }
                        ?>

                        <tr>

                            <!-- Document -->

                            <td>

                                <div class="document-main">

                                    <div class="document-icon">
                                        <i
                                            class="fa-solid <?= e(mimeIcon($document['mime_type'] ?? null)) ?>"
                                        ></i>
                                    </div>

                                    <div class="document-info">

                                        <strong>
                                            <?= e(
                                                $document['nom']
                                                ?: $document['nom_original']
                                                ?: 'Document sans nom'
                                            ) ?>
                                        </strong>

                                        <?php if (!empty($document['nom_original'])): ?>

                                            <span>
                                                <?= e(
                                                    $document['nom_original']
                                                ) ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </td>

                            <!-- Type -->

                            <td>

                                <span
                                    class="document-type <?= e(typeClass($typeNom)) ?>"
                                >
                                    <?= e($typeNom) ?>
                                </span>

                            </td>

                            <!-- Bien -->

                            <td>

                                <?php if (!empty($document['reference_bien'])): ?>

                                    <div class="related-info">

                                        <strong>
                                            <?= e(
                                                $document['reference_bien']
                                            ) ?>
                                        </strong>

                                        <?php if (!empty($document['titre_bien'])): ?>

                                            <span>
                                                <?= e(
                                                    $document['titre_bien']
                                                ) ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                <?php else: ?>

                                    <span class="empty-value">
                                        -
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- Contrat -->

                            <td>

                                <?php if (!empty($document['numero_contrat'])): ?>

                                    <span class="contract-number">
                                        <?= e(
                                            $document['numero_contrat']
                                        ) ?>
                                    </span>

                                <?php else: ?>

                                    <span class="empty-value">
                                        -
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- Locataire -->

                            <td>

                                <?php if ($locataireNom !== '-'): ?>

                                    <div class="tenant-info">

                                        <i class="fa-solid fa-user"></i>

                                        <span>
                                            <?= e($locataireNom) ?>
                                        </span>

                                    </div>

                                <?php else: ?>

                                    <span class="empty-value">
                                        -
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- Version -->

                            <td>

                                <span class="version-badge">
                                    V<?= (int) (
                                        $document['version'] ?: 1
                                    ) ?>
                                </span>

                            </td>

                            <!-- Taille -->

                            <td>

                                <?= e(
                                    formatFileSize(
                                        $document['taille_octets'] !== null
                                            ? (int) $document['taille_octets']
                                            : null
                                    )
                                ) ?>

                            </td>

                            <!-- Date -->

                            <td>

                                <div class="date-info">

                                    <strong>
                                        <?= e(
                                            formatDateFr(
                                                $document['created_at']
                                            )
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= e(
                                            formatDateTimeFr(
                                                $document['created_at']
                                            )
                                        ) ?>
                                    </span>

                                </div>

                            </td>

                            <!-- Actions -->

                            <td>

                                <?php if ($hasFile): ?>

                                    <div class="document-actions">

                                        <a
                                            href="/LOKA/app/proprietaires/Documents/telechargerDocument.php?id=<?= (int) $document['id_document'] ?>&action=voir"
                                            class="document-button view-document"
                                            target="_blank"
                                            rel="noopener"
                                            title="Voir le document"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            Voir
                                        </a>

                                        <a
                                            href="/LOKA/app/proprietaires/Documents/telechargerDocument.php?id=<?= (int) $document['id_document'] ?>&action=telecharger"
                                            class="document-button download-document"
                                            title="Télécharger le document"
                                        >
                                            <i class="fa-solid fa-download"></i>
                                            Télécharger
                                        </a>

                                    </div>

                                <?php else: ?>

                                    <span class="no-document">
                                        <i class="fa-solid fa-file-circle-xmark"></i>
                                        Aucun fichier
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    <i class="fa-solid fa-folder-open"></i>
                </div>

                <h3>
                    Aucun document trouvé
                </h3>

                <p>
                    Aucun document ne correspond aux critères sélectionnés.
                </p>

                <a
                    href="/LOKA/app/proprietaires/Documents/documents.php"
                    class="btn-reset empty-reset"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Afficher tous les documents
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>