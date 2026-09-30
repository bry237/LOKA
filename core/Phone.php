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
}
