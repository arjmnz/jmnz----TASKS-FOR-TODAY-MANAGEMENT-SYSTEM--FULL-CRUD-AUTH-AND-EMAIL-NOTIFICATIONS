<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve older users without inventing passwords or changing their identity.
        // Blank legacy emails become NULL; duplicate addresses need an explicit correction.
        if (DB::table('users')->whereNotNull('email')->where('email', '<>', '')
            ->select('email')->groupBy('email')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Correct duplicate user email addresses before migrating.');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->default(null)->change();
        });
        DB::table('users')->where('email', '')->update(['email' => null]);
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['password', 'email_verified_at', 'remember_token']);
        });
        DB::table('users')->whereNull('email')->update(['email' => '']);
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->default('')->change();
        });
    }
};
