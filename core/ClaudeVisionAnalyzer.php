<?php
declare(strict_types=1);

/**
 * Première analyse assistée (pas une vérification d'identité légale) d'un document
 * de vérification d'agence via l'API Claude (vision). Échoue toujours "doucement" :
 * une erreur réseau/clé absente ne doit jamais bloquer l'inscription, seulement priver
 * l'admin du résumé IA lors de sa revue manuelle.
 */
final class ClaudeVisionAnalyzer
{
	private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
	private const API_VERSION = '2023-06-01';

	/**
	 * @return array{resume: ?string, alertes: string[], erreur: bool}
	 */
	public static function analyze(string $absoluteFilePath, string $mimeType, string $documentType, string $expectedName): array
	{
		$config = require dirname(__DIR__) . '/config/services.php';
		$apiKey = (string) ($config['anthropic']['api_key'] ?? '');
		if ($apiKey === '' || $mimeType === 'application/pdf' || !is_readable($absoluteFilePath)) {
			// Pas de clé configurée, ou PDF (l'API vision ne lit que des images) : on saute l'analyse.
			return ['resume' => null, 'alertes' => [], 'erreur' => $apiKey === ''];
		}

		$label = $documentType === 'PIECE_IDENTITE'
			? "une pièce d'identité du responsable déclaré \"{$expectedName}\""
			: "un justificatif d'immatriculation d'agence (Kbis/SIRET)";

		$prompt = "Ce document doit être {$label}. Réponds UNIQUEMENT avec un objet JSON strict de la forme "
			. '{"type_detecte": string, "resume": string, "alertes": string[]}. '
			. "\"alertes\" doit lister toute incohérence (nom ne correspondant pas, document expiré, mauvaise qualité/illisible, type de document différent de celui attendu). "
			. 'Liste vide si rien à signaler. Ne mets aucun texte en dehors du JSON.';

		$payload = json_encode([
			'model' => $config['anthropic']['model'] ?? 'claude-sonnet-5',
			'max_tokens' => 500,
			'messages' => [[
				'role' => 'user',
				'content' => [
					['type' => 'image', 'source' => [
						'type' => 'base64',
						'media_type' => $mimeType,
						'data' => base64_encode((string) file_get_contents($absoluteFilePath)),
					]],
					['type' => 'text', 'text' => $prompt],
				],
			]],
		], JSON_THROW_ON_ERROR);

		try {
			$ch = curl_init(self::ENDPOINT);
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 20,
				CURLOPT_HTTPHEADER => [
					'content-type: application/json',
					'x-api-key: ' . $apiKey,
					'anthropic-version: ' . self::API_VERSION,
				],
				CURLOPT_POSTFIELDS => $payload,
			]);
			$response = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($response === false || $status !== 200) {
				return ['resume' => null, 'alertes' => [], 'erreur' => true];
			}

			$body = json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
			$text = (string) ($body['content'][0]['text'] ?? '');
			$verdict = json_decode($text, true);
			if (!is_array($verdict)) {
				return ['resume' => null, 'alertes' => [], 'erreur' => true];
			}

			return [
				'resume' => is_string($verdict['resume'] ?? null) ? $verdict['resume'] : null,
				'alertes' => array_values(array_filter((array) ($verdict['alertes'] ?? []), 'is_string')),
				'erreur' => false,
			];
		} catch (Throwable $exception) {
			return ['resume' => null, 'alertes' => [], 'erreur' => true];
		}
	}
}
