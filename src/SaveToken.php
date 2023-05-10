<?php

namespace Gulachek\Bassline;

class SaveToken
{
	const RESERVE_SECONDS = 10;

	public function __construct(
		public readonly int $userId,
		public readonly int $unixSaveTime,
		public readonly string $key
	)
	{
	}

	public static function decode(string $encoded): SaveToken
	{
		$ar = \json_decode($encoded);
		return new SaveToken($ar->userId, $ar->unixSaveTime, $ar->key);
	}

	private static function newKey(): string
	{
		return \base64_encode(\random_bytes(16));
	}

	public function encode(): string
	{
		return \json_encode([
			'userId' => $this->userId,
			'unixSaveTime' => $this->unixSaveTime,
			'key' => $this->key
		]);
	}

	public function tryReserve(int $userId, ?string $key = null): ?SaveToken
	{
		$key = $key ?? $this->key;

		if ($key !== $this->key)
			return null;

		$now_sec = \time();

		$time_buffer = $userId === $this->userId ? 0 : self::RESERVE_SECONDS;

		if ($now_sec < $this->unixSaveTime + $time_buffer)
			return null;

		return new SaveToken($userId, $now_sec, self::newKey());
	}

	public static function createForUser(int $userId): SaveToken
	{
		return new SaveToken($userId, \time(), self::newKey());
	}

	public static function tryReserveEncoded(int $userId, ?string $encodedToken, ?string $key = null): ?SaveToken
	{
		if ($encodedToken)
		{
			$token = self::decode($encodedToken);
			return $token->tryReserve($userId, $key);
		}
		else
		{
			return self::createForUser($userId);
		}
	}
}
