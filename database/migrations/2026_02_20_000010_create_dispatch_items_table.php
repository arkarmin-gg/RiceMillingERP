<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dispatch_id');
            $table->uuid('item_id');
            $table->integer('quantity');

            $table->foreign('dispatch_id')->references('id')->on('dispatches');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_items');
    }
};
