<?php
declare(strict_types=1);

/**
 * Normalise un envoi de fichier qui peut arriver de deux façons :
 * - $_FILES[...] classique (multipart/form-data) ;
 * - un repli base64 envoyé en application/x-www-form-urlencoded, sous la forme de deux
 *   champs POST "<field>_b64" (contenu encodé) et "<field>_name" (nom d'origine).
 *
 * Ce repli existe parce que l'hébergement mutualisé de production bloque (403, au niveau du
 * reverse proxy, avant même PHP) toute requête POST en multipart/form-data — voir
 * layouts/admin.js et app/jordan/layouts/modal.js, qui basculent automatiquement les formulaires
 * avec fichier sur ce format quand ils sont soumis depuis une modale.
 *
 * Les deux chemins retournent un tableau de la même forme que $_FILES[...], ce qui permet au
 * code appelant de rester inchangé à part l'appel à UploadHelper::resolve() / ::store().
 */
final class UploadHelper
{
	public static function resolve(string $field): ?array
	{
		if (!empty($_FILES[$field]) && is_uploaded_file((string) ($_FILES[$field]['tmp_name'] ?? ''))) {
			$file = $_FILES[$field];
			$file['__real'] = true;
			return $file;
		}

		$b64 = $_POST[$field . '_b64'] ?? null;
		if ($b64 === null || $b64 === '') {
			return null;
		}

		$data = base64_decode((string) $b64, true);
		if ($data === false) {
			return ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0, '__real' => false];
		}

		$tmpPath = tempnam(sys_get_temp_dir(), 'loka_up_');
		if ($tmpPath === false || file_put_contents($tmpPath, $data) === false) {
			return ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_CANT_WRITE, 'size' => 0, '__real' => false];
		}

		return [
			'name' => basename((string) ($_POST[$field . '_name'] ?? 'fichier')),
			'type' => '',
			'tmp_name' => $tmpPath,
			'error' => UPLOAD_ERR_OK,
			'size' => strlen($data),
			'__real' => false,
		];
	}

	public static function store(array $file, string $destination): bool
	{
		if (!empty($file['__real'])) {
			return move_uploaded_file((string) $file['tmp_name'], $destination);
		}
		return rename((string) $file['tmp_name'], $destination);
	}
}
