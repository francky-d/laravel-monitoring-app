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
        Schema::table('incidents', function (Blueprint $table) {
            $table->integer('response_code')->nullable();
            $table->integer('response_time')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('resolved_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notifications')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['response_code', 'response_time', 'error_message', 'resolved_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_notifications');
        });
    }
};