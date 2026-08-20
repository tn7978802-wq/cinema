<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite does not support ALTER COLUMN; rebuild the table safely
        if ($driver === 'sqlite') {
            DB::transaction(function () {
                // Create a new table with admin_role as integer (default 0)
                Schema::create('Users_new', function (Blueprint $table) {
                    $table->id();
                    $table->string('fullname');
                    $table->string('email')->unique();
                    $table->timestamp('email_verified_at')->nullable();
                    $table->string('password');
                    $table->string('phone')->nullable();
                    $table->string('google_id')->nullable();
                    $table->string('avatar')->nullable();
                    $table->string('username')->nullable();
                    $table->string('security_code')->nullable();
                    $table->integer('admin_role')->default(0);
                    $table->rememberToken();
                    $table->timestamps();
                });

                // Copy data from old table mapping boolean -> integer (true -> 2, else 0)
                DB::statement(
                    'INSERT INTO "Users_new" (id, fullname, email, email_verified_at, password, phone, google_id, avatar, username, security_code, admin_role, remember_token, created_at, updated_at) '
                    .'SELECT id, fullname, email, email_verified_at, password, phone, google_id, avatar, username, security_code, '
                    .'CASE WHEN admin_role THEN 2 ELSE 0 END, remember_token, created_at, updated_at FROM "Users"'
                );

                Schema::drop('Users');
                Schema::rename('Users_new', 'Users');
            });

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('UPDATE `Users` SET `admin_role` = CASE WHEN `admin_role` <> 0 THEN 2 ELSE 0 END');
            DB::statement('ALTER TABLE `Users` MODIFY `admin_role` INT NOT NULL DEFAULT 0');

            return;
        }

        DB::statement('ALTER TABLE "Users" ALTER COLUMN admin_role DROP DEFAULT');
        DB::statement('ALTER TABLE "Users" ALTER COLUMN admin_role TYPE INTEGER USING (CASE WHEN admin_role THEN 2 ELSE 0 END)');
        DB::statement('ALTER TABLE "Users" ALTER COLUMN admin_role SET DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::transaction(function () {
                // Recreate original table with boolean admin_role default false
                Schema::create('Users_old', function (Blueprint $table) {
                    $table->id();
                    $table->string('fullname');
                    $table->string('email')->unique();
                    $table->timestamp('email_verified_at')->nullable();
                    $table->string('password');
                    $table->string('phone')->nullable();
                    $table->string('google_id')->nullable();
                    $table->string('avatar')->nullable();
                    $table->string('username')->nullable();
                    $table->string('security_code')->nullable();
                    $table->boolean('admin_role')->default(false);
                    $table->rememberToken();
                    $table->timestamps();
                });

                // Map integer back to boolean: 2 -> true, else false
                DB::statement(
                    'INSERT INTO "Users_old" (id, fullname, email, email_verified_at, password, phone, google_id, avatar, username, security_code, admin_role, remember_token, created_at, updated_at) '
                    .'SELECT id, fullname, email, email_verified_at, password, phone, google_id, avatar, username, security_code, '
                    .'CASE WHEN admin_role = 2 THEN 1 ELSE 0 END, remember_token, created_at, updated_at FROM "Users"'
                );

                Schema::drop('Users');
                Schema::rename('Users_old', 'Users');
            });

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('UPDATE `Users` SET `admin_role` = CASE WHEN `admin_role` = 2 THEN 1 ELSE 0 END');
            DB::statement('ALTER TABLE `Users` MODIFY `admin_role` TINYINT(1) NOT NULL DEFAULT 0');

            return;
        }

        DB::statement('ALTER TABLE "Users" ALTER COLUMN admin_role DROP DEFAULT');
        DB::statement('ALTER TABLE "Users" ALTER COLUMN admin_role TYPE BOOLEAN USING (CASE WHEN admin_role = 2 THEN true ELSE false END)');
        DB::statement('ALTER TABLE "Users" ALTER COLUMN admin_role SET DEFAULT false');
    }
};
