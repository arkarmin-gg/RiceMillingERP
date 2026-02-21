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
            $table->integer('quantity');

            $table->primary(['owner_id', 'item_id']);

            $table->foreign('owner_id')->references('id')->on('parties');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
