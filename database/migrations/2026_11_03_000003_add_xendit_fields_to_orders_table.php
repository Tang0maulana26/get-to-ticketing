<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('xendit_invoice_id', 100)->nullable()->after('status');
            $table->text('xendit_invoice_url')->nullable()->after('xendit_invoice_id');
            $table->string('payment_method', 50)->nullable()->after('xendit_invoice_url');
            $table->string('payment_channel', 50)->nullable()->after('payment_method');
            $table->index('xendit_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['xendit_invoice_id']);
            $table->dropColumn(['xendit_invoice_id', 'xendit_invoice_url', 'payment_method', 'payment_channel']);
        });
    }
};

