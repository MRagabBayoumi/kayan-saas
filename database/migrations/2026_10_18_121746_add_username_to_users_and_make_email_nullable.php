<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 100)->nullable()->unique()->after('name');
            }
            $table->string('email')->nullable()->change();
        });

        // Populate username for existing users if null
        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $user) {
            $base = ! empty($user->email) ? explode('@', $user->email)[0] : 'user_'.$user->id;
            $username = Str::slug($base, '_');
            if (empty($username)) {
                $username = 'user_'.$user->id;
            }

            $candidate = $username;
            $counter = 1;
            while (DB::table('users')->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = $username.$counter++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
            $table->string('email')->nullable(false)->change();
        });
    }
};
