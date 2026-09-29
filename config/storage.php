<?php
declare(strict_types=1);

/**
 * Emplacement de stockage des fichiers uploadés (hors du dossier servi par Apache,
 * pour ne jamais être accessible par une URL directe).
 */
return [
	'agence_documents_path' => getenv('LOKA_STORAGE_PATH') ?: dirname(__DIR__, 3) . '/loka-storage/documents-agence',
];
