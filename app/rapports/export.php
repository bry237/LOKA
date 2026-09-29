<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';

$user = Authorization::requireRole(['administrateur', 'gestionnaire']);
$controller = new RapportController();

$type = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$agencyId = $user['id_agence'];

$data = [];
$headers = [];
$filename = "export_{$type}_" . date('Ymd_His') . ".csv";

switch ($type) {
    case 'revenus':
        $raw = $controller->getRevenueReport($agencyId, $dateFrom, $dateTo);
        $headers = ['Mois', 'Total Encaissé', 'Nb Paiements'];
        foreach($raw as $r) {
            $data[] = [$r['mois'], $r['total_encaisse'], $r['nb_paiements']];
        }
        break;
    case 'occupation':
        $raw = $controller->getOccupancyReport($agencyId);
        $headers = ['Statut', 'Nombre'];
        foreach($raw as $r) {
            $data[] = [$r['statut'], $r['count']];
        }
        break;
    case 'impayes':
        $raw = $controller->getOverdueReport($agencyId);
        $headers = ['Locataire', 'Bien', 'Contrat', 'Montant dû', 'Retard (jours)'];
        foreach($raw as $r) {
            $data[] = [$r['locataire_nom'], $r['bien_nom'], $r['contrat_ref'], $r['montant_du'], $r['retard_jours']];
        }
        break;
    case 'maintenance':
        $raw = $controller->getMaintenanceReport($agencyId, $dateFrom, $dateTo);
        $headers = ['Statut', 'Priorité', 'Nombre', 'Coût total', 'Résolution moyenne (jours)'];
        foreach($raw as $r) {
            $data[] = [$r['statut'], $r['priorite'], $r['count'], $r['total_cout'], $r['avg_resolution_days']];
        }
        break;
    case 'performance_biens':
        $raw = $controller->getPropertyPerformance($agencyId);
        $headers = ['Bien', 'Statut', 'Revenu total généré'];
        foreach($raw as $r) {
            $data[] = [$r['nom'], $r['statut'], $r['total_revenu']];
        }
        break;
    case 'fiabilite_locataires':
        $raw = $controller->getTenantReport($agencyId);
        $headers = ['Locataire', 'Total des échéances', 'Échéances payées', 'Taux de fiabilité'];
        foreach($raw as $r) {
            $taux = $r['total_echeances'] > 0 ? round(((float)$r['payees'] / (float)$r['total_echeances']) * 100, 1) . ' %' : '0 %';
            $data[] = [$r['locataire'], $r['total_echeances'], $r['payees'], $taux];
        }
        break;
    default:
        die("Type d'export invalide.");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, $headers);
foreach ($data as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit;
