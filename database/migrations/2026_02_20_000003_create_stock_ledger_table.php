<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('movement_type', [
                'GRN',
                'SALE',
                'PRODUCTION',
                'ADJUSTMENT',
                'TRANSFER',
            ]);
            $table->uuid('reference_id');
            $table->uuid('owner_id');
            $table->uuid('item_id');
            $table->uuid('location_id');
            $table->integer('quantity');
            $table->integer('direction');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('owner_id')->references('id')->on('parties');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('location_id')->references('id')->on('locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
