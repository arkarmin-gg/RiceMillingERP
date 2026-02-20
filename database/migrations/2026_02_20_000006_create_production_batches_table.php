<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_number')->unique();
            $table->uuid('merchant_id');
            $table->dateTime('production_date');
            $table->string('status');

            $table->foreign('merchant_id')->references('id')->on('parties');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
