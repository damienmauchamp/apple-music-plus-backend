<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('name');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn('active');
            $table->dropSoftDeletes();
        });
    }
};
