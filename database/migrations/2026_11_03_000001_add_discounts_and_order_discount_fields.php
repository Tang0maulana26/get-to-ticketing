<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->string('type', 20)->default('percent'); // 'percent' or 'fixed'
            $table->bigInteger('value'); // Percentage (e.g. 10 for 10%) or whole Rupiah (e.g. 50000)
            $table->bigInteger('min_order_amount')->default(0); // Minimum subtotal required in Rupiah
            $table->bigInteger('max_discount_amount')->nullable(); // Cap for percentage discount in Rupiah
            $table->integer('usage_limit')->nullable(); // Max times discount can be used (null = unlimited)
            $table->integer('used_count')->default(0);
            $table->timestampTz('start_time')->nullable();
            $table->timestampTz('end_time')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz();

            $table->index(['code', 'active']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete();
            $table->string('discount_code', 50)->nullable();
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_amount')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropColumn(['discount_id', 'discount_code', 'subtotal', 'discount_amount']);
        });

        Schema::dropIfExists('discounts');
    }
};

