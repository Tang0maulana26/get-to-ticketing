<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
        });

        // Populate usernames for existing users if any
        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $user) {
            $base = Str::slug(explode('@', $user->email)[0], '_');
            if (empty($base)) {
                $base = 'user_' . $user->id;
            }
            $username = $base;
            $counter = 1;
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base . '_' . $counter++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        Schema::table('discounts', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('name')->constrained('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn(['event_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username']);
        });
    }
};

