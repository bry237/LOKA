<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

$biens = $controller->getMesBiensPourContrat();
$locataires = $controller->getLocatairesDeLAgence();

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$input = [
    'id_bien' => '',
    'id_locataire' => '',
    'date_debut' => date('Y-m-d'),
    'date_fin' => '',
    'loyer' => '',
    'charges' => '0',
    'depot_garantie' => '0',
    'frequence_paiement' => 'MONTHLY',
    'jour_echeance' => '5',
    'type_contrat' => 'HABITATION_VIDE',
];
$errors = [];
$idContratCree = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Votre session a expiré. Rechargez la page.';
    } else {
        $input = array_merge($input, $_POST);
        [$errors, $idContratCree] = $controller->creerContrat($input);
        if (empty($errors)) {
            header('Location: detailContrat.php?id=' . $idContratCree . '&success=created');
            exit;
        }
    }
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Ajouter un contrat';
$activeNav = 'Mes contrats';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Ajouter un contrat';
$greetingSubtitle = 'Créez un contrat de location pour un de vos biens et associez-y un locataire.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="contrat.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes contrats
    </a>

    <?php if (!empty($errors['general'])): ?>
      <div class="feedback error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <?php if (empty($biens)): ?>
      <div class="panel">
        <div class="empty-state" style="padding:48px 20px;">
          <h4>Vous n’avez aucun bien</h4>
          <p>Ajoutez d’abord un bien avant de pouvoir créer un contrat.</p>
          <a href="../biens.php" class="btn btn-primary" style="margin-top:16px;">Voir mes biens</a>
        </div>
      </div>
    <?php elseif (empty($locataires)): ?>
      <div class="panel">
        <div class="empty-state" style="padding:48px 20px;">
          <h4>Aucun locataire disponible</h4>
          <p>Votre agence n’a pour l’instant aucun locataire enregistré. Contactez-la pour qu’elle en ajoute un avant de créer un contrat.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="panel" style="max-width:760px;">
        <div class="panel-header"><div class="panel-title">Nouveau contrat</div></div>
        <form method="post" class="form-grid" style="padding:18px;">
          <input type="hidden" name="csrf_token" value="<?= e(Auth::csrfToken()) ?>">

          <div class="form-field">
            <label for="id_bien">Bien</label>
            <select id="id_bien" name="id_bien" required onchange="prefillBien(this)">
              <option value="">— Choisir un bien —</option>
              <?php foreach ($biens as $bien): ?>
                <option value="<?= (int) $bien['id_bien'] ?>"
                  data-loyer="<?= e((string) $bien['loyer']) ?>"
                  data-charges="<?= e((string) $bien['charges']) ?>"
                  data-caution="<?= e((string) $bien['caution']) ?>"
                  <?= (string) $input['id_bien'] === (string) $bien['id_bien'] ? 'selected' : '' ?>>
                  <?= e($bien['titre']) ?> (<?= e($bien['reference']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_bien'])): ?><span class="field-error"><?= e($errors['id_bien']) ?></span><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="id_locataire">Locataire</label>
            <select id="id_locataire" name="id_locataire" required>
              <option value="">— Choisir un locataire —</option>
              <?php foreach ($locataires as $locataire): ?>
                <option value="<?= (int) $locataire['id_locataire'] ?>" <?= (string) $input['id_locataire'] === (string) $locataire['id_locataire'] ? 'selected' : '' ?>>
                  <?= e($locataire['prenom'] . ' ' . $locataire['nom']) ?><?= $locataire['email'] ? ' — ' . e($locataire['email']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_locataire'])): ?><span class="field-error"><?= e($errors['id_locataire']) ?></span><?php endif; ?>
            <p class="cell-sub" style="margin:4px 0 0;">Seuls les locataires déjà enregistrés auprès de votre agence apparaissent ici.</p>
          </div>

          <div class="form-field">
            <label for="type_contrat">Type de contrat</label>
            <select id="type_contrat" name="type_contrat">
              <?php $typesContrat = ['HABITATION_VIDE' => 'Habitation (vide)', 'HABITATION_MEUBLE' => 'Habitation (meublé)', 'COMMERCIAL' => 'Commercial', 'PROFESSIONNEL' => 'Professionnel', 'SAISONNIER' => 'Saisonnier']; ?>
              <?php foreach ($typesContrat as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $input['type_contrat'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label for="frequence_paiement">Fréquence de paiement</label>
            <select id="frequence_paiement" name="frequence_paiement">
              <?php $frequences = ['MONTHLY' => 'Mensuelle', 'QUARTERLY' => 'Trimestrielle', 'YEARLY' => 'Annuelle']; ?>
              <?php foreach ($frequences as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $input['frequence_paiement'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label for="date_debut">Date de début</label>
            <input type="date" id="date_debut" name="date_debut" required value="<?= e((string) $input['date_debut']) ?>">
            <?php if (!empty($errors['date_debut'])): ?><span class="field-error"><?= e($errors['date_debut']) ?></span><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="date_fin">Date de fin (optionnel)</label>
            <input type="date" id="date_fin" name="date_fin" value="<?= e((string) $input['date_fin']) ?>">
            <?php if (!empty($errors['date_fin'])): ?><span class="field-error"><?= e($errors['date_fin']) ?></span><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="jour_echeance">Jour d’échéance</label>
            <input type="number" id="jour_echeance" name="jour_echeance" min="1" max="28" required value="<?= e((string) $input['jour_echeance']) ?>">
            <?php if (!empty($errors['jour_echeance'])): ?><span class="field-error"><?= e($errors['jour_echeance']) ?></span><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="loyer">Loyer mensuel (€)</label>
            <input type="number" id="loyer" name="loyer" min="0.01" step="0.01" required value="<?= e((string) $input['loyer']) ?>">
            <?php if (!empty($errors['loyer'])): ?><span class="field-error"><?= e($errors['loyer']) ?></span><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="charges">Charges (€)</label>
            <input type="number" id="charges" name="charges" min="0" step="0.01" value="<?= e((string) $input['charges']) ?>">
          </div>

          <div class="form-field">
            <label for="depot_garantie">Dépôt de garantie (€)</label>
            <input type="number" id="depot_garantie" name="depot_garantie" min="0" step="0.01" value="<?= e((string) $input['depot_garantie']) ?>">
          </div>

          <div class="form-actions full">
            <a href="contrat.php" class="btn">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer le contrat</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

<script>
function prefillBien(select) {
  var option = select.options[select.selectedIndex];
  if (!option || !option.value) return;
  var loyer = document.getElementById('loyer');
  var charges = document.getElementById('charges');
  var depot = document.getElementById('depot_garantie');
  if (loyer && !loyer.value) loyer.value = option.dataset.loyer || '';
  if (charges && (charges.value === '' || charges.value === '0')) charges.value = option.dataset.charges || '0';
  if (depot && (depot.value === '' || depot.value === '0')) depot.value = option.dataset.caution || '0';
}
</script>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
