<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AgenceController.php';
require_once dirname(__DIR__) . '/abonnements/AbonnementModel.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? 0);
$detail = $id > 0 ? AgenceController::detail($id) : null;

if (!$detail) {
	header('Location: index.php?error=' . urlencode('Agence introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$agence = $detail['agence'];
$admin = $detail['admin'];
$stats = $detail['stats'];
$subscription = $detail['subscription'];
$history = $detail['history'];

$pageTitle = $agence['nom'];
$activeNav = 'Agences';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "Détail de l'agence.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="agences.css">';

$csrfToken = Auth::csrfToken();
$redirectTarget = 'detail.php?id=' . $id;

// Chemins absolus : cette page est aussi chargée dans la modale globale depuis le widget
// "Agences récentes" du Dashboard (app/administration/dashboard/), un dossier différent du
// sien — des liens/actions relatifs ("modifier.php", "valider-document.php"...) se résolvent
// alors contre l'URL de la page hôte (dashboard/) et pointent vers des fichiers inexistants.
$base = '/LOKA/app/administration/agences';

$statusLabels = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente', 'SUSPENDED' => 'Suspendu'];
$statusTones = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'SUSPENDED' => 'suspendu'];
$subStatusLabels = ['ACTIVE' => 'Actif', 'EXPIRED' => 'Expiré', 'CANCELLED' => 'Annulé'];
$subStatusTones = ['ACTIVE' => 'actif', 'EXPIRED' => 'suspendu', 'CANCELLED' => 'inactif'];
$availablePlans = AbonnementModel::activePlans();

$documents = $detail['documents'] ?? [];
$docTypeLabels = ['PIECE_IDENTITE' => "Pièce d'identité du responsable", 'JUSTIFICATIF_IMMATRICULATION' => "Justificatif d'immatriculation", 'AUTRE' => 'Autre document'];
$docStatusLabels = ['EN_ATTENTE' => 'En attente', 'VALIDE' => 'Validé', 'REJETE' => 'Rejeté'];
$docStatusTones = ['EN_ATTENTE' => 'attente', 'VALIDE' => 'actif', 'REJETE' => 'suspendu'];

$nextStatus = $agence['statut'] === 'ACTIVE' ? 'SUSPENDED' : 'ACTIVE';
$actionLabel = match ($agence['statut']) {
	'ACTIVE' => 'Suspendre',
	'SUSPENDED' => 'Réactiver',
	default => 'Activer',
};
$actionClass = $agence['statut'] === 'ACTIVE' ? 'btn-danger' : 'btn-success';

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="<?= $base ?>/index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux agences
    </a>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="detail-header">
      <div class="detail-identity">
        <div class="detail-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($agence['nom'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
        <div>
          <h1 class="detail-name">
            <?= htmlspecialchars($agence['nom'], ENT_QUOTES, 'UTF-8') ?>
            <span class="status-badge <?= $statusTones[$agence['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$agence['statut']] ?? $agence['statut'], ENT_QUOTES, 'UTF-8') ?></span>
          </h1>
          <p class="detail-sub">Inscrite le <?= (new DateTimeImmutable($agence['created_at']))->format('d/m/Y') ?><?= $agence['ville'] ? ' · ' . htmlspecialchars($agence['ville'], ENT_QUOTES, 'UTF-8') : '' ?></p>
        </div>
      </div>
      <div class="detail-actions">
        <a class="btn" href="<?= $base ?>/modifier.php?id=<?= (int) $agence['id_agence'] ?>" data-modal>Modifier</a>
        <form class="inline-form" method="post" action="<?= $base ?>/changer-statut.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id" value="<?= (int) $agence['id_agence'] ?>">
          <input type="hidden" name="statut" value="<?= $nextStatus ?>">
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="btn <?= $actionClass ?>"<?= $agence['statut'] === 'ACTIVE' ? ' onclick="return confirm(\'Suspendre cette agence ?\');"' : '' ?>><?= $actionLabel ?></button>
        </form>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($agence['email'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= htmlspecialchars($agence['telephone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= htmlspecialchars($agence['adresse'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Ville</span><span class="info-value"><?= htmlspecialchars($agence['ville'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Code postal</span><span class="info-value"><?= htmlspecialchars($agence['code_postal'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Pays</span><span class="info-value"><?= htmlspecialchars($agence['pays'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Dernière mise à jour</span><span class="info-value"><?= (new DateTimeImmutable($agence['updated_at']))->format('d/m/Y H:i') ?></span></div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Historique des abonnements</div>
            <form class="inline-form" method="post" action="<?= $base ?>/changer-plan.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id" value="<?= (int) $agence['id_agence'] ?>">
              <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8') ?>">
              <select name="id_abonnement" class="filter-select" style="margin-right:6px;">
                <?php foreach ($availablePlans as $plan): ?>
                  <option value="<?= (int) $plan['id_abonnement'] ?>"<?= ($subscription['plan_nom'] ?? null) === $plan['nom'] ? ' selected' : '' ?>><?= htmlspecialchars($plan['nom'], ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float) $plan['prix_mensuel'], 2, ',', ' ') ?> €)</option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm">Changer de plan</button>
            </form>
          </div>
          <?php if (!$history): ?>
            <p class="empty-note">Aucun abonnement souscrit pour le moment.</p>
          <?php else: ?>
            <div class="table-scroll">
              <table>
                <thead>
                  <tr><th>FORMULE</th><th>PRIX / MOIS</th><th>DÉBUT</th><th>FIN</th><th>STATUT</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($history as $h): ?>
                    <tr>
                      <td><?= htmlspecialchars($h['plan_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= number_format((float) $h['prix_mensuel'], 2, ',', ' ') ?> €</td>
                      <td><?= (new DateTimeImmutable($h['date_debut']))->format('d/m/Y') ?></td>
                      <td><?= $h['date_fin'] ? (new DateTimeImmutable($h['date_fin']))->format('d/m/Y') : '—' ?></td>
                      <td><span class="status-badge <?= $subStatusTones[$h['statut']] ?? '' ?>"><?= htmlspecialchars($subStatusLabels[$h['statut']] ?? $h['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Administrateur</div></div>
          <?php if (!$admin): ?>
            <p class="empty-note">Aucun administrateur associé à cette agence.</p>
          <?php else: ?>
            <div class="info-list">
              <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom'], ENT_QUOTES, 'UTF-8') ?></span></div>
              <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?></span></div>
              <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= htmlspecialchars($admin['telephone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
              <div class="info-row"><span class="info-label">Vérification</span><span class="status-badge <?= $admin['telephone_verifie'] ? 'actif' : 'attente' ?>"><?= $admin['telephone_verifie'] ? 'Téléphone vérifié' : 'Téléphone non vérifié' ?></span></div>
              <div class="info-row"><span class="info-label">Statut</span><span class="status-badge <?= $statusTones[$admin['statut']] ?? ($admin['statut'] === 'ACTIVE' ? 'actif' : 'suspendu') ?>"><?= $admin['statut'] === 'ACTIVE' ? 'Actif' : $admin['statut'] ?></span></div>
              <div class="info-row"><span class="info-label">Dernière connexion</span><span class="info-value"><?= $admin['derniere_connexion'] ? (new DateTimeImmutable($admin['derniere_connexion']))->format('d/m/Y H:i') : 'Jamais connecté' ?></span></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="panel">
          <div class="panel-header"><div class="panel-title">Aperçu</div></div>
          <div class="mini-stats">
            <div class="mini-stat">
              <div class="num"><?= $stats['utilisateurs'] ?></div>
              <div class="lbl">Utilisateurs</div>
            </div>
            <div class="mini-stat">
              <div class="num"><?= $stats['biens'] ?></div>
              <div class="lbl">Biens</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Documents de vérification</div></div>
      <?php if (!$documents): ?>
        <p class="empty-note">Aucun document soumis.</p>
      <?php else: ?>
        <div class="document-list">
          <?php foreach ($documents as $doc): ?>
            <div class="document-item">
              <div class="document-main">
                <div class="document-title">
                  <?= htmlspecialchars($docTypeLabels[$doc['type']] ?? $doc['type'], ENT_QUOTES, 'UTF-8') ?>
                  <span class="status-badge <?= $docStatusTones[$doc['statut_verification']] ?? '' ?>"><?= htmlspecialchars($docStatusLabels[$doc['statut_verification']] ?? $doc['statut_verification'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="document-sub"><?= htmlspecialchars($doc['nom_original'], ENT_QUOTES, 'UTF-8') ?> · <?= (new DateTimeImmutable($doc['created_at']))->format('d/m/Y H:i') ?></div>
                <?php if ($doc['analyse_ia_resume']): ?>
                  <p class="document-ia-summary"><?= htmlspecialchars($doc['analyse_ia_resume'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if (!empty($doc['analyse_ia_alertes'])): ?>
                  <ul class="document-ia-alerts">
                    <?php foreach ($doc['analyse_ia_alertes'] as $alerte): ?>
                      <li><?= htmlspecialchars($alerte, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
                <?php if ($doc['statut_verification'] === 'REJETE' && $doc['motif_rejet']): ?>
                  <p class="document-ia-summary">Motif du rejet : <?= htmlspecialchars($doc['motif_rejet'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
              </div>
              <div class="document-actions">
                <a class="btn btn-sm" href="<?= $base ?>/document-telecharger.php?id=<?= (int) $doc['id_document_agence'] ?>" target="_blank" rel="noopener">Télécharger</a>
                <?php if ($doc['statut_verification'] === 'EN_ATTENTE'): ?>
                  <form class="inline-form" method="post" action="<?= $base ?>/valider-document.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $doc['id_document_agence'] ?>">
                    <input type="hidden" name="agence_id" value="<?= (int) $agence['id_agence'] ?>">
                    <input type="hidden" name="statut" value="VALIDE">
                    <button type="submit" class="btn btn-sm btn-success">Valider</button>
                  </form>
                  <form class="inline-form" method="post" action="<?= $base ?>/valider-document.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $doc['id_document_agence'] ?>">
                    <input type="hidden" name="agence_id" value="<?= (int) $agence['id_agence'] ?>">
                    <input type="hidden" name="statut" value="REJETE">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Rejeter ce document ?');">Rejeter</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
