<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('album_artist', function (Blueprint $table) {
			$table->id();

			$table->unsignedBiginteger('album_id');
			$table->unsignedBiginteger('artist_id');

			$table->foreign('artist_id')
				->references('id')
				->on('artists')
				->onDelete('cascade');
			$table->foreign('album_id')
				->references('id')
				->on('albums')
				->onDelete('cascade');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('album_artist');
	}
};
