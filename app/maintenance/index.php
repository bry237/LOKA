<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$locataire = $controller->getCurrentLocataire();
$requests = $controller->getMyRequests();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function statutMaintenance(string $statut): string
{
    return match ($statut) {
        'OPEN' => 'Ouverte',
        'ASSIGNED' => 'Assignée',
        'IN_PROGRESS' => 'En cours',
        'WAITING' => 'En attente',
        'RESOLVED' => 'Résolue',
        'CLOSED' => 'Clôturée',
        'CANCELLED' => 'Annulée',
        default => $statut,
    };
}

function statutTone(string $statut): string
{
    return match ($statut) {
        'RESOLVED', 'CLOSED' => 'actif',
        'CANCELLED' => 'inactif',
        'WAITING' => 'suspendu',
        default => 'attente',
    };
}

function prioriteMaintenance(string $priorite): string
{
    return match ($priorite) {
        'LOW' => 'Faible',
        'MEDIUM' => 'Moyenne',
        'HIGH' => 'Élevée',
        'URGENT' => 'Urgente',
        default => $priorite,
    };
}

$prenom = e($locataire['prenom'] ?? '');
$nom = e($locataire['nom'] ?? '');

$pageTitle = 'Maintenance';
$activeNav = 'Maintenance';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Maintenance';
$greetingSubtitle = 'Suivez vos demandes de maintenance.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Mes demandes</h1>
        <p>Retrouvez ici toutes vos demandes de maintenance.</p>
      </div>
      <a href="ajouter.php" class="btn btn-primary">+ Signaler un problème</a>
    </div>

    <?php if (empty($requests)): ?>
      <div class="panel">
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></div>
          <h4>Aucune demande de maintenance</h4>
          <p>Signalez un problème dans votre logement pour créer une demande.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="panel table-panel">
        <div class="document-list">
          <?php foreach ($requests as $request): ?>
            <div class="document-item">
              <div class="document-main">
                <div class="document-title"><?= e($request['titre']) ?> <span class="status-badge <?= statutTone($request['statut']) ?>"><?= e(statutMaintenance($request['statut'])) ?></span></div>
                <div class="document-sub"><?= e($request['description']) ?></div>
                <div class="document-sub">
                  <?= e($request['logement_titre'] ?? 'Logement') ?><?php if (!empty($request['ville'])): ?> · <?= e($request['ville']) ?><?php endif; ?>
                  &nbsp;·&nbsp; Priorité : <strong style="color:var(--heading);"><?= e(prioriteMaintenance($request['priorite'])) ?></strong>
                </div>
              </div>
              <div class="document-actions">
                <a href="detail.php?id=<?= (int) $request['id_intervention'] ?>" class="btn btn-sm">Voir le détail</a>
                <a href="modifier.php?id=<?= (int) $request['id_intervention'] ?>" class="btn btn-sm">Modifier</a>
                <a href="supprimer.php?id=<?= (int) $request['id_intervention'] ?>" class="btn btn-sm btn-danger">Supprimer</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
