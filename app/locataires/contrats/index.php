<?php

declare(strict_types=1);

require_once __DIR__ . '/ContratController.php';

$controller = new ContratController();
$locataire = $controller->getLocataire();

$prenom = (string) ($locataire['prenom'] ?? '');
$nom = (string) ($locataire['nom'] ?? '');

try {
    $contrats = $controller->getContrats();
} catch (Throwable $e) {
    error_log('Erreur contrats LOKA : ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
    http_response_code(500);
    echo '<h2>Erreur de chargement des contrats</h2><pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}

function h(?string $value): string
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

function statutSignature(?string $statut): array
{
    return match ($statut) {
        'SIGNE_SIMULATION' => ['Signé (simulation)', 'actif'],
        'DEPOSE' => ['En attente de vérification', 'attente'],
        'VALIDE' => ['Validé par l’agence', 'actif'],
        'REFUSE' => ['À corriger', 'suspendu'],
        default => ['En attente de signature', 'attente'],
    };
}

$pageTitle = 'Mes contrats';
$activeNav = 'Mon contrat';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Mes contrats';
$greetingSubtitle = 'Consultez vos contrats, simulez votre signature et déposez un PDF signé.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'L';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">'
    . '<link rel="stylesheet" href="/LOKA/app/locataires/ops.css?v=' . filemtime(dirname(__DIR__) . '/ops.css') . '">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (empty($contrats)): ?>
      <div class="panel">
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
          <h4>Aucun contrat pour le moment</h4>
          <p>Lorsqu'un contrat sera mis à votre disposition par l'agence, vous pourrez le consulter, le télécharger, simuler sa signature ou déposer le PDF signé depuis cette page.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="panel table-panel">
        <div class="panel-header">
          <div class="panel-title">Mes contrats</div>
          <span class="panel-sub" style="margin:0;"><?= count($contrats) ?> contrat(s)</span>
        </div>
        <div class="document-list">
          <?php foreach ($contrats as $c): ?>
            <?php [$label, $tone] = statutSignature($c['statut_signature'] ?? null); ?>
            <div class="document-item">
              <div style="display:flex;gap:14px;min-width:0;">
                <div class="cell-avatar">PDF</div>
                <div class="document-main">
                  <div class="document-title">
                    Contrat <?= h($c['numero'] ?? '') ?>
                    <span class="status-badge <?= h($tone) ?>"><?= h($label) ?></span>
                  </div>
                  <div class="document-sub"><?= h($c['titre_bien'] ?: $c['reference_bien'] ?: 'Logement') ?></div>
                  <div class="document-sub">
                    Début : <strong style="color:var(--heading);"><?= dateFr($c['date_debut'] ?? null) ?></strong>
                    &nbsp;·&nbsp; Fin : <strong style="color:var(--heading);"><?= dateFr($c['date_fin'] ?? null) ?></strong>
                    &nbsp;·&nbsp; Loyer : <strong style="color:var(--heading);"><?= number_format((float) ($c['loyer'] ?? 0), 2, ',', ' ') ?> €</strong>
                    &nbsp;·&nbsp; Charges : <strong style="color:var(--heading);"><?= number_format((float) ($c['charges'] ?? 0), 2, ',', ' ') ?> €</strong>
                  </div>
                </div>
              </div>
              <div class="document-actions">
                <a class="btn btn-primary btn-sm" href="detail.php?id=<?= (int) $c['id_contrat'] ?>">Voir le contrat</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
