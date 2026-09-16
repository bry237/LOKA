<?php
declare(strict_types=1);

final class Session
{
	private const NAME = 'LOKA_SESSION';

	public static function start(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			return;
		}

		$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		session_name(self::NAME);
		session_set_cookie_params([
			'lifetime' => 0,
			'path' => '/',
			'domain' => '',
			'secure' => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		session_start();
	}

	public static function regenerate(): void
	{
		self::start();
		session_regenerate_id(true);
	}

	public static function destroy(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}

		$_SESSION = [];
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		session_destroy();
	}
}
