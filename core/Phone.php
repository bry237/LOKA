<?php
declare(strict_types=1);

/**
 * Normalisation d'un numéro de téléphone (indicatif pays + numéro national) au format E.164.
 */
final class Phone
{
	public static function toE164(string $countryCode, string $national): ?string
	{
		$countryCode = trim($countryCode);
		if (!preg_match('/^\+[1-9]\d{0,3}$/', $countryCode)) {
			return null;
		}

		$digits = preg_replace('/\D/', '', $national) ?? '';
		if ($digits === '') {
			return null;
		}
		if ($countryCode !== '+1' && $digits[0] === '0') {
			$digits = substr($digits, 1);
		}
		if ($digits === '') {
			return null;
		}

		$e164 = $countryCode . $digits;
		return preg_match('/^\+[1-9]\d{6,14}$/', $e164) ? $e164 : null;
	}

	/**
	 * Opération inverse de toE164() : retrouve l'indicatif (parmi la liste de codes proposée) et le
	 * numéro national, pour pré-remplir un formulaire d'édition à partir d'un numéro déjà stocké.
	 *
	 * @param array<string,string> $knownCountryCodes indicatif => libellé (ex. layouts/phone-country-codes.php)
	 * @return array{0: string, 1: string} [indicatif, numéro national]
	 */
	public static function splitE164(string $e164, array $knownCountryCodes): array
	{
		$codes = array_keys($knownCountryCodes);
		usort($codes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
		foreach ($codes as $code) {
			if (str_starts_with($e164, $code)) {
				return [$code, substr($e164, strlen($code))];
			}
		}
		return ['+33', ltrim($e164, '+')];
	}
}
