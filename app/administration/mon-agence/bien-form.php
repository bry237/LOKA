<?php
declare(strict_types=1);

/**
 * Formulaire partagé par bien-ajouter.php et bien-modifier.php.
 * Variables attendues : $errors, $values, $types, $addresses, $buildings, $action, $isModal,
 * $csrfToken, $idBien (null en création), $cancelHref.
 */
?>
<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>

  <div class="form-grid">
    <div class="form-field">
      <label for="reference">Référence</label>
      <input type="text" id="reference" name="reference" maxlength="100" value="<?= htmlspecialchars($values['reference'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['reference'])): ?><span class="field-error"><?= htmlspecialchars($errors['reference'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="titre">Titre</label>
      <input type="text" id="titre" name="titre" maxlength="200" value="<?= htmlspecialchars($values['titre'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['titre'])): ?><span class="field-error"><?= htmlspecialchars($errors['titre'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="id_type_bien">Type de bien</label>
      <select id="id_type_bien" name="id_type_bien">
        <option value="">— Choisir —</option>
        <?php foreach ($types as $type): ?>
          <option value="<?= (int) $type['id_type_bien'] ?>"<?= (string) $values['id_type_bien'] === (string) $type['id_type_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['id_type_bien'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_type_bien'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>

    <div class="form-field">
      <label for="id_adresse">Adresse</label>
      <select id="id_adresse" name="id_adresse" onchange="document.querySelectorAll('[data-new-address]').forEach(g => g.hidden = this.value !== '__new__');">
        <option value="">— Choisir —</option>
        <?php foreach ($addresses as $address): ?>
          <option value="<?= (int) $address['id_adresse'] ?>"<?= (string) $values['id_adresse'] === (string) $address['id_adresse'] ? ' selected' : '' ?>><?= htmlspecialchars($address['ligne1'] . ', ' . $address['ville'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
        <option value="__new__"<?= $values['id_adresse'] === '__new__' ? ' selected' : '' ?>>+ Nouvelle adresse…</option>
      </select>
      <?php if (!empty($errors['id_adresse'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_adresse'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field" data-new-address<?= $values['id_adresse'] !== '__new__' ? ' hidden' : '' ?>>
      <label for="nouvelle_ligne1">Nouvelle adresse — ligne 1</label>
      <input type="text" id="nouvelle_ligne1" name="nouvelle_ligne1" value="<?= htmlspecialchars($values['nouvelle_ligne1'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field" data-new-address<?= $values['id_adresse'] !== '__new__' ? ' hidden' : '' ?>>
      <label for="nouvelle_ville">Nouvelle adresse — ville</label>
      <input type="text" id="nouvelle_ville" name="nouvelle_ville" value="<?= htmlspecialchars($values['nouvelle_ville'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field" data-new-address<?= $values['id_adresse'] !== '__new__' ? ' hidden' : '' ?>>
      <label for="nouveau_code_postal">Nouvelle adresse — code postal</label>
      <input type="text" id="nouveau_code_postal" name="nouveau_code_postal" value="<?= htmlspecialchars($values['nouveau_code_postal'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-field">
      <label for="id_immeuble">Immeuble (optionnel)</label>
      <select id="id_immeuble" name="id_immeuble">
        <option value="">Aucun immeuble</option>
        <?php foreach ($buildings as $building): ?>
          <option value="<?= (int) $building['id_immeuble'] ?>"<?= (string) $values['id_immeuble'] === (string) $building['id_immeuble'] ? ' selected' : '' ?>><?= htmlspecialchars($building['nom'] ?: 'Immeuble', ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-field">
      <label for="etage">Étage</label>
      <input type="number" min="0" id="etage" name="etage" value="<?= htmlspecialchars($values['etage'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-field">
      <label for="surface">Surface (m²)</label>
      <input type="number" step="0.01" min="0" id="surface" name="surface" value="<?= htmlspecialchars($values['surface'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['surface'])): ?><span class="field-error"><?= htmlspecialchars($errors['surface'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="nombre_pieces">Nombre de pièces</label>
      <input type="number" min="0" id="nombre_pieces" name="nombre_pieces" value="<?= htmlspecialchars($values['nombre_pieces'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="meuble">Meublé</label>
      <select id="meuble" name="meuble">
        <option value="0"<?= $values['meuble'] !== '1' ? ' selected' : '' ?>>Non</option>
        <option value="1"<?= $values['meuble'] === '1' ? ' selected' : '' ?>>Oui</option>
      </select>
    </div>

    <div class="form-field">
      <label for="date_disponibilite">Date de disponibilité</label>
      <input type="date" id="date_disponibilite" name="date_disponibilite" value="<?= htmlspecialchars($values['date_disponibilite'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="dpe_classe">Classe DPE</label>
      <select id="dpe_classe" name="dpe_classe">
        <option value="">Non renseigné</option>
        <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $classe): ?>
          <option value="<?= $classe ?>"<?= $values['dpe_classe'] === $classe ? ' selected' : '' ?>><?= $classe ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-field">
      <label for="ges_classe">Classe GES</label>
      <select id="ges_classe" name="ges_classe">
        <option value="">Non renseigné</option>
        <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $classe): ?>
          <option value="<?= $classe ?>"<?= $values['ges_classe'] === $classe ? ' selected' : '' ?>><?= $classe ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-field">
      <label for="loyer">Loyer mensuel (€)</label>
      <input type="number" step="0.01" min="0" id="loyer" name="loyer" value="<?= htmlspecialchars($values['loyer'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="charges">Charges (€)</label>
      <input type="number" step="0.01" min="0" id="charges" name="charges" value="<?= htmlspecialchars($values['charges'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="caution">Caution (€)</label>
      <input type="number" step="0.01" min="0" id="caution" name="caution" value="<?= htmlspecialchars($values['caution'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-field">
      <label for="statut">Statut</label>
      <select id="statut" name="statut">
        <?php foreach (['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance'] as $key => $label): ?>
          <option value="<?= $key ?>"<?= $values['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['statut'])): ?><span class="field-error"><?= htmlspecialchars($errors['statut'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field span-2">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="3"><?= htmlspecialchars($values['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $idBien ? 'Enregistrer les modifications' : 'Créer le bien' ?></button>
    <?php if ($isModal): ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>" data-modal-cancel>Annuler</a>
    <?php else: ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>">Annuler</a>
    <?php endif; ?>
  </div>
</form>
