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
            $table->string('username', 100)->nullable()->unique();
        });

        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $base = substr(Str::slug($user->name, '_'), 0, 80) ?: 'user';
                $username = $base;
                $suffix = 2;
                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $base.'_'.$suffix++;
                }
                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
