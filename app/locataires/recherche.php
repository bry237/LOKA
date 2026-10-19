<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();

$filters = [
    'ville' => trim($_GET['ville'] ?? ''),
    'type_bien' => $_GET['type_bien'] ?? '',
    'loyer_min' => $_GET['loyer_min'] ?? '',
    'loyer_max' => $_GET['loyer_max'] ?? '',
    'pieces' => $_GET['pieces'] ?? '',
    'surface_min' => $_GET['surface_min'] ?? '',

    'balcon' => isset($_GET['balcon']) ? 1 : 0,
    'terrasse' => isset($_GET['terrasse']) ? 1 : 0,
    'jardin' => isset($_GET['jardin']) ? 1 : 0,
    'piscine' => isset($_GET['piscine']) ? 1 : 0,
    'ascenseur' => isset($_GET['ascenseur']) ? 1 : 0,
    'chauffage_sol' => isset($_GET['chauffage_sol']) ? 1 : 0,
    'acces_pmr' => isset($_GET['acces_pmr']) ? 1 : 0,

    'meuble' => $_GET['meuble'] ?? '',
    'exposition' => $_GET['exposition'] ?? '',
    'chauffage' => $_GET['chauffage'] ?? '',
    'climatisation' => $_GET['climatisation'] ?? '',
    'etat_bien' => $_GET['etat_bien'] ?? '',
    'classe_energetique' => $_GET['classe_energetique'] ?? '',
    'type_vendeur' => $_GET['type_vendeur'] ?? '',

    'tri' => $_GET['tri'] ?? ''
];

$logements = $controller->searchHousing($filters);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function selected($value, $current): string
{
    return (string) $value === (string) $current ? 'selected' : '';
}

