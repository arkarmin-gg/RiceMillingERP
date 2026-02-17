<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('profile_image_url')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->foreignUuid('role_id')->constrained('roles');
            $table->boolean('is_banned')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('full_name');
            $table->index('email');
            $table->index('is_banned');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};

