<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

$idLocataire = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$locataire = $controller->getLocataireDetail($idLocataire);

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable ou accès non autorisé.');
}

$contrats = $controller->getContratsLocataire($idLocataire);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

function formatMontant($montant): string
{
    if ($montant === null || $montant === '') {
        return '—';
    }

    return number_format((float) $montant, 2, ',', ' ') . ' €';
}

function contratStatutBadge(?string $statut): array
{
    return match ($statut) {
        'ACTIVE' => ['Actif', 'actif'],
        'DRAFT' => ['Brouillon', 'attente'],
        'RENEWED' => ['Renouvelé', 'actif'],
        'EXPIRED' => ['Expiré', 'inactif'],
        'TERMINATED' => ['Résilié', 'suspendu'],
        default => ['Non renseigné', 'inactif'],
    };
}

function statutLocataireBadge(?string $statut): array
{
    return match ($statut) {
        'ACTIVE' => ['Actif', 'actif'],
        'INACTIVE' => ['Inactif', 'inactif'],
        'ARCHIVED' => ['Archivé', 'inactif'],
        default => ['Non renseigné', 'inactif'],
    };
}

$nomComplet = trim(($locataire['prenom'] ?? '') . ' ' . ($locataire['nom'] ?? '')) ?: 'Locataire';
$initiales = mb_strtoupper(mb_substr((string) $locataire['prenom'], 0, 1) . mb_substr((string) $locataire['nom'], 0, 1)) ?: 'L';
[$labelStatut, $toneStatut] = statutLocataireBadge($locataire['statut_locataire'] ?? null);

$contratActif = null;
foreach ($contrats as $contrat) {
    if (($contrat['statut_contrat'] ?? null) === 'ACTIVE') {
        $contratActif = $contrat;
        break;
    }
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = $nomComplet;
$activeNav = 'Mes locataires';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Détail du locataire';
$greetingSubtitle = 'Consultez les informations et les contrats du locataire.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes locataires
    </a>

    <div class="detail-header">
      <div class="detail-identity">
        <div class="detail-avatar"><?= e($initiales) ?></div>
        <div>
          <h1 class="detail-name"><?= e($nomComplet) ?></h1>
          <p class="detail-sub"><?= e($locataire['email'] ?? '—') ?></p>
        </div>
      </div>
      <span class="status-badge <?= $toneStatut ?>"><?= e($labelStatut) ?></span>
    </div>

    <div class="mini-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:18px;">
      <div class="mini-stat">
        <div class="num"><?= count($contrats) ?></div>
        <div class="lbl">Contrats</div>
      </div>
      <div class="mini-stat">
        <div class="num"><?= e($contratActif ? ($contratActif['reference_bien'] ?: $contratActif['titre_bien'] ?: '—') : '—') ?></div>
        <div class="lbl">Bien actuel</div>
      </div>
      <div class="mini-stat">
        <div class="num"><?= $contratActif ? formatMontant($contratActif['loyer']) : '—' ?></div>
        <div class="lbl">Loyer actuel</div>
      </div>
      <div class="mini-stat">
        <div class="num"><?= $contratActif ? formatDate($contratActif['date_entree'] ?: $contratActif['date_debut']) : '—' ?></div>
        <div class="lbl">Entrée actuelle</div>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations personnelles</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Prénom</span><span class="info-value"><?= e($locataire['prenom'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= e($locataire['nom'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Date de naissance</span><span class="info-value"><?= formatDate($locataire['date_naissance'] ?? null) ?></span></div>
            <div class="info-row"><span class="info-label">Profession</span><span class="info-value"><?= e($locataire['profession'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= e(trim(implode(' ', array_filter([$locataire['adresse'] ?? '', $locataire['code_postal'] ?? '', $locataire['ville'] ?? '', $locataire['pays'] ?? '']))) ?: '—') ?></span></div>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Coordonnées</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= e($locataire['email'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= e($locataire['telephone'] ?? '—') ?></span></div>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Situation financière</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Revenu mensuel</span><span class="info-value"><?= formatMontant($locataire['revenu_mensuel'] ?? null) ?></span></div>
            <div class="info-row"><span class="info-label">Fourchette de revenus</span><span class="info-value"><?= e($locataire['revenu_mensuel_fourchette'] ?? '—') ?></span></div>
          </div>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel table-panel">
          <div class="panel-header"><div class="panel-title">Contrats sur vos biens</div></div>
          <?php if (empty($contrats)): ?>
            <div class="empty-state" style="padding:40px 20px;">
              <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
              <h4>Aucun contrat</h4>
              <p>Aucun contrat n'est actuellement associé à ce locataire.</p>
            </div>
          <?php else: ?>
            <div class="document-list">
              <?php foreach ($contrats as $contrat): ?>
                <?php [$label, $tone] = contratStatutBadge($contrat['statut_contrat'] ?? null); ?>
                <div class="document-item">
                  <div style="display:flex;gap:14px;min-width:0;">
                    <div class="cell-avatar"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
                    <div class="document-main">
                      <div class="document-title">Contrat <?= e($contrat['numero_contrat'] ?: ('#' . $contrat['id_contrat'])) ?><span class="status-badge <?= $tone ?>" style="margin-left:8px;"><?= e($label) ?></span></div>
                      <div class="document-sub"><?= e($contrat['titre_bien'] ?: 'Bien immobilier') ?><?= $contrat['reference_bien'] ? ' · ' . e($contrat['reference_bien']) : '' ?></div>
                      <div class="document-sub">Du <strong style="color:var(--heading);"><?= formatDate($contrat['date_debut'] ?? null) ?></strong> au <strong style="color:var(--heading);"><?= formatDate($contrat['date_fin'] ?? null) ?></strong></div>
                      <div class="document-sub">Loyer : <strong style="color:var(--heading);"><?= formatMontant($contrat['loyer'] ?? null) ?></strong> &nbsp;·&nbsp; Charges : <strong style="color:var(--heading);"><?= formatMontant($contrat['charges'] ?? null) ?></strong> &nbsp;·&nbsp; Dépôt : <strong style="color:var(--heading);"><?= formatMontant($contrat['depot_garantie'] ?? null) ?></strong></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
