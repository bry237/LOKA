<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/DocumentAgenceModel.php';

Authorization::requireRole('Administrateur plateforme');

$id = (int) ($_GET['id'] ?? 0);
$document = $id > 0 ? DocumentAgenceModel::find($id) : null;
if (!$document) {
	http_response_code(404);
	exit('Document introuvable.');
}

$storage = require dirname(__DIR__, 3) . '/config/storage.php';
$baseDir = realpath($storage['agence_documents_path']);
$path = realpath(rtrim($storage['agence_documents_path'], '/\\') . '/agence-' . $document['id_agence'] . '/' . $document['chemin_stockage']);

if ($baseDir === false || $path === false || !str_starts_with($path, $baseDir)) {
	http_response_code(404);
	exit('Document introuvable.');
}

header('Content-Type: ' . ($document['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $document['nom_original']) . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
