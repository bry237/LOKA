<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();
$dossier = $controller->getDossierVerification();
$interventions = $controller->getInterventions();

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

function interventionStatutBadge(string $statut): array
{
    return match ($statut) {
        'OPEN' => ['Ouverte', 'attente'],
        'ASSIGNED' => ['Assignée', 'attente'],
        'IN_PROGRESS' => ['En cours', 'attente'],
        'WAITING' => ['En attente', 'attente'],
        'RESOLVED' => ['Résolu', 'actif'],
        'CLOSED' => ['Résolu', 'actif'],
        'CANCELLED' => ['Annulée', 'inactif'],
        default => [$statut, 'inactif'],
    };
}

function prioriteBadge(string $priorite): array
{
    return match ($priorite) {
        'URGENT' => ['Urgente', 'suspendu'],
        'HIGH' => ['Haute', 'attente'],
        'MEDIUM' => ['Moyenne', 'attente'],
        default => ['Faible', 'inactif'],
    };
}

/*
 * Checklist de vérification du dossier : chaque ligne est verte ("ok") si tout va bien,
 * orange ("alerte") sinon — dérivée des mêmes données que le Dashboard et le Centre de
 * vérification, jamais d'état inventé.
 */
$checklist = [
    [
        'label' => 'Statut du contrat',
        'ok' => $dossier['contrats_actifs'] > 0,
        'detail' => $dossier['contrats_actifs'] > 0
            ? $dossier['contrats_actifs'] . ' contrat(s) actif(s)'
            : 'Aucun contrat actif actuellement',
    ],
    [
        'label' => 'Réception des paiements',
        'ok' => $dossier['echeances_en_retard'] === 0,
        'detail' => $dossier['echeances_en_retard'] === 0
            ? 'Toutes les échéances sont à jour'
            : $dossier['echeances_en_retard'] . ' échéance(s) en retard',
    ],
    [
        'label' => 'Validation des pièces justificatives',
        'ok' => $dossier['documents_en_attente'] === 0,
        'detail' => $dossier['documents_en_attente'] === 0
            ? 'Aucun document en attente'
            : $dossier['documents_en_attente'] . ' document(s) en attente de validation',
    ],
    [
        'label' => 'Quittances disponibles',
        'ok' => $dossier['quittances_disponibles'] > 0,
        'detail' => $dossier['quittances_disponibles'] > 0
            ? $dossier['quittances_disponibles'] . ' quittance(s) disponible(s)'
            : 'Aucune quittance émise pour le moment',
    ],
];

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Vérification & incidents';
$activeNav = 'Vérification';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Centre de vérification';
$greetingSubtitle = "Vue d'ensemble de votre dossier et suivi des incidents signalés.";
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

if (isset($_GET['success'])) {
    $successMessage = (string) $_GET['success'];
}

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!empty($successMessage)): ?>
      <div class="feedback success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">État du dossier</div></div>
      <div class="info-list">
        <?php foreach ($checklist as $item): ?>
          <div class="info-row">
            <span class="info-label"><?= e($item['label']) ?></span>
            <span class="info-value" style="display:flex;align-items:center;gap:8px;">
              <span class="status-badge <?= $item['ok'] ? 'actif' : 'attente' ?>"><?= $item['ok'] ? 'OK' : 'Alerte' ?></span>
              <span style="color:var(--text);font-weight:500;"><?= e($item['detail']) ?></span>
            </span>
          </div>
        <?php endforeach; ?>
        <?php if ($dossier['interventions_ouvertes'] > 0): ?>
          <div class="info-row">
            <span class="info-label">Incidents en cours</span>
            <span class="info-value"><span class="status-badge suspendu"><?= $dossier['interventions_ouvertes'] ?> ouvert(s)</span></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel table-panel" style="margin-top:18px;">
      <div class="panel-header">
        <div class="panel-title">Signalements &amp; incidents</div>
        <a class="btn btn-primary btn-sm" href="signaler.php" data-modal>+ Signaler un incident</a>
      </div>
      <?php if (empty($interventions)): ?>
        <div class="empty-state" style="padding:40px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg></div>
          <h4>Aucun incident signalé</h4>
          <p>Si vous constatez un problème technique sur l'un de vos biens (fuite, document manquant, etc.), signalez-le ici.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>TITRE</th><th>BIEN</th><th>PRIORITÉ</th><th>STATUT</th><th>CRÉÉ LE</th></tr></thead>
            <tbody>
              <?php foreach ($interventions as $i): ?>
                <?php
                [$labelStatut, $toneStatut] = interventionStatutBadge((string) $i['statut']);
                [$labelPriorite, $tonePriorite] = prioriteBadge((string) $i['priorite']);
                ?>
                <tr>
                  <td class="cell-title"><?= e($i['titre']) ?></td>
                  <td class="cell-sub"><?= e($i['bien_titre'] ?? '—') ?></td>
                  <td><span class="status-badge <?= $tonePriorite ?>"><?= e($labelPriorite) ?></span></td>
                  <td><span class="status-badge <?= $toneStatut ?>"><?= e($labelStatut) ?></span></td>
                  <td class="cell-sub"><?= dateFr($i['created_at'] ?? null) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
