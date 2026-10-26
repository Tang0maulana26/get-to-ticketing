<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer');
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 300);
            $table->text('description');
            $table->string('banner')->nullable();
            $table->string('venue', 160);
            $table->string('city', 80);
            $table->string('address', 300);
            $table->timestampTz('start_time');
            $table->timestampTz('end_time')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('featured')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['status', 'start_time']);
            $table->index(['category_id', 'start_time']);
        });
        Schema::create('event_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('name', 80);
            $table->string('description', 300)->nullable();
            $table->bigInteger('price'); // Whole Indonesian rupiah, never floating point.
            $table->integer('capacity');
            $table->integer('stock'); // Available, excludes pending reservations and paid tickets.
            $table->integer('max_per_order')->default(6);
            $table->boolean('active')->default(true);
            $table->timestampsTz();
            $table->unique(['event_id', 'name']);
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('checkout_key');
            $table->string('status', 20)->default('pending');
            $table->string('currency', 3)->default('IDR');
            $table->bigInteger('total');
            $table->timestampTz('expires_at');
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'checkout_key']);
            $table->index(['status', 'expires_at']);
            $table->index(['user_id', 'created_at']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_category_id')->constrained()->restrictOnDelete();
            $table->string('event_title', 160);
            $table->string('ticket_name', 80);
            $table->timestampTz('event_start_time');
            $table->string('venue', 240);
            $table->integer('quantity');
            $table->bigInteger('unit_price');
            $table->bigInteger('subtotal');
            $table->timestampsTz();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->uuid('code')->unique();
            $table->string('status', 20)->default('valid');
            $table->timestampTz('checked_in_at')->nullable();
            $table->timestampsTz();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ticket_categories ADD CONSTRAINT inventory_nonnegative CHECK (capacity >= 0 AND stock >= 0 AND stock <= capacity AND price >= 0 AND max_per_order BETWEEN 1 AND 20)');
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT valid_order_item CHECK (quantity > 0 AND unit_price >= 0 AND subtotal = quantity * unit_price)');
            DB::statement('ALTER TABLE orders ADD CONSTRAINT valid_order_total CHECK (total >= 0)');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT valid_order_status CHECK (status IN ('pending', 'paid', 'cancelled', 'expired'))");
        }
    }

    public function down(): void
    {
        foreach (['tickets', 'order_items', 'orders', 'ticket_categories', 'event_images', 'events', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
