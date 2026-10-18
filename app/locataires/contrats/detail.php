<?php

declare(strict_types=1);
require_once __DIR__ . '/ContratController.php';
$controller = new ContratController();
$locataire = $controller->getLocataire();
$prenom = (string) ($locataire['prenom'] ?? '');
$nom = (string) ($locataire['nom'] ?? '');
$initiales = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$contrat = $controller->getContrat($id);
if (!$contrat) { http_response_code(404); exit('Contrat introuvable ou accès non autorisé.'); }
function h(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function dateFr(?string $date): string { if (!$date) return '—'; $t = strtotime($date); return $t ? date('d/m/Y à H:i', $t) : '—'; }
$statut = (string)($contrat['statut_signature'] ?? 'EN_ATTENTE');
$messages = [
    'signature' => 'La signature simulée a été enregistrée.',
    'depot' => 'Votre PDF a été déposé. Il est en attente de vérification par l’agence.',
];
$success = $messages[(string)($_GET['success'] ?? '')] ?? null;
$error = isset($_GET['error']) ? (string)$_GET['error'] : null;
$bloque = in_array($statut, ['VALIDE'], true);
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Contrat <?= h($contrat['numero']) ?> - LOKA</title><link rel="stylesheet" href="../locataire.css"><link rel="stylesheet" href="contrats.css"></head><body><div class="layout contrats-layout">
<aside class="sidebar">
  <div class="logo">LOKA</div>
  <nav>
    <a href="/LOKA/app/locataires/index.php"><span class="nav-icon">⌂</span><span>Accueil</span></a>
    <a href="/LOKA/app/locataires/detail.php"><span class="nav-icon">👤</span><span>Mon profil</span></a>
    <a href="/LOKA/app/locataires/logement.php"><span class="nav-icon">🏠</span><span>Mon logement</span></a>
    <a href="/LOKA/app/locataires/recherche.php"><span class="nav-icon">🔎</span><span>Recherche</span></a>
    <a class="active" href="index.php"><span class="nav-icon">📄</span><span>Mon contrat</span></a>
    <a href="/LOKA/app/locataires/paiements/paiement.php"><span class="nav-icon">💳</span><span>Mes paiements</span></a>
    <a href="/LOKA/app/maintenance/index.php"><span class="nav-icon">🔧</span><span>Maintenance</span></a>
    <a href="/LOKA/app/locataires/quittances/index.php"><span class="nav-icon">🧾</span><span>Quittances</span></a>
    <a href="/LOKA/app/locataires/notifications/notification.php"><span class="nav-icon">🔔</span><span>Notifications</span></a>
  </nav>
  <div class="sidebar-bottom"><a href="/LOKA/app/authentification/deconnexion.php"><span class="nav-icon">↪</span><span>Déconnexion</span></a></div>
</aside>
<main class="main-content">
<header class="topbar"><div class="topbar-left"><h2>Détail du contrat</h2><p>Gérez votre contrat de location</p></div><div class="user-info"><div class="user-details"><strong><?= h($prenom . ' ' . $nom) ?></strong><span>Locataire</span></div><div class="avatar"><?= h($initiales ?: 'L') ?></div></div></header>
<div class="content contrats-content">
<a class="back" href="index.php">← Retour à mes contrats</a><div class="heading"><div><p class="eyebrow">CONTRAT DE LOCATION</p><h1>Contrat <?= h($contrat['numero']) ?></h1><p class="muted"><?= h($contrat['titre_bien'] ?: $contrat['reference_bien'] ?: 'Logement') ?></p></div><span class="badge <?= $statut === 'VALIDE' || $statut === 'SIGNE_SIMULATION' ? 'success' : 'pending' ?>"><?= h(match($statut) {'SIGNE_SIMULATION'=>'Signé (simulation)','DEPOSE'=>'PDF déposé · à vérifier','VALIDE'=>'Validé par l’agence','REFUSE'=>'À corriger',default=>'En attente de signature'}) ?></span></div>
<?php if ($success): ?><div class="alert success-alert"><?= h($success) ?></div><?php endif; ?><?php if ($error): ?><div class="alert error-alert"><?= h($error) ?></div><?php endif; ?>
<div class="detail-grid"><section class="panel"><h2>Informations du contrat</h2><dl class="details"><div><dt>Numéro</dt><dd><?= h($contrat['numero']) ?></dd></div><div><dt>Date de début</dt><dd><?= h(dateFr($contrat['date_debut'] ?? null)) ?></dd></div><div><dt>Date de fin</dt><dd><?= h(dateFr($contrat['date_fin'] ?? null)) ?></dd></div><div><dt>Loyer mensuel</dt><dd><?= number_format((float)$contrat['loyer'], 2, ',', ' ') ?> €</dd></div><div><dt>Charges mensuelles</dt><dd><?= number_format((float)$contrat['charges'], 2, ',', ' ') ?> €</dd></div><div><dt>Dépôt de garantie</dt><dd><?= number_format((float)$contrat['depot_garantie'], 2, ',', ' ') ?> €</dd></div></dl><div class="actions"><a class="button" href="telecharger.php?id=<?= $id ?>&type=original">Télécharger le contrat original</a><?php if (!empty($contrat['chemin_pdf_signe'])): ?><a class="button secondary" href="telecharger.php?id=<?= $id ?>&type=signe">Télécharger mon PDF déposé</a><?php endif; ?></div></section>
<section class="panel"><h2>Signature en ligne</h2><p class="muted">Cette fonction sert à démontrer le parcours utilisateur dans le projet scolaire. Elle n’utilise aucun prestataire et ne constitue pas une signature électronique juridiquement garantie.</p><?php if (!empty($contrat['nom_signataire'])): ?><div class="signature-record"><span class="record-label">Nom saisi</span><strong><?= h($contrat['nom_signataire']) ?></strong><span class="record-label">Date d’enregistrement</span><strong><?= h(dateFr($contrat['date_signature'] ?? null)) ?></strong><span class="record-label">Statut</span><strong><?= h($statut === 'VALIDE' ? 'Validé par l’agence' : 'Signé (simulation)') ?></strong></div><?php endif; ?>
<?php if (!$bloque && $statut !== 'DEPOSE'): ?><form method="post" action="action.php" class="form"><input type="hidden" name="csrf_token" value="<?= h($controller->token()) ?>"><input type="hidden" name="id_contrat" value="<?= $id ?>"><input type="hidden" name="action" value="signer"><label for="nom_signataire">Nom complet du signataire</label><input id="nom_signataire" name="nom_signataire" type="text" maxlength="255" required value="<?= h($contrat['nom_signataire'] ?? ($controller->getLocataire()['prenom'].' '.$controller->getLocataire()['nom'])) ?>"><label class="check"><input type="checkbox" name="consentement" value="1" required><span>Je confirme avoir pris connaissance du contrat et souhaite enregistrer cette signature de démonstration.</span></label><button class="button" type="submit">Confirmer la signature simulée</button></form><?php elseif ($statut === 'DEPOSE'): ?><p class="muted">Votre PDF a été déposé. Attendez la vérification de l’agence avant de recommencer.</p><?php else: ?><p class="muted">Le contrat a été validé par l’agence. La signature est verrouillée.</p><?php endif; ?></section>
<section class="panel upload-panel"><h2>Déposer un contrat signé</h2><p class="muted">Si vous avez signé le document hors de LOKA, vous pouvez transmettre ici le PDF signé. Format PDF uniquement, 10 Mo maximum.</p><?php if (!$bloque): ?><form method="post" action="action.php" enctype="multipart/form-data" class="form"><input type="hidden" name="csrf_token" value="<?= h($controller->token()) ?>"><input type="hidden" name="id_contrat" value="<?= $id ?>"><input type="hidden" name="action" value="deposer"><input type="hidden" name="MAX_FILE_SIZE" value="10485760"><label for="contrat_signe">Fichier PDF signé</label><input id="contrat_signe" name="contrat_signe" type="file" accept="application/pdf,.pdf" required><button class="button" type="submit">Envoyer le PDF signé</button></form><?php else: ?><p>Le contrat est validé. Le dépôt est fermé.</p><?php endif; ?><?php if (!empty($contrat['nom_fichier_signe'])): ?><div class="file-info"><strong>Dernier fichier envoyé</strong><span><?= h($contrat['nom_fichier_signe']) ?></span><span class="muted">Déposé le <?= h(dateFr($contrat['date_depot'] ?? null)) ?></span></div><?php endif; ?></section></div>
<div class="notice"><strong>À savoir :</strong> « Signé (simulation) » signifie que LOKA a enregistré une confirmation. « PDF déposé » signifie qu’un fichier a été transmis. Seule une vérification effectuée par l’agence peut faire passer le dossier à « Validé par l’agence » dans la gestion administrative.</div></div></main></div></body></html>
