<?php

namespace AppleMusicAPI;

use App\Services\Token\DeveloperToken;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class AbstractAPI {

	protected string $name = 'API';
	protected string $url = 'https://api.music.apple.com/';
	protected string $path = 'v1/';
	protected string $storefront;
	//
	protected bool $developer = true;
	protected bool $scrapped = false;
	private string $developer_token = '';
	private int $token_expiracy = 3600; // 3600;
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
		$this->initClient();
	}

	/**
	 * @throws Exception Too many failures
	 */
	public function prepare(bool $retrying = false): void {
		if(!$retrying) {
			$this->token_expiracy_status_try = 0;
			return;
		}

		$this->token_expiracy_status_try++;

		if($this->token_expiracy_status_try > $this->token_expiracy_status_max_try) {
			// todo : custom exception
			throw new Exception('Too many failures');
		}
	}

	public function headers(): array {
		return [];
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
			'verify' => (bool) env('AM_SSL_VERIRY', false),
			'headers' => $this->headers(),
		];

		$token = ($token ?? $this->developer_token) ?: '';
		if($token) {
			$options['headers']['Authorization'] = "Bearer {$token}";
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
		} catch(GuzzleException $e) {
			$expired = true;
		}
		$this->developer_token = $current_token;
		return $expired;
	}

	/**
	 * @throws Exception
	 */
	protected function initDeveloperToken(bool $renew = false): void {

		if(!$this->developer) {
			$this->developer_token = '';
			return;
		}

		$this->developer_token = (new DeveloperToken($renew))->getToken();
	}

	/**
	 * @throws GuzzleException 400 error
	 * @throws Exception Too many failures
	 */
	protected function get($uri, array $parameters = [], array $options = [], bool $retrying = false): APIResponse {

		$this->prepare($retrying);
		$this->setUrl($uri, $parameters);

		try {
//			return $this->client->get($uri, $this->options);
			$request = new APIRequest($this->client, 'GET', $uri, $parameters, $options, $retrying, $this->scrapped);
			return $request->run();
		} catch(GuzzleException $e) {
			if($this->token_expiracy_status && $this->token_expiracy_status === $e->getCode()) {
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
