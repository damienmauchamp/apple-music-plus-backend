<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		//
		Schema::table('albums', function (Blueprint $table) {
			$table->boolean('isComplete')
				->after('isCompilation');
			$table->boolean('disabled')
				->default(false)
				->after('custom');
		});

		Schema::table('songs', function (Blueprint $table) {
			$table->string('previewUrl')
				->nullable()
				->after('durationInMillis');
			$table->boolean('disabled')
				->default(false)
				->after('custom');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('albums', function (Blueprint $table) {
			$table->dropColumn('isComplete');
			$table->dropColumn('disabled');
		});
		Schema::table('songs', function (Blueprint $table) {
			$table->dropColumn('previewUrl');
			$table->dropColumn('disabled');
		});
	}
};
