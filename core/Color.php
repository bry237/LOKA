<?php
declare(strict_types=1);

/**
 * Petites opérations sur les couleurs hexadécimales, utilisées pour dériver un thème d'interface
 * à partir de la couleur dominante extraite du logo d'une agence (voir
 * app/administration/mon-agence/parametres.php et layouts/agency-identity.php).
 */
final class Color
{
	public static function isValidHex(string $value): bool
	{
		return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
	}

	/**
	 * Ramène une couleur arbitraire dans une plage exploitable pour du texte blanc par-dessus
	 * (boutons, badges) : ni trop claire (illisible), ni trop terne (grisâtre). Les logos à dominante
	 * blanche/pastel, une fois leurs pixels de fond filtrés côté client, peuvent encore produire une
	 * couleur trop claire ou peu saturée — ce garde-fou s'applique une fois, à l'enregistrement.
	 */
	public static function clampForUi(string $hex): string
	{
		[$h, $s, $l] = self::toHsl($hex);
		$s = max($s, 0.40);
		$l = min(max($l, 0.28), 0.46);
		return self::fromHsl($h, $s, $l);
	}

	/** Variante plus sombre d'une couleur, pour les dégradés (ex. --dark, --deep de admin.css). */
	public static function darken(string $hex, float $amount): string
	{
		[$h, $s, $l] = self::toHsl($hex);
		return self::fromHsl($h, $s, max(0.0, $l * (1 - $amount)));
	}

	/** @return array{0: float, 1: float, 2: float} [teinte 0-360, saturation 0-1, luminosité 0-1] */
	private static function toHsl(string $hex): array
	{
		$hex = ltrim($hex, '#');
		$r = hexdec(substr($hex, 0, 2)) / 255;
		$g = hexdec(substr($hex, 2, 2)) / 255;
		$b = hexdec(substr($hex, 4, 2)) / 255;

		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$l = ($max + $min) / 2;

		if ($max === $min) {
			return [0.0, 0.0, $l];
		}

		$d = $max - $min;
		$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
		$h = match ($max) {
			$r => ($g - $b) / $d + ($g < $b ? 6 : 0),
			$g => ($b - $r) / $d + 2,
			default => ($r - $g) / $d + 4,
		};

		return [$h * 60, $s, $l];
	}

	private static function fromHsl(float $h, float $s, float $l): string
	{
		if ($s === 0.0) {
			$v = (int) round($l * 255);
			return self::toHex($v, $v, $v);
		}

		$q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
		$p = 2 * $l - $q;
		$hk = $h / 360;

		$hueToRgb = static function (float $p, float $q, float $t): float {
			if ($t < 0) $t += 1;
			if ($t > 1) $t -= 1;
			if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
			if ($t < 1 / 2) return $q;
			if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
			return $p;
		};

		$r = $hueToRgb($p, $q, $hk + 1 / 3);
		$g = $hueToRgb($p, $q, $hk);
		$b = $hueToRgb($p, $q, $hk - 1 / 3);

		return self::toHex((int) round($r * 255), (int) round($g * 255), (int) round($b * 255));
	}

	private static function toHex(int $r, int $g, int $b): string
	{
		$clamp = static fn (int $v): int => max(0, min(255, $v));
		return sprintf('#%02X%02X%02X', $clamp($r), $clamp($g), $clamp($b));
	}
}
