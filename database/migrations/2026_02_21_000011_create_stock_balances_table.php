<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->uuid('owner_id');
            $table->uuid('item_id');
            $table->uuid('location_id');
            $table->decimal('quantity', 14, 2);

            $table->primary(['owner_id', 'item_id', 'location_id']);

            $table->foreign('owner_id')->references('id')->on('parties');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('location_id')->references('id')->on('locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
