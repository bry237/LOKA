<?php
declare(strict_types=1);

/**
 * Identité de l'agence du membre connecté (agence, badges de compteurs) — chargée une seule fois
 * par page, avant la sidebar ET avant le <style> de thème dans header.php (qui en a besoin plus tôt
 * dans le document, pour surcharger les couleurs avant que le reste du <head> ne s'affiche).
 * Fournit : $sidebarUser, $sidebarAgence, $sidebarBadges.
 */
require_once __DIR__ . '/../core/Auth.php';
require_once dirname(__DIR__) . '/app/administration/mon-agence/MonAgenceModel.php';

$sidebarUser = Auth::user();
$sidebarAgence = $sidebarUser && $sidebarUser['id_agence'] !== null
	? MonAgenceModel::agency((int) $sidebarUser['id_agence'])
	: null;
$sidebarBadges = $sidebarUser && $sidebarUser['id_agence'] !== null
	? MonAgenceModel::navBadges((int) $sidebarUser['id_agence'])
	: ['contrats' => 0, 'interventions' => 0, 'biens' => 0, 'echeances_en_retard' => 0];
