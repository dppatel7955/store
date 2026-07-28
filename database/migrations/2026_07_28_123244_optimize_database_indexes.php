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
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'category_id']);
            $table->index(['is_active', 'brand_id']);
            $table->index(['is_active', 'is_featured']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['is_active', 'parent_id']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'category_id']);
            $table->dropIndex(['is_active', 'brand_id']);
            $table->dropIndex(['is_active', 'is_featured']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'parent_id']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
