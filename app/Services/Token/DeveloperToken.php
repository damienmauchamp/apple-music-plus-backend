<?php

namespace App\Services\Token;

use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;

class DeveloperToken {

	public const ONE_HOUR = 3600;
	public const SIX_MONTHS = 15552000;

	public function __construct(private bool $renew = false) {

	}

	/**
	 * @throws Exception
	 */
	public function getToken(?int $expiracy = null): string {

		// .env variable
		if (env('AM_DEVELOPER_TOKEN')) {
			return env('AM_DEVELOPER_TOKEN');
		}

		$expiracy = $expiracy ?: self::SIX_MONTHS;
		if ($this->renew) {
			return $this->generate($expiracy);
		}

		return $this->fetchToken() ?? $this->generate($expiracy);
	}

	private function fetchToken() {
		if ($this->renew) {
			return null;
		}

		// fetching the first not expired token
		// SELECT token FROM token WHERE expiracy > now() ORDER BY expiracy
		$result = DB::table('tokens')
			->where('expiracy', '>=', date('Y-m-d H:i:s'))
			->orderBy('expiracy')
			->first();

		return $result?->token;
	}

	private function saveToken($token, $expiracy) {
		$expiracy_date = (new \DateTime())->add(new \DateInterval("PT{$expiracy}S"));
		DB::table('tokens')->insert([
			'token' => $token,
			'expiracy' => $expiracy_date,
		]);
	}

	private function getAppleAuthKeyPath(): string {
		return config_path(sprintf('keys/%s', env('APPLE_AUTH_KEY_FILE')));
	}

	private function getAppleAuthKey(): string {

		if (env('APPLE_AUTH_KEY')) {
			return env('APPLE_AUTH_KEY');
		}

		$path = $this->getAppleAuthKeyPath();
		if (!is_file($path)) {
			return '';
		}

		return file_get_contents($path);
	}

	/**
	 * @param int $expiracy
	 * @return string
	 * @throws Exception
	 * @todo : custom exceptions
	 * @todo : expiracy default value as env variable
	 */
	public function generate(int $expiracy): string {
		$private_key = $this->getAppleAuthKey();
		$team_id = env('APPLE_TEAM_ID');
		$key_id = env('APPLE_KEY_ID');

		if (!$private_key) {
			throw new Exception('Unable to generate developer token : no APPLE_AUTH_KEY_FILE or APPLE_AUTH_KEY found');
		}
		if (!$team_id) {
			throw new Exception('Unable to generate developer token : no APPLE_TEAM_ID found');
		}
		if (!$key_id) {
			throw new Exception('Unable to generate developer token : no APPLE_KEY_ID found');
		}

		try {
			$token = JWT::getToken($private_key, $key_id, $team_id, null, (int) $expiracy);
			$this->saveToken($token, $expiracy);

			return $token;
		} catch (DomainException $exception) {
			throw new Exception(sprintf('Unable to generate developer token : %s', $exception->getMessage()));
		}
	}

}
