<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token');
            $table->foreignUuid('user_id')->nullable()->constrained('users');
            $table->foreignUuid('admin_id')->nullable()->constrained('admins');
            $table->timestamp('expires_at');
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('admin_id');
            $table->index('expires_at');
            $table->index('is_revoked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};

