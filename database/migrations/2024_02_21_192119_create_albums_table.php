<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('albums', function (Blueprint $table) {
			$table->id();
			$table->string('storeId');
			$table->string('name');
			$table->string('artistName');
			$table->string('artworkUrl')
				->comment('Can be formatted as {w}x{h}bb.{b}');
			$table->string('releaseDate');
			$table->string('contentRating');
			$table->integer('trackCount');
			$table->boolean('isSingle');
			$table->boolean('isCompilation')->default(false);
			$table->string('upc')->default('');
			$table->boolean('custom')->default(false);
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('albums');
	}
};
