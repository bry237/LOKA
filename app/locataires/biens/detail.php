<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/LocataireController.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

$idBien = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$logement = $controller->getHousingDetail($idBien);

if (!$logement) {
    http_response_code(404);
    exit('Logement introuvable ou plus disponible.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function labelOuiNon(?string $value): string
{
    return match ($value) {
        'OUI' => 'Oui',
        'NON' => 'Non',
        default => '—',
    };
}

function labelExposition(?string $value): string
{
    return match ($value) {
        'NORD' => 'Nord',
        'SUD' => 'Sud',
        'EST' => 'Est',
        'OUEST' => 'Ouest',
        'NORD_OUEST' => 'Nord-Ouest',
        'NORD_EST' => 'Nord-Est',
        'SUD_EST' => 'Sud-Est',
        'SUD_OUEST' => 'Sud-Ouest',
        default => '—',
    };
}

function labelChauffage(?string $value): string
{
    return match ($value) {
        'INDIVIDUEL' => 'Individuel',
        'COLLECTIF' => 'Collectif',
        default => '—',
    };
}

function labelEtatBien(?string $value): string
{
    return match ($value) {
        'TRES_BON' => 'Très bon état',
        'BON' => 'Bon état',
        'RENOVER' => 'À rénover',
        'TRAVAUX_A_VENIR' => 'Travaux à venir',
        default => '—',
    };
}

function labelTypeVendeur(?string $value): string
{
    return match ($value) {
        'PARTICULIER' => 'Particulier',
        'PROFESSIONNEL' => 'Professionnel',
        default => '—',
    };
}

$images = $logement['images'];
$adresseComplete = trim(
    ($logement['ligne1'] ?? '') . (!empty($logement['ligne2']) ? ', ' . $logement['ligne2'] : '')
);

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$pageTitle = $logement['titre'];
$activeNav = 'Recherche';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = $logement['titre'];
$greetingSubtitle = 'Détail de l’annonce.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">'
    . '<link rel="stylesheet" href="../ops.css?v=' . filemtime(dirname(__DIR__) . '/ops.css') . '">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="../recherche.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux résultats
    </a>

    <div class="listing-hero">
      <?php if (!empty($images)): ?>
        <img src="<?= e($images[0]) ?>" alt="<?= e($logement['titre']) ?>">
      <?php else: ?>
        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
      <?php endif; ?>
    </div>
    <?php if (count($images) > 1): ?>
      <div class="listing-gallery">
        <?php foreach (array_slice($images, 1) as $img): ?>
          <img src="<?= e($img) ?>" alt="">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="detail-header">
      <div class="detail-identity">
        <div>
          <span class="result-type"><?= e($logement['type_bien']) ?></span>
          <h1 class="detail-name"><?= e($logement['titre']) ?></h1>
          <p class="detail-sub">
            <?= e($logement['code_postal']) ?> <?= e($logement['ville']) ?><?= $adresseComplete !== '' ? ' — ' . e($adresseComplete) : '' ?>
          </p>
        </div>
      </div>
      <div class="result-price" style="font-size:22px;"><?= number_format((float) $logement['loyer'], 0, ',', ' ') ?> € <small>/ mois CC</small></div>
    </div>

    <div class="result-meta" style="margin:-10px 0 18px;">
      <span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg><?= e((string) $logement['surface']) ?> m²</span>
      <span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-7a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v7"/><path d="M2 13V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M12 9V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4"/><path d="M2 20h20"/></svg><?= e((string) $logement['nombre_pieces']) ?> pièce(s)</span>
      <?php if ($logement['etage'] !== null): ?><span>Étage <?= e((string) $logement['etage']) ?></span><?php endif; ?>
      <?php if (!empty($logement['date_disponibilite'])): ?><span>Disponible le <?= date('d/m/Y', strtotime((string) $logement['date_disponibilite'])) ?></span><?php endif; ?>
    </div>

    <div class="housing-feature-row" style="margin-bottom:18px;">
      <?php foreach ([
          'balcon' => 'Balcon', 'terrasse' => 'Terrasse', 'jardin' => 'Jardin', 'piscine' => 'Piscine',
          'ascenseur' => 'Ascenseur', 'chauffage_sol' => 'Chauffage au sol', 'acces_pmr' => 'Accès PMR',
      ] as $key => $label): ?>
        <?php if (!empty($logement[$key])): ?><span class="housing-feature"><?= $label ?></span><?php endif; ?>
      <?php endforeach; ?>
      <?php if ($logement['meuble'] === 'OUI'): ?><span class="housing-feature">Meublé</span><?php endif; ?>
      <?php if ($logement['climatisation'] === 'OUI'): ?><span class="housing-feature">Climatisation</span><?php endif; ?>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Description</div></div>
          <p style="padding:0 18px 18px;font-size:13.5px;color:var(--text);line-height:1.6;white-space:pre-line;">
            <?= e($logement['description'] ?: 'Aucune description disponible.') ?>
          </p>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Caractéristiques</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Type de bien</span><span class="info-value"><?= e($logement['type_bien']) ?></span></div>
            <div class="info-row"><span class="info-label">Meublé</span><span class="info-value"><?= labelOuiNon($logement['meuble']) ?></span></div>
            <div class="info-row"><span class="info-label">Climatisation</span><span class="info-value"><?= labelOuiNon($logement['climatisation']) ?></span></div>
            <div class="info-row"><span class="info-label">Chauffage</span><span class="info-value"><?= labelChauffage($logement['chauffage']) ?></span></div>
            <div class="info-row"><span class="info-label">Exposition</span><span class="info-value"><?= labelExposition($logement['exposition']) ?></span></div>
            <div class="info-row"><span class="info-label">État du bien</span><span class="info-value"><?= labelEtatBien($logement['etat_bien']) ?></span></div>
            <?php if (!empty($logement['classe_energetique'])): ?>
              <div class="info-row"><span class="info-label">Classe énergétique (DPE)</span><span class="info-value"><?= e($logement['classe_energetique']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($logement['ges_classe'])): ?>
              <div class="info-row"><span class="info-label">Classe GES</span><span class="info-value"><?= e($logement['ges_classe']) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">Type de vendeur</span><span class="info-value"><?= labelTypeVendeur($logement['type_vendeur']) ?></span></div>
          </div>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Loyer &amp; charges</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Loyer hors charges</span><span class="info-value"><?= number_format((float) $logement['loyer'] - (float) $logement['charges'], 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Charges</span><span class="info-value"><?= number_format((float) $logement['charges'], 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Total mensuel</span><span class="info-value"><?= number_format((float) $logement['loyer'], 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Dépôt de garantie (caution)</span><span class="info-value"><?= number_format((float) $logement['caution'], 0, ',', ' ') ?> €</span></div>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Contact de l'agence</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Agence</span><span class="info-value"><?= e($logement['agence_nom']) ?></span></div>
            <?php if (!empty($logement['agence_telephone'])): ?>
              <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><a href="tel:<?= e($logement['agence_telephone']) ?>"><?= e($logement['agence_telephone']) ?></a></span></div>
            <?php endif; ?>
            <?php if (!empty($logement['agence_email'])): ?>
              <div class="info-row"><span class="info-label">Email</span><span class="info-value"><a href="mailto:<?= e($logement['agence_email']) ?>"><?= e($logement['agence_email']) ?></a></span></div>
            <?php endif; ?>
          </div>
          <?php if (!empty($logement['agence_email'])): ?>
            <div class="form-actions" style="padding:0 18px 18px;">
              <a href="mailto:<?= e($logement['agence_email']) ?>?subject=<?= rawurlencode('Intérêt pour ' . $logement['titre']) ?>" class="btn btn-primary" style="width:100%;justify-content:center;">Contacter l'agence</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
