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
			$table->dateTime('last_updated')
				->nullable()
				->comment('Last time the artist data has been fetched')
				->after('artworkUrl');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('artists', function (Blueprint $table) {
			$table->dropColumn('last_updated');
		});
	}
};
