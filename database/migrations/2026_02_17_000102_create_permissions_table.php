<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('module_id')->constrained('modules');
            $table->enum('action', ['CREATE', 'READ', 'UPDATE', 'DELETE']);
            $table->timestamps();
            $table->softDeletes();

            $table->index('deleted_at');
            $table->unique(['module_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};

