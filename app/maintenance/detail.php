<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$idIntervention = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idIntervention <= 0) {
    header('Location: index.php');
    exit;
}

$intervention = $controller->getRequest($idIntervention);

if (!$intervention) {
    header('Location: index.php');
    exit;
}

$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    header('Location: index.php');
    exit;
}

$comments = $controller->getComments($idIntervention);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$statuts = ['OPEN' => 'Ouverte', 'IN_PROGRESS' => 'En cours', 'DONE' => 'Terminée', 'CLOSED' => 'Clôturée', 'CANCELLED' => 'Annulée'];
$priorites = ['LOW' => 'Faible', 'MEDIUM' => 'Moyenne', 'HIGH' => 'Haute', 'URGENT' => 'Urgente'];
$statutTones = ['OPEN' => 'attente', 'IN_PROGRESS' => 'attente', 'DONE' => 'actif', 'CLOSED' => 'actif', 'CANCELLED' => 'inactif'];

$statut = $statuts[$intervention['statut']] ?? $intervention['statut'];
$priorite = $priorites[$intervention['priorite']] ?? $intervention['priorite'];
$statutTone = $statutTones[$intervention['statut']] ?? 'attente';

$pageTitle = 'Détail de la demande';
$activeNav = 'Maintenance';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Maintenance';
$greetingSubtitle = 'Consultez le détail de votre demande d\'intervention.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes demandes
    </a>

    <div class="detail-header">
      <div class="detail-identity">
        <div>
          <p class="panel-sub" style="margin:0 0 2px;">DEMANDE DE MAINTENANCE</p>
          <h1 class="detail-name"><?= e($intervention['titre']) ?></h1>
          <p class="detail-sub">Demande n°<?= (int) $intervention['id_intervention'] ?></p>
        </div>
      </div>
      <span class="status-badge <?= $statutTone ?>"><?= e($statut) ?></span>
    </div>

    <div class="panel" style="margin-bottom:18px;">
      <div class="mini-stats cols-4" style="padding:18px;">
        <div class="mini-stat"><div class="num" style="font-size:14px;"><?= e($priorite) ?></div><div class="lbl">Priorité</div></div>
        <div class="mini-stat"><div class="num" style="font-size:14px;"><?= dateFr($intervention['created_at']) ?></div><div class="lbl">Date de création</div></div>
        <div class="mini-stat"><div class="num" style="font-size:14px;"><?= dateFr($intervention['date_debut']) ?></div><div class="lbl">Date de début</div></div>
        <div class="mini-stat"><div class="num" style="font-size:14px;"><?= dateFr($intervention['date_fin']) ?></div><div class="lbl">Date de fin</div></div>
      </div>

      <div class="panel-header"><div class="panel-title">Logement concerné</div></div>
      <div class="info-list">
        <div class="info-row"><span class="info-label">Logement</span><span class="info-value"><?= e($intervention['logement_titre']) ?></span></div>
        <div class="info-row"><span class="info-label">Référence</span><span class="info-value"><?= e($intervention['reference']) ?></span></div>
        <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= e($intervention['ligne1']) ?><?php if (!empty($intervention['ligne2'])): ?>, <?= e($intervention['ligne2']) ?><?php endif; ?>, <?= e($intervention['code_postal']) ?> <?= e($intervention['ville']) ?></span></div>
      </div>

      <div class="panel-header"><div class="panel-title">Description</div></div>
      <p style="padding:0 18px 18px;font-size:13px;color:var(--text);line-height:1.6;"><?= nl2br(e($intervention['description'])) ?></p>

      <?php if (!empty($intervention['compte_rendu'])): ?>
        <div class="panel-header"><div class="panel-title">Compte rendu</div></div>
        <p style="padding:0 18px 18px;font-size:13px;color:var(--text);line-height:1.6;"><?= nl2br(e($intervention['compte_rendu'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <div class="panel-title">Commentaires</div>
          <p class="panel-sub">Retrouvez ici les informations échangées concernant cette intervention.</p>
        </div>
      </div>
      <?php if (empty($comments)): ?>
        <div class="empty-note">Aucun échange n'a encore été ajouté à cette demande.</div>
      <?php else: ?>
        <div class="info-list">
          <?php foreach ($comments as $comment): ?>
            <div class="info-row" style="display:block;">
              <div style="display:flex;justify-content:space-between;gap:10px;">
                <strong style="color:var(--heading);font-size:13px;"><?= e(trim(($comment['prenom'] ?? '') . ' ' . ($comment['nom'] ?? ''))) ?></strong>
                <span style="font-size:11.5px;color:#8AA0A0;"><?= dateFr($comment['created_at']) ?></span>
              </div>
              <div style="margin-top:4px;font-size:13px;color:var(--text);"><?= nl2br(e($comment['contenu'])) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
