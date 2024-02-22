<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::table('artists', function (Blueprint $table) {
			$table->string('artworkUrl')
				->comment('Can be formatted as {w}x{h}bb.{b}')
				->after('name');
		});

	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('artists', function (Blueprint $table) {
			$table->string('artworkUrl');
		});
	}
};
