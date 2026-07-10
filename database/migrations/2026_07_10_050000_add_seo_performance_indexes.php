<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
