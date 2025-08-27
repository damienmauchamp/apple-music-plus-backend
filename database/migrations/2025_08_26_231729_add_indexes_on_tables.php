<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->index('storeId', 'idx_artists_storeId');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->index('storeId', 'idx_albums_storeId');
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->index('storeId', 'idx_songs_storeId');
            $table->index('albumId', 'idx_songs_albumId');
        });
    }

    public function down(): void
    {
        Schema::dropIndex('artists', 'idx_artists_storeId');
        Schema::dropIndex('albums', 'idx_albums_storeId');
        Schema::dropIndex('songs', 'idx_songs_storeId');
        Schema::dropIndex('songs', 'idx_songs_albumId');
    }
};
