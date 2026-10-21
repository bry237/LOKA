<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

try {
    $contrats = $controller->getContrats();
} catch (Throwable $e) {
    error_log('Erreur contrats propriétaire LOKA : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue lors du chargement de vos contrats.');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y', $timestamp) : '—';
}

function statutContrat(?string $statut): array
{
    return match ($statut) {
        'ACTIVE' => ['Actif', 'actif'],
        'DRAFT' => ['Brouillon', 'attente'],
        'RENEWED' => ['Renouvelé', 'actif'],
        'EXPIRED' => ['Expiré', 'inactif'],
        'TERMINATED' => ['Résilié', 'suspendu'],
        'ARCHIVED' => ['Archivé', 'inactif'],
        default => ['Non renseigné', 'inactif'],
    };
}

function statutSignature(?string $statut): array
{
    return match ($statut) {
        'SIGNE_SIMULATION' => ['Signé (simulation)', 'actif'],
        'DEPOSE' => ['PDF déposé · à vérifier', 'attente'],
        'VALIDE' => ['Contrat signé', 'actif'],
        'REFUSE' => ['Non signé / erreur', 'suspendu'],
        default => ['En attente de signature', 'attente'],
    };
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Mes contrats';
$activeNav = 'Mes contrats';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Mes contrats';
$greetingSubtitle = 'Consultez les contrats de location de vos biens et leur statut de signature.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (empty($contrats)): ?>
      <div class="panel">
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
          <h4>Aucun contrat pour le moment</h4>
          <p>Créez un contrat pour un de vos biens, ou attendez que votre agence en crée un.</p>
          <a href="ajouterContrat.php" class="btn btn-primary" style="margin-top:16px;">+ Ajouter un contrat</a>
        </div>
      </div>
    <?php else: ?>
      <div class="panel table-panel">
        <div class="panel-header">
          <div class="panel-title">Mes contrats</div>
          <span class="panel-sub" style="margin:0;"><?= count($contrats) ?> contrat(s)</span>
          <a href="ajouterContrat.php" class="btn btn-primary" style="margin-left:auto;">+ Ajouter un contrat</a>
        </div>
        <div class="document-list">
          <?php foreach ($contrats as $c): ?>
            <?php
            [$labelContrat, $toneContrat] = statutContrat($c['statut_contrat'] ?? null);
            [$labelSignature, $toneSignature] = statutSignature($c['statut_signature'] ?? null);
            ?>
            <div class="document-item">
              <div style="display:flex;gap:14px;min-width:0;">
                <div class="cell-avatar">PDF</div>
                <div class="document-main">
                  <div class="document-title">
                    Contrat <?= e($c['numero'] ?? '') ?>
                    <span class="status-badge <?= $toneContrat ?>"><?= e($labelContrat) ?></span>
                    <span class="status-badge <?= $toneSignature ?>"><?= e($labelSignature) ?></span>
                  </div>
                  <div class="document-sub"><?= e($c['titre_bien'] ?: $c['reference_bien'] ?: 'Bien') ?><?= $c['locataire_nom'] ? ' · Locataire : ' . e(trim(($c['locataire_prenom'] ?? '') . ' ' . $c['locataire_nom'])) : '' ?></div>
                  <div class="document-sub">
                    Début : <strong style="color:var(--heading);"><?= dateFr($c['date_debut'] ?? null) ?></strong>
                    &nbsp;·&nbsp; Fin : <strong style="color:var(--heading);"><?= dateFr($c['date_fin'] ?? null) ?></strong>
                    &nbsp;·&nbsp; Loyer : <strong style="color:var(--heading);"><?= number_format((float) ($c['loyer'] ?? 0), 2, ',', ' ') ?> €</strong>
                  </div>
                </div>
              </div>
              <div class="document-actions">
                <a class="btn btn-primary btn-sm" href="detailContrat.php?id=<?= (int) $c['id_contrat'] ?>">Voir le contrat</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
