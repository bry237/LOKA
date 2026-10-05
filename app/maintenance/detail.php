<?php declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/MaintenanceController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header("Location: index.php");
    exit;
}

$controller = new MaintenanceController();
$intervention = $controller->find($agencyId, (int)$id);

if (!$intervention) {
    header("Location: index.php");
    exit;
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_comment') {
        $content = $_POST['contenu'] ?? '';
        if ($controller->addComment($agencyId, (int)$id, (int)$user['id_utilisateur'], $content)) {
            $successMessage = "Commentaire ajouté.";
        }
    } elseif ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if ($controller->updateStatus($agencyId, (int)$id, $status)) {
            $successMessage = "Statut mis à jour.";
            $intervention = $controller->find($agencyId, (int)$id);
        }
    } elseif ($action === 'close') {
        $data = [
            'cout_final' => $_POST['cout_final'] ?? '',
            'compte_rendu' => $_POST['compte_rendu'] ?? ''
        ];
        [$closeErrors, $closeId] = $controller->close($agencyId, (int)$id, $data);
        if (empty($closeErrors)) {
            $successMessage = "Intervention clôturée.";
            $intervention = $controller->find($agencyId, (int)$id);
        } else {
            $errors = $closeErrors;
        }
    }
}

$comments = $controller->comments($agencyId, (int)$id);

function getPrioriteBadge(string $priorite): string {
    return match ($priorite) {
        'LOW' => '<span class="badge badge-green">Faible</span>',
        'MEDIUM' => '<span class="badge badge-blue">Moyenne</span>',
        'HIGH' => '<span class="badge badge-warning">Haute</span>',
        'URGENT' => '<span class="badge badge-danger">Urgente</span>',
        default => '<span class="badge">Inconnue</span>'
    };
}

function getStatutBadge(string $statut): string {
    return match ($statut) {
        'OPEN' => '<span class="badge badge-gray">Ouverte</span>',
        'ASSIGNED' => '<span class="badge badge-blue">Assignée</span>',
        'IN_PROGRESS' => '<span class="badge badge-warning">En cours</span>',
        'WAITING' => '<span class="badge badge-warning">En attente</span>',
        'RESOLVED' => '<span class="badge badge-green">Résolue</span>',
        'CLOSED' => '<span class="badge badge-gray">Clôturée</span>',
        'CANCELLED' => '<span class="badge badge-danger">Annulée</span>',
        default => '<span class="badge">Inconnu</span>'
    };
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($intervention['titre'], ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
    <style>
        .detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 2rem; }
        .info-group { margin-bottom: 1rem; }
        .info-label { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem; }
        .info-value { font-weight: 500; }
        .comment { padding: 1rem; border-bottom: 1px solid var(--border-color); }
        .comment:last-child { border-bottom: none; }
        .comment-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .badge-green { background: #dcfce7; color: #166534; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-blue { background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-warning { background: #fef08a; color: #854d0e; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-danger { background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-gray { background: #f3f4f6; color: #374151; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge { display: inline-block; font-weight: 500; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'maintenance'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="index.php" class="btn btn-secondary" style="margin-right: 1rem;">&larr; Retour</a>
                <div>
                    <div class="eyebrow">Détail Intervention</div>
                    <h1><?= htmlspecialchars($intervention['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
            </div>
            <div class="topbar-right">
                <a href="modifier.php?id=<?= $intervention['id_intervention'] ?>" class="btn btn-secondary">Modifier</a>
            </div>
        </header>
        <main class="page">
            <?php if ($successMessage): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="detail-grid">
                <div>
                    <div class="card">
                        <h3>Informations</h3>
                        <div class="info-group">
                            <div class="info-label">Description</div>
                            <div class="info-value"><?= nl2br(htmlspecialchars((string)$intervention['description'], ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="info-group">
                                <div class="info-label">Bien</div>
                                <div class="info-value">
                                    <?php if ($intervention['bien_nom']): ?>
                                        <?= htmlspecialchars($intervention['bien_nom'] . ' (' . $intervention['bien_reference'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Locataire</div>
                                <div class="info-value">
                                    <?php if ($intervention['locataire_nom']): ?>
                                        <?= htmlspecialchars($intervention['locataire_prenom'] . ' ' . $intervention['locataire_nom'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Priorité</div>
                                <div class="info-value"><?= getPrioriteBadge($intervention['priorite']) ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Statut</div>
                                <div class="info-value"><?= getStatutBadge($intervention['statut']) ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Coût estimé</div>
                                <div class="info-value"><?= htmlspecialchars((string)$intervention['cout_estime'], ENT_QUOTES, 'UTF-8') ?> €</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Coût final</div>
                                <div class="info-value"><?= $intervention['cout_final'] !== null ? htmlspecialchars((string)$intervention['cout_final'], ENT_QUOTES, 'UTF-8') . ' €' : '-' ?></div>
                            </div>
                        </div>
                        <?php if ($intervention['compte_rendu']): ?>
                            <div class="info-group mt-3">
                                <div class="info-label">Compte rendu</div>
                                <div class="info-value"><?= nl2br(htmlspecialchars((string)$intervention['compte_rendu'], ENT_QUOTES, 'UTF-8')) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3>Commentaires</h3>
                        <div class="comments-list mb-3">
                            <?php if (empty($comments)): ?>
                                <p class="text-muted">Aucun commentaire.</p>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="comment">
                                        <div class="comment-header">
                                            <span><?= htmlspecialchars($c['utilisateur_prenom'] . ' ' . $c['utilisateur_nom'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div><?= nl2br(htmlspecialchars($c['contenu'], ENT_QUOTES, 'UTF-8')) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="action" value="add_comment">
                            <div class="form-group">
                                <textarea name="contenu" class="form-control" rows="3" placeholder="Ajouter un commentaire..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Commenter</button>
                        </form>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h3>Actions</h3>
                        <form method="post" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="action" value="update_status">
                            
                            <?php if ($intervention['statut'] === 'OPEN'): ?>
                                <button type="submit" name="status" value="ASSIGNED" class="btn btn-secondary w-100 mb-2">Assigner</button>
                            <?php endif; ?>
                            
                            <?php if (in_array($intervention['statut'], ['OPEN', 'ASSIGNED'])): ?>
                                <button type="submit" name="status" value="IN_PROGRESS" class="btn btn-primary w-100 mb-2">Commencer</button>
                            <?php endif; ?>
                            
                            <?php if (in_array($intervention['statut'], ['IN_PROGRESS', 'WAITING'])): ?>
                                <button type="submit" name="status" value="RESOLVED" class="btn btn-success w-100 mb-2">Marquer comme résolu</button>
                                <button type="submit" name="status" value="WAITING" class="btn btn-warning w-100 mb-2">Mettre en attente</button>
                            <?php endif; ?>
                            
                            <?php if ($intervention['statut'] !== 'CLOSED' && $intervention['statut'] !== 'CANCELLED'): ?>
                                <button type="submit" name="status" value="CANCELLED" class="btn btn-danger w-100 mb-2" onclick="return confirm('Voulez-vous vraiment annuler cette intervention ?');">Annuler</button>
                            <?php endif; ?>
                        </form>

                        <?php if ($intervention['statut'] === 'RESOLVED'): ?>
                            <hr>
                            <h4>Clôturer l'intervention</h4>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                                <input type="hidden" name="action" value="close">
                                <div class="form-group">
                                    <label class="form-label">Coût final (€)</label>
                                    <input type="number" step="0.01" name="cout_final" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Compte rendu *</label>
                                    <textarea name="compte_rendu" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Clôturer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
