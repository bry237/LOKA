<?php
declare(strict_types=1);

final class FileUpload
{
	private const EXTENSIONS = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'application/pdf' => 'pdf',
	];

	/**
	 * Valide et déplace un fichier uploadé ($_FILES[...]) vers $destinationDir.
	 *
	 * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
	 * @param string[] $allowedMimes
	 * @return array{stored_name: string, original_name: string, mime_type: string, size: int}
	 */
	public static function store(array $file, string $destinationDir, array $allowedMimes, int $maxBytes): array
	{
		if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			throw new RuntimeException('Le fichier envoyé est invalide ou incomplet.');
		}
		if (!is_uploaded_file($file['tmp_name'])) {
			throw new RuntimeException('Le fichier envoyé est invalide.');
		}
		if ((int) $file['size'] > $maxBytes) {
			throw new RuntimeException('Le fichier dépasse la taille maximale autorisée (' . (int) ($maxBytes / 1024 / 1024) . ' Mo).');
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = (string) $finfo->file($file['tmp_name']);
		if (!in_array($mimeType, $allowedMimes, true)) {
			throw new RuntimeException('Type de fichier non autorisé (formats acceptés : PDF, JPEG, PNG).');
		}

		if (!is_dir($destinationDir) && !mkdir($destinationDir, 0750, true) && !is_dir($destinationDir)) {
			throw new RuntimeException('Impossible de créer le dossier de stockage.');
		}

		$storedName = bin2hex(random_bytes(16)) . '.' . (self::EXTENSIONS[$mimeType] ?? 'bin');
		$destination = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $storedName;

		if (!move_uploaded_file($file['tmp_name'], $destination)) {
			throw new RuntimeException('Impossible d’enregistrer le fichier envoyé.');
		}

		return [
			'stored_name' => $storedName,
			'original_name' => basename((string) $file['name']),
			'mime_type' => $mimeType,
			'size' => (int) $file['size'],
		];
	}
}
