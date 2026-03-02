<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->uuid('subject_id')->nullable()->after('description');
            $table->string('subject_type')->nullable()->after('subject_id');
            $table->json('properties')->nullable()->after('subject_type');

            $table->index(['subject_id', 'subject_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_id', 'subject_type']);
            $table->dropColumn(['subject_id', 'subject_type', 'properties']);
        });
    }
};
