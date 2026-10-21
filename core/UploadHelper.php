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

	/**
	 * Variante de resolve() pour un champ <input type="file" multiple> : lit $_FILES[field]
	 * classique (format tableau PHP natif) quand il existe, sinon le repli base64 envoyé via
	 * "<field>_b64[]" / "<field>_name[]". Retourne un tableau de fichiers au même format que
	 * resolve() (un par fichier effectivement sélectionné).
	 *
	 * @return array<int, array{name: string, type: string, tmp_name: string, error: int, size: int, __real: bool}>
	 */
	public static function resolveMultiple(string $field): array
	{
		$files = [];

		if (!empty($_FILES[$field]) && is_array($_FILES[$field]['name'] ?? null)) {
			foreach ($_FILES[$field]['name'] as $i => $name) {
				$error = $_FILES[$field]['error'][$i] ?? UPLOAD_ERR_NO_FILE;
				if ($error === UPLOAD_ERR_NO_FILE) {
					continue;
				}
				$files[] = [
					'name' => (string) $name,
					'type' => (string) ($_FILES[$field]['type'][$i] ?? ''),
					'tmp_name' => (string) ($_FILES[$field]['tmp_name'][$i] ?? ''),
					'error' => (int) $error,
					'size' => (int) ($_FILES[$field]['size'][$i] ?? 0),
					'__real' => true,
				];
			}
			return $files;
		}

		$b64List = $_POST[$field . '_b64'] ?? null;
		$nameList = $_POST[$field . '_name'] ?? null;
		if (!is_array($b64List) || !is_array($nameList)) {
			return [];
		}

		foreach ($b64List as $i => $b64) {
			if ($b64 === '' || $b64 === null) {
				continue;
			}
			$data = base64_decode((string) $b64, true);
			if ($data === false) {
				continue;
			}
			$tmpPath = tempnam(sys_get_temp_dir(), 'loka_up_');
			if ($tmpPath === false || file_put_contents($tmpPath, $data) === false) {
				continue;
			}
			$files[] = [
				'name' => basename((string) ($nameList[$i] ?? 'fichier')),
				'type' => '',
				'tmp_name' => $tmpPath,
				'error' => UPLOAD_ERR_OK,
				'size' => strlen($data),
				'__real' => false,
			];
		}

		return $files;
	}
}
