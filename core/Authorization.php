<?php
declare(strict_types=1);

require_once __DIR__ . '/Auth.php';

final class Authorization
{
	public static function requireRole(string ...$roles): array
	{
		return Auth::requireRoles($roles);
	}
}
