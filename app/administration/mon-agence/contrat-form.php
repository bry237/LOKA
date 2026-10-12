<?php
declare(strict_types=1);

/**
 * Formulaire partagé par contrat-ajouter.php et contrat-modifier.php.
 * Variables attendues : $errors, $values, $biens, $action, $isModal, $csrfToken, $idContrat (null en création), $cancelHref.
 */
?>
<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>

  <div class="form-grid">
    <div class="form-field">
      <label for="numero">Numéro de contrat</label>
      <input type="text" id="numero" name="numero" maxlength="100" value="<?= htmlspecialchars($values['numero'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['numero'])): ?><span class="field-error"><?= htmlspecialchars($errors['numero'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="id_bien">Bien</label>
      <select id="id_bien" name="id_bien">
        <option value="">— Choisir —</option>
        <?php foreach ($biens as $b): ?>
          <option value="<?= (int) $b['id_bien'] ?>"<?= (string) $values['id_bien'] === (string) $b['id_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($b['titre'] . ' (' . $b['reference'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['id_bien'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_bien'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="type_contrat">Type de contrat</label>
      <select id="type_contrat" name="type_contrat">
        <?php foreach (['HABITATION_VIDE' => 'Habitation vide', 'HABITATION_MEUBLE' => 'Habitation meublée', 'COMMERCIAL' => 'Commercial', 'PROFESSIONNEL' => 'Professionnel', 'SAISONNIER' => 'Saisonnier'] as $key => $label): ?>
          <option value="<?= $key ?>"<?= $values['type_contrat'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-field">
      <label for="statut">Statut</label>
      <select id="statut" name="statut">
        <?php foreach (['DRAFT' => 'Brouillon', 'ACTIVE' => 'Actif', 'EXPIRED' => 'Expiré', 'RENEWED' => 'Renouvelé', 'TERMINATED' => 'Résilié', 'ARCHIVED' => 'Archivé'] as $key => $label): ?>
          <option value="<?= $key ?>"<?= $values['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['statut'])): ?><span class="field-error"><?= htmlspecialchars($errors['statut'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>

    <div class="form-field">
      <label for="date_debut">Date de début</label>
      <input type="date" id="date_debut" name="date_debut" value="<?= htmlspecialchars($values['date_debut'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['date_debut'])): ?><span class="field-error"><?= htmlspecialchars($errors['date_debut'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="date_fin">Date de fin (optionnel)</label>
      <input type="date" id="date_fin" name="date_fin" value="<?= htmlspecialchars($values['date_fin'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['date_fin'])): ?><span class="field-error"><?= htmlspecialchars($errors['date_fin'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>

    <div class="form-field">
      <label for="loyer">Loyer mensuel (€)</label>
      <input type="number" step="0.01" min="0" id="loyer" name="loyer" value="<?= htmlspecialchars($values['loyer'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['loyer'])): ?><span class="field-error"><?= htmlspecialchars($errors['loyer'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="charges">Charges (€)</label>
      <input type="number" step="0.01" min="0" id="charges" name="charges" value="<?= htmlspecialchars($values['charges'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="depot_garantie">Dépôt de garantie (€)</label>
      <input type="number" step="0.01" min="0" id="depot_garantie" name="depot_garantie" value="<?= htmlspecialchars($values['depot_garantie'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-field">
      <label for="frequence_paiement">Fréquence de paiement</label>
      <select id="frequence_paiement" name="frequence_paiement">
        <?php foreach (['MONTHLY' => 'Mensuelle', 'QUARTERLY' => 'Trimestrielle', 'YEARLY' => 'Annuelle'] as $key => $label): ?>
          <option value="<?= $key ?>"<?= $values['frequence_paiement'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-field">
      <label for="jour_echeance">Jour d'échéance</label>
      <input type="number" min="1" max="31" id="jour_echeance" name="jour_echeance" value="<?= htmlspecialchars($values['jour_echeance'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $idContrat ? 'Enregistrer les modifications' : 'Créer le contrat' ?></button>
    <?php if ($isModal): ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>" data-modal-cancel>Annuler</a>
    <?php else: ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>">Annuler</a>
    <?php endif; ?>
  </div>
</form>
