<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exact_credentials', function (Blueprint $table): void {
            $table->id();
            $table->string('exact_connection')->index();
            $table->string('token_type');
            $table->string('access_token', 2048);
            $table->string('refresh_token', 2048);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exact_credentials');
    }
};