function checked($value): string
{
    return !empty($value) ? 'checked' : '';
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rechercher un logement | LOKA</title>

    <link rel="stylesheet" href="./locataire.css">

    <style>

        .checkbox-filter label {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            cursor: pointer;
            margin: 0;
        }

        .checkbox-filter input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            flex-shrink: 0;
            cursor: pointer;
        }

        .checkbox-filter span {
            display: inline-block;
        }

        .filter-separator {
            grid-column: 1 / -1;
            margin: 15px 0 5px;
            padding-bottom: 8px;
            border-bottom: 1px solid #DDE8E8;
        }

        .filter-separator h3 {
            margin: 0;
            color: #075E63;
            font-size: 16px;
        }

        .housing-features {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .housing-feature {
            padding: 5px 10px;
            background: #EFFBFA;
            color: #075E63;
            border-radius: 6px;
            font-size: 13px;
        }

        .housing-result-card {
            overflow: hidden;
        }

        .housing-result-image {
            width: 100%;
            height: 230px;
            overflow: hidden;
            background: #EEF3F3;
        }

        .housing-result-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .housing-result-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #EEF3F3;
            color: #075E63;
            font-size: 55px;
        }

        .housing-result-content {
            padding: 24px;
        }

        @media (max-width: 768px) {

            .housing-filters {
                grid-template-columns: 1fr !important;
            }

            .filter-separator {
                grid-column: 1;
            }

            .housing-result-image {
                height: 200px;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>

        <nav>

            <a href="index.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
                Accueil
            </a>

            <a href="detail.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
                Mon profil
            </a>

            <a href="logement.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></span>
                Mon logement
            </a>

            <a href="recherche.php" class="active">
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

                <h1>
                    Rechercher un logement
               </h1>

                <p>
                    Trouvez un logement correspondant à vos critères.
                </p>

            </div>

            

        </header>

        <section class="search-section">

            <div class="section-header">

                <div>

                    <span class="section-label">
                        RECHERCHE
                    </span>

                    <h2>
                        Trouver votre logement
                    </h2>

                </div>

            </div>

            <form
                method="GET"
                action="recherche.php"
                class="housing-filters"
            >

                <div class="filter-group">

                    <label for="ville">
                        Ville
                    </label>

                    <input
                        type="text"
                        id="ville"
                        name="ville"
                        value="<?= e($filters['ville']) ?>"
                        placeholder="Ex : Limoges"
                    >

                </div>

                <div class="filter-group">

                    <label for="type_bien">
                        Type de logement
                    </label>

                    <select id="type_bien" name="type_bien">

                        <option value="">
                            Tous les types
                        </option>

                        <option value="1" <?= selected(1, $filters['type_bien']) ?>>
                            Appartement
                        </option>

                        <option value="2" <?= selected(2, $filters['type_bien']) ?>>
                            Maison
                        </option>

                        <option value="3" <?= selected(3, $filters['type_bien']) ?>>
                            Studio
                        </option>

                        <option value="4" <?= selected(4, $filters['type_bien']) ?>>
                            Chambre
                        </option>

                        <option value="5" <?= selected(5, $filters['type_bien']) ?>>
                            Colocation
                        </option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="loyer_min">
                        Loyer minimum
                    </label>

                    <input
                        type="number"
                        id="loyer_min"
                        name="loyer_min"
                        value="<?= e($filters['loyer_min']) ?>"
                        min="0"
                        placeholder="€"
                    >

                </div>

                <div class="filter-group">

                    <label for="loyer_max">
                        Loyer maximum
                    </label>

                    <input
                        type="number"
                        id="loyer_max"
                        name="loyer_max"
                        value="<?= e($filters['loyer_max']) ?>"
                        min="0"
                        placeholder="€"
                    >

                </div>

                <div class="filter-group">

                    <label for="pieces">
                        Nombre de pièces minimum
                    </label>

                    <select id="pieces" name="pieces">

                        <option value="">
                            Peu importe
                        </option>

                        <option value="1" <?= selected(1, $filters['pieces']) ?>>
                            1 pièce ou plus
                        </option>

                        <option value="2" <?= selected(2, $filters['pieces']) ?>>
                            2 pièces ou plus
                        </option>

                        <option value="3" <?= selected(3, $filters['pieces']) ?>>
                            3 pièces ou plus
                        </option>

                        <option value="4" <?= selected(4, $filters['pieces']) ?>>
                            4 pièces ou plus
                        </option>

                        <option value="5" <?= selected(5, $filters['pieces']) ?>>
                            5 pièces ou plus
                        </option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="surface_min">
                        Surface minimum
                    </label>

                    <input
                        type="number"
                        id="surface_min"
                        name="surface_min"
                        value="<?= e($filters['surface_min']) ?>"
                        min="0"
                        placeholder="m²"
                    >

                </div>

                <div class="filter-separator">

                    <h3>
                        Caractéristiques du logement
                    </h3>

                </div>

                <div class="filter-group checkbox-filter">
                    <label for="balcon">
                        <input
                            type="checkbox"
                            id="balcon"
                            name="balcon"
                            value="1"
                            <?= checked($filters['balcon']) ?>
                        >
                        <span>Balcon</span>
                    </label>
                </div>

                <div class="filter-group checkbox-filter">
                    <label for="terrasse">
                        <input
                            type="checkbox"
                            id="terrasse"
                            name="terrasse"
                            value="1"
                            <?= checked($filters['terrasse']) ?>
                        >
                        <span>Terrasse</span>
                    </label>
                </div>

                <div class="filter-group checkbox-filter">
                    <label for="jardin">
                        <input
                            type="checkbox"
                            id="jardin"
                            name="jardin"
                            value="1"
                            <?= checked($filters['jardin']) ?>
                        >
                        <span>Jardin</span>
                    </label>
                </div>

                <div class="filter-group checkbox-filter">
                    <label for="piscine">
                        <input
                            type="checkbox"
                            id="piscine"
                            name="piscine"
                            value="1"
                            <?= checked($filters['piscine']) ?>
                        >
                        <span>Piscine</span>
                    </label>
                </div>

                <div class="filter-group checkbox-filter">
                    <label for="ascenseur">
                        <input
                            type="checkbox"
                            id="ascenseur"
                            name="ascenseur"
                            value="1"
                            <?= checked($filters['ascenseur']) ?>
                        >
                        <span>Ascenseur</span>
                    </label>
                </div>

                <div class="filter-group checkbox-filter">
                    <label for="chauffage_sol">
                        <input
                            type="checkbox"
                            id="chauffage_sol"
                            name="chauffage_sol"
                            value="1"
                            <?= checked($filters['chauffage_sol']) ?>
                        >
                        <span>Chauffage au sol</span>
                    </label>
                </div>

                <div class="filter-group checkbox-filter">
                    <label for="acces_pmr">
                        <input
                            type="checkbox"
                            id="acces_pmr"
                            name="acces_pmr"
                            value="1"
                            <?= checked($filters['acces_pmr']) ?>
                        >
                        <span>Accès PMR</span>
                    </label>
                </div>

                <div class="filter-group">

                    <label for="meuble">
                        Meublé
                    </label>

                    <select id="meuble" name="meuble">

                        <option value="">
                            Peu importe
                        </option>

                        <option value="OUI" <?= selected('OUI', $filters['meuble']) ?>>
                            Oui
                        </option>

                        <option value="NON" <?= selected('NON', $filters['meuble']) ?>>
                            Non
                        </option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="exposition">
                        Exposition
                    </label>

                    <select id="exposition" name="exposition">

                        <option value="">
                            Peu importe
                        </option>

                        <option value="NORD" <?= selected('NORD', $filters['exposition']) ?>>
                            Nord
                        </option>

                        <option value="SUD" <?= selected('SUD', $filters['exposition']) ?>>
                            Sud
                        </option>

                        <option value="EST" <?= selected('EST', $filters['exposition']) ?>>
                            Est
                        </option>

                        <option value="OUEST" <?= selected('OUEST', $filters['exposition']) ?>>
                            Ouest
                        </option>

                        <option value="NORD_OUEST" <?= selected('NORD_OUEST', $filters['exposition']) ?>>
                            Nord-Ouest
                        </option>

                        <option value="NORD_EST" <?= selected('NORD_EST', $filters['exposition']) ?>>
                            Nord-Est
                        </option>

                        <option value="SUD_EST" <?= selected('SUD_EST', $filters['exposition']) ?>>
                            Sud-Est
                        </option>

                        <option value="SUD_OUEST" <?= selected('SUD_OUEST', $filters['exposition']) ?>>
                            Sud-Ouest
                        </option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="chauffage">
                        Chauffage
                    </label>

                    <select id="chauffage" name="chauffage">

                        <option value="">
                            Peu importe
                        </option>

                        <option value="INDIVIDUEL" <?= selected('INDIVIDUEL', $filters['chauffage']) ?>>
                            Individuel
                        </option>

                        <option value="COLLECTIF" <?= selected('COLLECTIF', $filters['chauffage']) ?>>
                            Collectif
                        </option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="climatisation">
                        Climatisation
                    </label>

                    <select id="climatisation" name="climatisation">

                        <option value="">
                            Peu importe
                        </option>

                        <option value="OUI" <?= selected('OUI', $filters['climatisation']) ?>>
                            Oui
                        </option>

                        <option value="NON" <?= selected('NON', $filters['climatisation']) ?>>
                            Non
                        </option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="etat_bien">
                        État du bien
                    </label>

                    <select id="etat_bien" name="etat_bien">

                        <option value="">
                            Peu importe
                        </option>

                        <option value="TRES_BON" <?= selected('TRES_BON', $filters['etat_bien']) ?>>
                            Très bon état
                        </option>

                        <option value="BON" <?= selected('BON', $filters['etat_bien']) ?>>
                            Bon état
                        </option>

                        <option value="RENOVER" <?= selected('RENOVER', $filters['etat_bien']) ?>>
                            À rénover
                        </option>

                        <option value="TRAVAUX_A_VENIR" <?= selected('TRAVAUX_A_VENIR', $filters['etat_bien']) ?>>
                            Travaux à venir
                        </option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="classe_energetique">
                        Classe énergétique
                    </label>

                    <select id="classe_energetique" name="classe_energetique">

                        <option value="">
                            Toutes les classes
                        </option>

                        <option value="A" <?= selected('A', $filters['classe_energetique']) ?>>A</option>
                        <option value="B" <?= selected('B', $filters['classe_energetique']) ?>>B</option>
                        <option value="C" <?= selected('C', $filters['classe_energetique']) ?>>C</option>
                        <option value="D" <?= selected('D', $filters['classe_energetique']) ?>>D</option>
                        <option value="E" <?= selected('E', $filters['classe_energetique']) ?>>E</option>
                        <option value="F" <?= selected('F', $filters['classe_energetique']) ?>>F</option>
                        <option value="G" <?= selected('G', $filters['classe_energetique']) ?>>G</option>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="type_vendeur">
                        Type de vendeur
                    </label>

                    <select id="type_vendeur" name="type_vendeur">

                        <option value="">
                            Peu importe
                        </option>

                        <option value="PARTICULIER" <?= selected('PARTICULIER', $filters['type_vendeur']) ?>>
                            Particulier
                        </option>

                        <option value="PROFESSIONNEL" <?= selected('PROFESSIONNEL', $filters['type_vendeur']) ?>>
                            Professionnel
                        </option>

                    </select>

                </div>

                <div class="filter-separator">

                    <h3>
                        Trier les résultats
                    </h3>

                </div>

                <div class="filter-group">

                    <label for="tri">
                        Trier par
                    </label>

                    <select id="tri" name="tri">

                        <option value="">
                            Pertinence
                        </option>

                        <option value="recent" <?= selected('recent', $filters['tri']) ?>>
                            Plus récentes
                        </option>

                        <option value="ancien" <?= selected('ancien', $filters['tri']) ?>>
                            Plus anciennes
                        </option>

                        <option value="prix_croissant" <?= selected('prix_croissant', $filters['tri']) ?>>
                            Prix croissant
                        </option>

                        <option value="prix_decroissant" <?= selected('prix_decroissant', $filters['tri']) ?>>
                            Prix décroissant
                        </option>

                    </select>

                </div>

                <div class="filter-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Rechercher
                    </button>

                    <a
                        href="recherche.php"
                        class="btn-secondary"
                    >
                        Réinitialiser
                    </a>

                </div>

            </form>

        </section>

        <section class="results-section">

            <div class="results-header">

                <div>

                    <span class="section-label">
                        LOGEMENTS DISPONIBLES
                    </span>

                    <h2>
                        <?= count($logements) ?> logement(s) trouvé(s)
                    </h2>

                </div>

            </div>

            <?php if (empty($logements)): ?>

                <div class="no-results">

                    <div class="no-results-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </div>

                    <h3>
                        Aucun logement trouvé
                    </h3>

                    <p>
                        Essayez de modifier vos critères de recherche.
                    </p>

                </div>

            <?php else: ?>

                <div class="housing-results">

                    <?php foreach ($logements as $logement): ?>

                        <article class="housing-result-card">

                            <div class="housing-result-image">

                                <?php if (!empty($logement['image_url'])): ?>

                                    <img
                                        src="<?= e($logement['image_url']) ?>"
                                        alt="<?= e($logement['titre']) ?>"
                                    >

                                <?php else: ?>

                                    <div class="housing-result-image-placeholder">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="housing-result-content">

                                <div class="housing-result-header">

                                    <div>

                                        <span class="housing-type">
                                            <?= e($logement['type_bien']) ?>
                                        </span>

                                        <h3>
                                            <?= e($logement['titre']) ?>
                                        </h3>

                                    </div>

                                    <div class="housing-price">

                                        <?= number_format(
                                            (float) $logement['loyer'],
                                            0,
                                            ',',
                                            ' '
                                        ) ?> €

                                        <span>
                                            / mois
                                        </span>

                                    </div>

                                </div>

                                <p class="housing-location">

                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M12 21s-6-5.686-6-10a6 6 0 0 1 12 0c0 4.314-6 10-6 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>

                                    <?= e($logement['code_postal']) ?>

                                    <?= e($logement['ville']) ?>

                                </p>

                                <div class="housing-characteristics">

                                    <span>
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
                                        <?= e((string) $logement['surface']) ?>
                                        m²
                                    </span>

                                    <span>
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M2 20v-7a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v7"/><path d="M2 13V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M12 9V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4"/><path d="M2 20h20"/></svg>
                                        <?= e((string) $logement['nombre_pieces']) ?>
                                        pièce(s)
                                    </span>

                                    <?php if ($logement['etage'] !== null): ?>

                                        <span>
                                            Étage
                                            <?= e((string) $logement['etage']) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="housing-features">

                                    <?php if (!empty($logement['balcon'])): ?>
                                        <span class="housing-feature">
                                            Balcon
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($logement['terrasse'])): ?>
                                        <span class="housing-feature">
                                            Terrasse
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($logement['jardin'])): ?>
                                        <span class="housing-feature">
                                            Jardin
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($logement['piscine'])): ?>
                                        <span class="housing-feature">
                                            Piscine
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($logement['ascenseur'])): ?>
                                        <span class="housing-feature">
                                            Ascenseur
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($logement['chauffage_sol'])): ?>
                                        <span class="housing-feature">
                                            Chauffage au sol
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($logement['acces_pmr'])): ?>
                                        <span class="housing-feature">
                                            Accès PMR
                                        </span>
                                    <?php endif; ?>

                                    <?php if (
                                        isset($logement['meuble'])
                                        && $logement['meuble'] === 'OUI'
                                    ): ?>

                                        <span class="housing-feature">
                                            Meublé
                                        </span>

                                    <?php endif; ?>

                                    <?php if (
                                        isset($logement['climatisation'])
                                        && $logement['climatisation'] === 'OUI'
                                    ): ?>

                                        <span class="housing-feature">
                                            Climatisation
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <p class="housing-description">

                                    <?= e(
                                        $logement['description']
                                        ?: 'Aucune description disponible.'
                                    ) ?>

                                </p>

                                <div class="housing-result-footer">

                                    <span>

                                        Charges :

                                        <?= number_format(
                                            (float) $logement['charges'],
                                            0,
                                            ',',
                                            ' '
                                        ) ?> €

                                    </span>

                                    <a
                                        href="../biens/detail.php?id=<?= (int) $logement['id_bien'] ?>"
                                        class="btn-primary"
                                    >
                                        Voir le logement
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>