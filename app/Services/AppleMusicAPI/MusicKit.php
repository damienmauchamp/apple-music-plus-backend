<?php

namespace AppleMusicAPI;

//class MusicKit extends AbstractAPI {
class MusicKit extends AppleMusic {
	protected string $name = 'MusicKit API';
	protected string $path = 'v1/me/';
	private string $music_kit_token = '';

	private ?int $limit = null;
	private string $offset = '';
	private string $l = '';

	public function __construct(?string $music_kit_token = null, bool $renew = false) {
		// $this->music_kit_token = $music_kit_token ?? self::getHeaderToken() ?? $_SESSION['headerUserMusicToken'] ?? '';
		$this->music_kit_token = $music_kit_token ?? self::getHeaderToken() ?? '';
		parent::__construct($renew);
	}

	private static function getHeaderToken(): ?string {
		return request()?->headers?->get('Music-Token', null);
	}

//	public static function current(): self {
//		$api = new self();
//		return $api->setUserToken();
//	}
//
//	/**
//	 * @throws Exception No user found
//	 */
//	public static function fromUser(int $id): self {
//		$api = new self();
//		return $api->setUserTokenViaId($id);
//	}
//
//	public function setUserToken(): self {
//		return $this->setMusicKitToken($this->app->getUserToken());
//	}
//
//	/**
//	 * @throws Exception No user found
//	 */
//	public function setUserTokenViaId(int $id): self {
//		$user = $this->app->manager()->findOne2('users', ['id' => $id]);
//		if(!$user) {
//			throw new Exception('No user found');
//		}
//		return $this->setMusicKitToken($user['musickit_user_token']);
//	}

	public function setMusicKitToken(string $music_kit_token): self {
		$this->music_kit_token = $music_kit_token;
		$this->init();

		return $this;
	}

	public function headers(): array {
		return array_merge(parent::headers(), [
			'Music-User-Token' => $this->music_kit_token,
		]);
	}

	protected function initDeveloperToken(bool $renew = false): void {
		parent::initDeveloperToken($renew);

		// .env MUSIC_KIT_TOKEN
		if (env('AM_MUSIC_KIT_TOKEN')) {
			$this->music_kit_token = env('AM_MUSIC_KIT_TOKEN');
		}
	}

	public function test(): APIResponse {
		return $this->get('/library/search', [
			'term' => 'test',
			'types' => 'library-songs',
		]);
	}

	// region Artists

	public function getAllLibraryArtists(array $parameters = []): APIResponse {
		return $this->get('/library/artists', $parameters);
	}

	private function getPage($uri, array $parameters = [],
		?int $max_page = 5,
		?int $max_results = null): array {

		// setting limit
		$parameters['limit'] = $parameters['limit'] ?? $this->limit ?? 100;

		$page = 1;
		$results = 0;
		$data = [];
		while (true) {
			// setting pagination
			$parameters['offset'] = ($page - 1) * $parameters['limit'];

			// fetch
			$response = $this->get($uri, $parameters)->getData();

			// data
			$results += count($response['data']);
			$data = array_merge($data, array_values($response['data']));
			$next = $response['next'] ?? null;
			$total = $response['meta']['total'];

			if (!$next || !$data || $max_results !== null && $results >= $max_results || $max_page !== null && $page >= $max_page) {
				break;
			}

			$page++;
		}

		return [
			'results' => $results,
			'data' => $data,
			'page' => $page,
			'total_page' => (int) ceil($total / $parameters['limit']),
			'total' => $total ?? 0,
		];
	}

	public function getAllLibraryArtistsPaginate(array $parameters = [],
		?int $max_page = 5,
		?int $max_results = null): array {
		return $this->getPage('/library/artists', $parameters, $max_page, $max_results);
	}

	// endregion Artists

}
