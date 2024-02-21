<?php

namespace AppleMusicAPI;

class AppleMusic extends AbstractAPI {
	protected string $name = 'Apple Music API';

	public function __construct(string $storefront = '',
								bool $renew = false) {
		parent::__construct($renew);
		$this->storefront = $storefront ?: $this->getDefaultStorefront();
	}

	public function getDefaultStorefront(): string {
		return env('AM_DEFAULT_STOREFRONT', 'us');
	}

	public function test(): APIResponse {
		return $this->get("/catalog/{$this->storefront}/search", [
			'term' => 'test',
//			'types' => 'library-songs',
		]);
	}
}
