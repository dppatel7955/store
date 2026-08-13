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
        // Deduplicate any existing visitor_uuid rows keeping the latest
        \Illuminate\Support\Facades\DB::statement("
            DELETE t1 FROM visitors t1
            INNER JOIN visitors t2 
            WHERE t1.id < t2.id AND t1.visitor_uuid = t2.visitor_uuid
        ");

        Schema::table('visitors', function (Blueprint $table) {
            // Drop regular index and add unique index
            $table->dropIndex(['visitor_uuid']);
            $table->unique('visitor_uuid');

            $table->unsignedInteger('total_visits')->default(1)->after('page_views');
            $table->string('screen_resolution', 40)->nullable()->after('platform');
            $table->string('language', 30)->nullable()->after('screen_resolution');
            $table->string('timezone', 60)->nullable()->after('language');
            $table->string('connection_type', 40)->nullable()->after('timezone');
            $table->string('state', 100)->nullable()->after('country');
            $table->string('isp', 150)->nullable()->after('city');
            $table->string('guest_name', 100)->nullable()->after('user_id');
            $table->string('guest_email', 150)->nullable()->after('guest_name');
            $table->string('guest_phone', 40)->nullable()->after('guest_email');
            $table->unsignedInteger('cart_items_count')->default(0)->after('page_views');
            $table->decimal('cart_total', 10, 2)->default(0.00)->after('cart_items_count');
            $table->json('pages_history')->nullable()->after('current_page');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropUnique(['visitor_uuid']);
            $table->index('visitor_uuid');

            $table->dropColumn([
                'total_visits',
                'screen_resolution',
                'language',
                'timezone',
                'connection_type',
                'state',
                'isp',
                'guest_name',
                'guest_email',
                'guest_phone',
                'cart_items_count',
                'cart_total',
                'pages_history',
            ]);
        });
    }
};
