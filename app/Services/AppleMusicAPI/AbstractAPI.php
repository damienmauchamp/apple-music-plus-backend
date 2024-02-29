<?php

namespace AppleMusicAPI;

use App\Services\Token\DeveloperToken;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AbstractAPI {

	protected string $name = 'API';
	protected string $url = 'https://api.music.apple.com/';
	protected string $path = 'v1/';
	protected string $storefront;
	//
	protected bool $developer = true;
	protected bool $scrapped = false;
	private string $developer_token = '';
	private string $music_kit_token = '';
	// private int $token_expiracy = 3600; // 3600;
	//
	private ?int $token_expiracy_status = 401;
	private ?int $token_expiracy_status_try = 0;
	private ?int $token_expiracy_status_max_try = 2;
	//
	private Client $client;

	//

	public function __construct(bool $renew = false) {
//		parent::__construct();
		$this->init($renew);
	}

	public function init(bool $renew = false): void {
		$this->initDeveloperToken($renew);
		$this->initMusicKitToken();
		$this->initClient();
	}

	/**
	 * @throws Exception Too many failures
	 */
	public function prepare(bool $retrying = false): void {
		if (!$retrying) {
			$this->token_expiracy_status_try = 0;

			return;
		}

		$this->token_expiracy_status_try++;

		if ($this->token_expiracy_status_try > $this->token_expiracy_status_max_try) {
			// todo : custom exception
			throw new Exception('Too many failures');
		}
	}

	public function headers(): array {
		$headers = [];
		if ($this->music_kit_token) {
			$headers['Music-User-Token'] = $this->music_kit_token;
		}

		return $headers;
	}

	protected function setUrl(&$uri, array $parameters = []): string {
		$uri = preg_replace('/\/+/', '/', sprintf('%s/%s%s', $this->path, $uri,
			$parameters ? sprintf('?%s', http_build_query($parameters)) : ''));

		return $uri;
	}

	protected function initClient(?string $token = null): self {
		$options = [
			'base_uri' => $this->url,
//			'Accept' => 'application/json',
			'headers' => $this->headers(),
		];

		if (env('AM_SSL_CERT')) {
			$options['verify'] = env('AM_SSL_CERT');
		} else {
			$options['verify'] = env('AM_SSL_VERIRY', false);
		}

		$token = ($token ?? $this->developer_token) ?: '';
		if ($token) {
			$options['headers'] = array_merge([
				'Authorization' => "Bearer {$token}",
			], $options['headers']);
		}
		$this->client = new Client($options);

		return $this;
	}

	protected function tokenExpired(string $token): bool {
		$current_token = $this->developer_token;
		$this->initClient($token);

		$expired = false;
		try {
			$response = $this->test();
		} catch (GuzzleException $e) {
			$expired = true;
		}
		$this->developer_token = $current_token;

		return $expired;
	}

	/**
	 * @throws Exception
	 */
	protected function initDeveloperToken(bool $renew = false): void {

		if (!$this->developer) {
			$this->developer_token = '';

			return;
		}

		$this->developer_token = (new DeveloperToken($renew))->getToken();
	}

	protected function initMusicKitToken(): void {
		$this->music_kit_token = MusicKit::getRequestHeaderMusicToken() ?? '';
	}

	public function setMusicKitToken(string $music_kit_token): self {
		$this->music_kit_token = $music_kit_token;

		return $this;
	}

	/**
	 * @throws GuzzleException 400 error
	 * @throws Exception Too many failures
	 */
	protected function get($uri, array $parameters = [], array $options = [], bool $retrying = false): APIResponse {

		$this->prepare($retrying);
		$this->setUrl($uri, $parameters);

		try {
			$request = new APIRequest($this->client, 'GET', $uri, $parameters, $options, $retrying, $this->scrapped);

			return $request->run();
		} catch (GuzzleException $e) {
			if ($this->token_expiracy_status && $this->token_expiracy_status === $e->getCode()) {
				// retry
				$this->init(true);

				return $this->get($uri, $parameters, $options, true);
			}
			throw $e;
		}
	}

	/**
	 * @throws GuzzleException 400 error
	 */
	public function test(): APIResponse {
		return $this->get('/test');
	}

//	public function parse(ResponseInterface $response) {
//		return json_decode($response->getBody()->getContents(), true);
//	}

}
