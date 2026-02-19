<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('category', [
                'PADDY',
                'RICE',
                'BROKEN',
                'POINT_BROKEN',
                'BRAN',
                'POINT_BRAN',
                'HUSK',
                'WASTED',
            ]);
            $table->string('unit');
            $table->timestamps();
            $table->softDeletes();

            $table->index('deleted_at');
            $table->unique(['name', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

