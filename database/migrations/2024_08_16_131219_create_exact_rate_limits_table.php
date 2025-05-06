<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exact_rate_limits', function (Blueprint $table): void {
            $table->id();
            $table->string('exact_division')->index();
            $table->integer('timestamp');
            $table->integer('limit');
            $table->integer('remaining');
            $table->timestamp('reset_at');
            $table->integer('minutely_limit');
            $table->integer('minutely_remaining');
            $table->timestamp('minutely_reset_at');
            $table->timestamps();

            $table->index(['exact_division', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exact_rate_limits');
    }
};
