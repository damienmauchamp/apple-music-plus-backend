<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('developer_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 255)->unique();
            $table->string('notes')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developer_tokens');
    }
};
