<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Services\Core\ReleasesUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

// todo : ShouldBeUniqueUntilProcessing

class UpdateArtist implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(
		public Artist $artist,
		public bool $echo = false
	) {
	}

	public function uniqueId(): string {
		return $this->artist->storeId;
	}

	public function handle(): void {

		$updater = new ReleasesUpdater($this->artist->storeId);

		// fetching artist info
		$updater->updateArtist();

		// fetching albums & songs
		$updater->update();

		// todo : logs

		$this->passed();

		return;
	}

	public function failed(?Throwable $exception): void {
		if (!$this->echo) {
			return;
		}

		echo "❌ {$this->artist->name} ({$this->artist->storeId}) - " . $exception->getMessage() . "\n";
	}
	public function passed(): void {
		if (!$this->echo) {
			return;
		}

		echo "✅ {$this->artist->name} ({$this->artist->storeId})\n";
	}
}
