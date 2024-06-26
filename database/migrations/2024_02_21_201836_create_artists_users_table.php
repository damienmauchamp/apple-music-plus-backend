<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('artist_user', function (Blueprint $table) {
			$table->id();

			$table->unsignedBiginteger('artist_id');
			$table->unsignedBiginteger('user_id');

			$table->foreign('artist_id')
				->references('id')
				->on('artists')
				->onDelete('cascade');
			$table->foreign('user_id')
				->references('id')
				->on('users')
				->onDelete('cascade');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('artist_user');
	}
};
