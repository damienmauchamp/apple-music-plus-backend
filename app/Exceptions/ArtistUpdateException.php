<?php

namespace App\Exceptions;

use Exception;

class ArtistUpdateException extends Exception {
	// https://laravel.com/docs/10.x/errors#exception-log-context
	// public function context(): array {
	// 	return ['order_id' => $this->orderId];
	// }
}
