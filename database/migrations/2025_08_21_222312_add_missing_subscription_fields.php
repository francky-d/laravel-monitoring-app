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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('application_id')->nullable();
            $table->string('notification_type')->nullable();
            $table->string('email')->nullable();
            $table->string('webhook_url')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Add foreign key constraint
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropColumn(['application_id', 'notification_type', 'email', 'webhook_url', 'is_active']);
        });
    }
};