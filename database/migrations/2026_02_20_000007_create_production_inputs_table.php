<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_inputs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id');
            $table->uuid('item_id');
            $table->integer('quantity');
            $table->uuid('location_id');

            $table->foreign('batch_id')->references('id')->on('production_batches');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('location_id')->references('id')->on('locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_inputs');
    }
};
