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
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_uuid')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 30)->default('Desktop')->index();
            $table->string('browser', 60)->nullable();
            $table->string('platform', 60)->nullable();
            $table->text('landing_page')->nullable();
            $table->text('current_page')->nullable();
            $table->text('referrer')->nullable();
            $table->unsignedInteger('page_views')->default(1);
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
