<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

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

$hasAdvancedFilters = (bool) (
    $filters['balcon'] || $filters['terrasse'] || $filters['jardin'] || $filters['piscine']
    || $filters['ascenseur'] || $filters['chauffage_sol'] || $filters['acces_pmr']
    || $filters['meuble'] || $filters['exposition'] || $filters['chauffage']
    || $filters['climatisation'] || $filters['etat_bien'] || $filters['classe_energetique']
    || $filters['type_vendeur']
);

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

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$pageTitle = 'Rechercher un logement';
$activeNav = 'Recherche';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Rechercher un logement';
$greetingSubtitle = 'Trouvez un logement correspondant à vos critères.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">'
    . '<link rel="stylesheet" href="ops.css?v=' . filemtime(__DIR__ . '/ops.css') . '">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="panel" style="margin-bottom:18px;">
      <div class="panel-header"><div class="panel-title">Critères de recherche</div></div>
      <form method="GET" action="recherche.php">
        <div class="form-grid cols-3">
          <div class="form-field">
            <label for="ville">Ville</label>
            <input type="text" id="ville" name="ville" value="<?= e($filters['ville']) ?>" placeholder="Ex : Limoges">
          </div>
          <div class="form-field">
            <label for="type_bien">Type de logement</label>
            <select id="type_bien" name="type_bien">
              <option value="">Tous les types</option>
              <option value="1" <?= selected(1, $filters['type_bien']) ?>>Appartement</option>
              <option value="2" <?= selected(2, $filters['type_bien']) ?>>Maison</option>
              <option value="3" <?= selected(3, $filters['type_bien']) ?>>Studio</option>
              <option value="4" <?= selected(4, $filters['type_bien']) ?>>Chambre</option>
              <option value="5" <?= selected(5, $filters['type_bien']) ?>>Colocation</option>
            </select>
          </div>
          <div class="form-field">
            <label for="pieces">Nombre de pièces minimum</label>
            <select id="pieces" name="pieces">
              <option value="">Peu importe</option>
              <option value="1" <?= selected(1, $filters['pieces']) ?>>1 pièce ou plus</option>
              <option value="2" <?= selected(2, $filters['pieces']) ?>>2 pièces ou plus</option>
              <option value="3" <?= selected(3, $filters['pieces']) ?>>3 pièces ou plus</option>
              <option value="4" <?= selected(4, $filters['pieces']) ?>>4 pièces ou plus</option>
              <option value="5" <?= selected(5, $filters['pieces']) ?>>5 pièces ou plus</option>
            </select>
          </div>
          <div class="form-field">
            <label for="loyer_min">Loyer minimum</label>
            <input type="number" id="loyer_min" name="loyer_min" value="<?= e($filters['loyer_min']) ?>" min="0" placeholder="€">
          </div>
          <div class="form-field">
            <label for="loyer_max">Loyer maximum</label>
            <input type="number" id="loyer_max" name="loyer_max" value="<?= e($filters['loyer_max']) ?>" min="0" placeholder="€">
          </div>
          <div class="form-field">
            <label for="surface_min">Surface minimum</label>
            <input type="number" id="surface_min" name="surface_min" value="<?= e($filters['surface_min']) ?>" min="0" placeholder="m²">
          </div>
          <div class="form-field">
            <label for="tri">Trier par</label>
            <select id="tri" name="tri">
              <option value="">Plus récentes</option>
              <option value="ancien" <?= selected('ancien', $filters['tri']) ?>>Plus anciennes</option>
              <option value="prix_croissant" <?= selected('prix_croissant', $filters['tri']) ?>>Prix croissant</option>
              <option value="prix_decroissant" <?= selected('prix_decroissant', $filters['tri']) ?>>Prix décroissant</option>
            </select>
          </div>
        </div>

        <details class="filters-advanced"<?= $hasAdvancedFilters ? ' open' : '' ?>>
          <summary>
            Plus de critères
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </summary>
          <div class="form-grid cols-3">
            <div class="form-section-title">Caractéristiques du logement</div>
            <div class="form-field span-2" style="grid-column:span 3;">
              <div style="display:flex;flex-wrap:wrap;gap:16px;">
                <label class="filter-checkbox"><input type="checkbox" name="balcon" value="1" <?= checked($filters['balcon']) ?>><span>Balcon</span></label>
                <label class="filter-checkbox"><input type="checkbox" name="terrasse" value="1" <?= checked($filters['terrasse']) ?>><span>Terrasse</span></label>
                <label class="filter-checkbox"><input type="checkbox" name="jardin" value="1" <?= checked($filters['jardin']) ?>><span>Jardin</span></label>
                <label class="filter-checkbox"><input type="checkbox" name="piscine" value="1" <?= checked($filters['piscine']) ?>><span>Piscine</span></label>
                <label class="filter-checkbox"><input type="checkbox" name="ascenseur" value="1" <?= checked($filters['ascenseur']) ?>><span>Ascenseur</span></label>
                <label class="filter-checkbox"><input type="checkbox" name="chauffage_sol" value="1" <?= checked($filters['chauffage_sol']) ?>><span>Chauffage au sol</span></label>
                <label class="filter-checkbox"><input type="checkbox" name="acces_pmr" value="1" <?= checked($filters['acces_pmr']) ?>><span>Accès PMR</span></label>
              </div>
            </div>

            <div class="form-field">
              <label for="meuble">Meublé</label>
              <select id="meuble" name="meuble">
                <option value="">Peu importe</option>
                <option value="OUI" <?= selected('OUI', $filters['meuble']) ?>>Oui</option>
                <option value="NON" <?= selected('NON', $filters['meuble']) ?>>Non</option>
              </select>
            </div>
            <div class="form-field">
              <label for="exposition">Exposition</label>
              <select id="exposition" name="exposition">
                <option value="">Peu importe</option>
                <option value="NORD" <?= selected('NORD', $filters['exposition']) ?>>Nord</option>
                <option value="SUD" <?= selected('SUD', $filters['exposition']) ?>>Sud</option>
                <option value="EST" <?= selected('EST', $filters['exposition']) ?>>Est</option>
                <option value="OUEST" <?= selected('OUEST', $filters['exposition']) ?>>Ouest</option>
                <option value="NORD_OUEST" <?= selected('NORD_OUEST', $filters['exposition']) ?>>Nord-Ouest</option>
                <option value="NORD_EST" <?= selected('NORD_EST', $filters['exposition']) ?>>Nord-Est</option>
                <option value="SUD_EST" <?= selected('SUD_EST', $filters['exposition']) ?>>Sud-Est</option>
                <option value="SUD_OUEST" <?= selected('SUD_OUEST', $filters['exposition']) ?>>Sud-Ouest</option>
              </select>
            </div>
            <div class="form-field">
              <label for="chauffage">Chauffage</label>
              <select id="chauffage" name="chauffage">
                <option value="">Peu importe</option>
                <option value="INDIVIDUEL" <?= selected('INDIVIDUEL', $filters['chauffage']) ?>>Individuel</option>
                <option value="COLLECTIF" <?= selected('COLLECTIF', $filters['chauffage']) ?>>Collectif</option>
              </select>
            </div>
            <div class="form-field">
              <label for="climatisation">Climatisation</label>
              <select id="climatisation" name="climatisation">
                <option value="">Peu importe</option>
                <option value="OUI" <?= selected('OUI', $filters['climatisation']) ?>>Oui</option>
                <option value="NON" <?= selected('NON', $filters['climatisation']) ?>>Non</option>
              </select>
            </div>
            <div class="form-field">
              <label for="etat_bien">État du bien</label>
              <select id="etat_bien" name="etat_bien">
                <option value="">Peu importe</option>
                <option value="TRES_BON" <?= selected('TRES_BON', $filters['etat_bien']) ?>>Très bon état</option>
                <option value="BON" <?= selected('BON', $filters['etat_bien']) ?>>Bon état</option>
                <option value="RENOVER" <?= selected('RENOVER', $filters['etat_bien']) ?>>À rénover</option>
                <option value="TRAVAUX_A_VENIR" <?= selected('TRAVAUX_A_VENIR', $filters['etat_bien']) ?>>Travaux à venir</option>
              </select>
            </div>
            <div class="form-field">
              <label for="classe_energetique">Classe énergétique</label>
              <select id="classe_energetique" name="classe_energetique">
                <option value="">Toutes les classes</option>
                <option value="A" <?= selected('A', $filters['classe_energetique']) ?>>A</option>
                <option value="B" <?= selected('B', $filters['classe_energetique']) ?>>B</option>
                <option value="C" <?= selected('C', $filters['classe_energetique']) ?>>C</option>
                <option value="D" <?= selected('D', $filters['classe_energetique']) ?>>D</option>
                <option value="E" <?= selected('E', $filters['classe_energetique']) ?>>E</option>
                <option value="F" <?= selected('F', $filters['classe_energetique']) ?>>F</option>
                <option value="G" <?= selected('G', $filters['classe_energetique']) ?>>G</option>
              </select>
            </div>
            <div class="form-field">
              <label for="type_vendeur">Type de vendeur</label>
              <select id="type_vendeur" name="type_vendeur">
                <option value="">Peu importe</option>
                <option value="PARTICULIER" <?= selected('PARTICULIER', $filters['type_vendeur']) ?>>Particulier</option>
                <option value="PROFESSIONNEL" <?= selected('PROFESSIONNEL', $filters['type_vendeur']) ?>>Professionnel</option>
              </select>
            </div>
          </div>
        </details>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Rechercher</button>
          <a href="recherche.php" class="btn">Réinitialiser</a>
        </div>
      </form>
    </div>

    <div class="page-header">
      <h1 style="font-size:18px;"><?= count($logements) ?> logement(s) trouvé(s)</h1>
    </div>

    <?php if (empty($logements)): ?>
      <div class="panel">
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></div>
          <h4>Aucun logement trouvé</h4>
          <p>Essayez de modifier vos critères de recherche.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="result-grid">
        <?php foreach ($logements as $logement): ?>
          <article class="panel">
            <div class="result-card-image">
              <?php if (!empty($logement['image_url'])): ?>
                <img src="<?= e($logement['image_url']) ?>" alt="<?= e($logement['titre']) ?>">
              <?php else: ?>
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
              <?php endif; ?>
            </div>
            <div class="result-card-body">
              <div class="result-card-top">
                <div>
                  <span class="result-type"><?= e($logement['type_bien']) ?></span>
                  <h3><?= e($logement['titre']) ?></h3>
                </div>
                <div class="result-price"><?= number_format((float) $logement['loyer'], 0, ',', ' ') ?> € <small>/ mois</small></div>
              </div>

              <p class="result-location">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-5.686-6-10a6 6 0 0 1 12 0c0 4.314-6 10-6 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
                <?= e($logement['code_postal']) ?> <?= e($logement['ville']) ?>
              </p>

              <div class="result-meta">
                <span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg><?= e((string) $logement['surface']) ?> m²</span>
                <span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-7a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v7"/><path d="M2 13V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M12 9V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4"/><path d="M2 20h20"/></svg><?= e((string) $logement['nombre_pieces']) ?> pièce(s)</span>
                <?php if ($logement['etage'] !== null): ?><span>Étage <?= e((string) $logement['etage']) ?></span><?php endif; ?>
              </div>

              <div class="housing-feature-row">
                <?php if (!empty($logement['balcon'])): ?><span class="housing-feature">Balcon</span><?php endif; ?>
                <?php if (!empty($logement['terrasse'])): ?><span class="housing-feature">Terrasse</span><?php endif; ?>
                <?php if (!empty($logement['jardin'])): ?><span class="housing-feature">Jardin</span><?php endif; ?>
                <?php if (!empty($logement['piscine'])): ?><span class="housing-feature">Piscine</span><?php endif; ?>
                <?php if (!empty($logement['ascenseur'])): ?><span class="housing-feature">Ascenseur</span><?php endif; ?>
                <?php if (!empty($logement['chauffage_sol'])): ?><span class="housing-feature">Chauffage au sol</span><?php endif; ?>
                <?php if (!empty($logement['acces_pmr'])): ?><span class="housing-feature">Accès PMR</span><?php endif; ?>
                <?php if (isset($logement['meuble']) && $logement['meuble'] === 'OUI'): ?><span class="housing-feature">Meublé</span><?php endif; ?>
                <?php if (isset($logement['climatisation']) && $logement['climatisation'] === 'OUI'): ?><span class="housing-feature">Climatisation</span><?php endif; ?>
              </div>

              <p class="result-description"><?= e($logement['description'] ?: 'Aucune description disponible.') ?></p>

              <div class="result-card-footer">
                <span>Charges : <?= number_format((float) $logement['charges'], 0, ',', ' ') ?> €</span>
                <a href="biens/detail.php?id=<?= (int) $logement['id_bien'] ?>" class="btn btn-primary btn-sm">Voir le logement</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
