<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/BienAdminController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? 0);
$bien = $id > 0 ? BienAdminController::detail($id) : null;

if (!$bien) {
	header('Location: index.php?error=' . urlencode('Bien introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$pageTitle = $bien['titre'];
$activeNav = 'Biens';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail du bien.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();
$redirectTarget = 'detail.php?id=' . $id;

// Page aussi chargée dans la modale globale (ex. depuis une future liste liée à une agence) :
// chemins absolus pour que les actions se résolvent quel que soit le dossier hôte.
$base = '/LOKA/app/administration/biens';

$statusLabels = ['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé'];
$statusTones = ['CREATED' => 'attente', 'AVAILABLE' => 'actif', 'OCCUPIED' => 'attente', 'MAINTENANCE' => 'suspendu', 'ARCHIVED' => 'inactif'];
$agenceStatusLabels = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente', 'SUSPENDED' => 'Suspendu'];

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="<?= $base ?>/index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux biens
    </a>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="detail-header">
      <div class="detail-identity">
        <div class="detail-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($bien['titre'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
        <div>
          <h1 class="detail-name">
            <?= htmlspecialchars($bien['titre'], ENT_QUOTES, 'UTF-8') ?>
            <span class="status-badge <?= $statusTones[$bien['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$bien['statut']] ?? $bien['statut'], ENT_QUOTES, 'UTF-8') ?></span>
          </h1>
          <p class="detail-sub"><?= htmlspecialchars($bien['reference'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($bien['agence_nom'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Type</span><span class="info-value"><?= htmlspecialchars($bien['type_bien'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= htmlspecialchars(trim($bien['ligne1'] . ' ' . ($bien['ligne2'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Ville</span><span class="info-value"><?= htmlspecialchars($bien['code_postal'] . ' ' . $bien['ville'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Surface</span><span class="info-value"><?= number_format((float) $bien['surface'], 0) ?> m²</span></div>
            <div class="info-row"><span class="info-label">Pièces</span><span class="info-value"><?= (int) $bien['nombre_pieces'] ?></span></div>
            <div class="info-row"><span class="info-label">Loyer</span><span class="info-value"><?= number_format((float) $bien['loyer'], 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Charges</span><span class="info-value"><?= number_format((float) $bien['charges'], 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Caution</span><span class="info-value"><?= number_format((float) $bien['caution'], 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Dernière mise à jour</span><span class="info-value"><?= (new DateTimeImmutable($bien['updated_at']))->format('d/m/Y H:i') ?></span></div>
          </div>
          <?php if (!empty($bien['description'])): ?>
            <div class="info-list" style="padding-top:0;">
              <div class="info-row" style="flex-direction:column;align-items:flex-start;gap:6px;">
                <span class="info-label">Description</span>
                <span class="info-value"><?= nl2br(htmlspecialchars($bien['description'], ENT_QUOTES, 'UTF-8')) ?></span>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Agence</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= htmlspecialchars($bien['agence_nom'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Statut agence</span><span class="info-value"><?= htmlspecialchars($agenceStatusLabels[$bien['agence_statut']] ?? $bien['agence_statut'], ENT_QUOTES, 'UTF-8') ?></span></div>
          </div>
          <div class="form-actions">
            <a class="btn" href="/LOKA/app/administration/agences/detail.php?id=<?= (int) $bien['id_agence'] ?>" data-modal>Voir l'agence</a>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header"><div class="panel-title">Supervision</div></div>
          <form method="post" action="<?= $base ?>/changer-statut.php" style="padding:18px;display:flex;flex-direction:column;gap:12px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int) $bien['id_bien'] ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
            <div class="form-field">
              <label for="statut">Nouveau statut</label>
              <select id="statut" class="filter-select" name="statut" required>
                <?php foreach ($statusLabels as $key => $label): ?>
                  <option value="<?= $key ?>"<?= $bien['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-field">
              <label for="motif">Motif (optionnel)</label>
              <textarea id="motif" name="motif" rows="2" placeholder="Raison du changement, consignée dans le journal d'audit…"></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary" onclick="return confirm('Confirmer ce changement de statut ?');">Mettre à jour le statut</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
