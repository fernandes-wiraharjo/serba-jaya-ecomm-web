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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('profile_photo_disk')->nullable();
            $table->string('profile_photo_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'birth_date', 'gender', 'profile_photo_disk', 'profile_photo_path']);
        });
    }
};
