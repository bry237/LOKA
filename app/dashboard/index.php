<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/DashboardModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier', 'Comptable');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence associée à ce compte.');
}

$propertyStats = DashboardModel::propertyStats($agencyId);
$occupancyRate = DashboardModel::occupancyRate($agencyId);
$contractStats = DashboardModel::contractStats($agencyId);
$tenantCount = DashboardModel::tenantCount($agencyId);
$ownerCount = DashboardModel::ownerCount($agencyId);
$overdue = DashboardModel::overduePayments($agencyId);
$monthlyRevenue = DashboardModel::monthlyRevenue($agencyId);
$openInterventions = DashboardModel::openInterventions($agencyId);
$recentProperties = DashboardModel::recentProperties($agencyId);
$recentInterventions = DashboardModel::recentInterventions($agencyId);

$statusLabels = ['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé'];
$statusClasses = ['CREATED' => 'created', 'AVAILABLE' => 'available', 'OCCUPIED' => 'occupied', 'MAINTENANCE' => 'maintenance', 'ARCHIVED' => 'archived'];
$priorityLabels = ['LOW' => 'Basse', 'MEDIUM' => 'Moyenne', 'HIGH' => 'Haute', 'URGENT' => 'Urgente'];
$priorityClasses = ['LOW' => 'available', 'MEDIUM' => 'created', 'HIGH' => 'maintenance', 'URGENT' => 'archived'];
$interventionStatusLabels = ['OPEN' => 'Ouverte', 'ASSIGNED' => 'Assignée', 'IN_PROGRESS' => 'En cours', 'WAITING' => 'En attente', 'RESOLVED' => 'Résolue', 'CLOSED' => 'Clôturée', 'CANCELLED' => 'Annulée'];
?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Tableau de bord | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'dashboard';
		require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Bienvenue, <?= htmlspecialchars((string) ($user['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
					<div class="topbar__title">Tableau de bord</div>
				</div>
				<div class="user-chip">
					<span class="avatar"><?= strtoupper(substr((string) ($user['prenom'] ?? 'U'), 0, 1)) ?></span>
					<span><?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
				</div>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title">Vue d'ensemble</h1>
						<p class="page__subtitle">Indicateurs clés de votre portefeuille immobilier.</p>
					</div>
				</div>

				<!-- KPI principaux -->
				<div class="stats">
					<div class="card stat">
						<div class="stat__top"><span>Biens gérés</span><span class="stat__icon">⌂</span></div>
						<div class="stat__value"><?= (int) $propertyStats['total'] ?></div>
					</div>
					<div class="card stat">
						<div class="stat__top"><span>Taux d'occupation</span><span class="stat__icon">📊</span></div>
						<div class="stat__value"><?= number_format($occupancyRate, 1, ',', '') ?> %</div>
					</div>
					<div class="card stat">
						<div class="stat__top"><span>Revenus du mois</span><span class="stat__icon">€</span></div>
						<div class="stat__value"><?= number_format($monthlyRevenue, 2, ',', ' ') ?> €</div>
					</div>
					<div class="card stat">
						<div class="stat__top"><span>Impayés</span><span class="stat__icon" style="<?= (int) $overdue['nombre'] > 0 ? 'background:var(--danger-light);color:var(--danger);' : '' ?>">⚠</span></div>
						<div class="stat__value" style="<?= (int) $overdue['nombre'] > 0 ? 'color:var(--danger);' : '' ?>"><?= (int) $overdue['nombre'] ?></div>
					</div>
				</div>

				<!-- KPI secondaires -->
				<div class="stats" style="margin-bottom:24px;">
					<div class="card stat">
						<div class="stat__top"><span>Contrats actifs</span><span class="stat__icon">▣</span></div>
						<div class="stat__value"><?= (int) $contractStats['actifs'] ?></div>
					</div>
					<div class="card stat">
						<div class="stat__top"><span>Locataires</span><span class="stat__icon">👤</span></div>
						<div class="stat__value"><?= $tenantCount ?></div>
					</div>
					<div class="card stat">
						<div class="stat__top"><span>Propriétaires</span><span class="stat__icon">♙</span></div>
						<div class="stat__value"><?= $ownerCount ?></div>
					</div>
					<div class="card stat">
						<div class="stat__top"><span>Interventions ouvertes</span><span class="stat__icon">⚒</span></div>
						<div class="stat__value"><?= $openInterventions ?></div>
					</div>
				</div>

				<!-- Détails par catégorie -->
				<div class="detail-grid">
					<!-- Derniers biens -->
					<section class="card">
						<div class="card__header">
							<h2 class="card__title">Derniers biens ajoutés</h2>
							<a class="button button--ghost" href="/LOKA/app/biens/index.php" style="min-height:32px;font-size:12px;">Voir tout</a>
						</div>
						<div class="table-wrap">
							<table>
								<thead>
									<tr>
										<th>Bien</th>
										<th>Type</th>
										<th>Ville</th>
										<th>Loyer</th>
										<th>Statut</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($recentProperties as $p): ?>
										<tr>
											<td>
												<div class="cell-title"><a href="/LOKA/app/biens/detail.php?id=<?= (int) $p['id_bien'] ?>"><?= htmlspecialchars($p['titre'], ENT_QUOTES, 'UTF-8') ?></a></div>
												<div class="cell-muted"><?= htmlspecialchars($p['reference'], ENT_QUOTES, 'UTF-8') ?></div>
											</td>
											<td><?= htmlspecialchars($p['type_bien'], ENT_QUOTES, 'UTF-8') ?></td>
											<td><?= htmlspecialchars($p['ville'], ENT_QUOTES, 'UTF-8') ?></td>
											<td><?= number_format((float) $p['loyer'], 2, ',', ' ') ?> €</td>
											<td><span class="badge badge--<?= $statusClasses[$p['statut']] ?? 'created' ?>"><?= $statusLabels[$p['statut']] ?? $p['statut'] ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php if ($recentProperties === []): ?>
							<div class="empty"><strong>Aucun bien enregistré</strong></div>
						<?php endif; ?>
					</section>

					<!-- Interventions en cours -->
					<section class="card">
						<div class="card__header">
							<h2 class="card__title">Interventions en cours</h2>
							<a class="button button--ghost" href="/LOKA/app/maintenance/index.php" style="min-height:32px;font-size:12px;">Voir tout</a>
						</div>
						<div class="table-wrap">
							<table>
								<thead>
									<tr>
										<th>Intervention</th>
										<th>Priorité</th>
										<th>Statut</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($recentInterventions as $i): ?>
										<tr>
											<td>
												<div class="cell-title"><a href="/LOKA/app/maintenance/detail.php?id=<?= (int) $i['id_intervention'] ?>"><?= htmlspecialchars($i['titre'], ENT_QUOTES, 'UTF-8') ?></a></div>
												<div class="cell-muted"><?= htmlspecialchars($i['bien_reference'], ENT_QUOTES, 'UTF-8') ?></div>
											</td>
											<td><span class="badge badge--<?= $priorityClasses[$i['priorite']] ?? 'created' ?>"><?= $priorityLabels[$i['priorite']] ?? $i['priorite'] ?></span></td>
											<td><span class="badge badge--created"><?= $interventionStatusLabels[$i['statut']] ?? $i['statut'] ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php if ($recentInterventions === []): ?>
							<div class="empty"><strong>Aucune intervention en cours</strong></div>
						<?php endif; ?>
					</section>
				</div>

				<!-- Accès rapides -->
				<section class="card" style="margin-top:24px;">
					<div class="card__header">
						<h2 class="card__title">Accès rapides</h2>
					</div>
					<div class="card__body">
						<div class="stats" style="margin:0;">
							<a href="/LOKA/app/biens/ajouter.php" class="button button--primary" style="width:100%;justify-content:center;">＋ Nouveau bien</a>
							<a href="/LOKA/app/contrats/ajouter.php" class="button button--secondary" style="width:100%;justify-content:center;">＋ Nouveau contrat</a>
							<a href="/LOKA/app/locataires/ajouter.php" class="button button--secondary" style="width:100%;justify-content:center;">＋ Nouveau locataire</a>
							<a href="/LOKA/app/maintenance/ajouter.php" class="button button--secondary" style="width:100%;justify-content:center;">＋ Nouvelle intervention</a>
						</div>
					</div>
				</section>
			</main>
		</div>
	</div>
</body>

</html>