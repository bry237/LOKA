<?php
declare(strict_types=1);

/**
 * Constantes de présentation (couleurs, icônes, libellés) partagées par equipe.php,
 * equipe-inviter.php et biens-assignation.php. Aucune logique métier ici : juste de quoi éviter
 * de dupliquer les mêmes tableaux SVG/couleurs dans chaque vue.
 */

/**
 * Rôles affichables pour une agence (cf. MonAgenceModel::TEAM_ROLE_IDS), chacun avec sa couleur
 * et son icône. 'eligible' marque les rôles opérationnels pouvant être responsables d'un bien :
 * un comptable ou un administrateur ne doit jamais recevoir un bien via l'affectation automatique.
 *
 * @return array<string, array{tone: string, short: string, icon: string, eligible: bool}>
 */
function teamRoleMeta(): array
{
	return [
		'Administrateur agence' => ['tone' => 'violet', 'short' => 'Administrateurs', 'icon' => 'shield', 'eligible' => false],
		'Gestionnaire immobilier' => ['tone' => 'teal', 'short' => 'Gestionnaires', 'icon' => 'home', 'eligible' => true],
		'Comptable' => ['tone' => 'amber', 'short' => 'Comptables', 'icon' => 'card', 'eligible' => false],
		'Technicien' => ['tone' => 'blue', 'short' => 'Techniciens', 'icon' => 'tool', 'eligible' => true],
	];
}

/** @return array<string, string> */
function teamIcons(): array
{
	return [
		'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/></svg>',
		'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>',
		'card' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>',
		'tool' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg>',
		'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
		'copy' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
		'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
		'wand' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 4 1.5 1.5M4 20l9-9M19 9l1.5 1.5M18.5 2.5 21.5 5.5M13 7l-1.5-1.5"/><path d="M8 20h.01M4 15h.01M20 15h.01"/></svg>',
		'hand' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12V5a1.5 1.5 0 0 1 3 0v5"/><path d="M12 10V4a1.5 1.5 0 0 1 3 0v6"/><path d="M15 9.5a1.5 1.5 0 0 1 3 0V12"/><path d="M6 11V7.5a1.5 1.5 0 0 1 3 0V12"/><path d="M6 11v2a7 7 0 0 0 7 7h1a6 6 0 0 0 6-6v-1"/></svg>',
		'alert' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
	];
}
