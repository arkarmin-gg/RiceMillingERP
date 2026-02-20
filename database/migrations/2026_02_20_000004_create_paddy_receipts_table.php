<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paddy_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receipt_number')->unique();
            $table->uuid('merchant_id');
            $table->dateTime('received_date');
            $table->text('description')->nullable();

            $table->foreign('merchant_id')->references('id')->on('parties');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paddy_receipts');
    }
};

