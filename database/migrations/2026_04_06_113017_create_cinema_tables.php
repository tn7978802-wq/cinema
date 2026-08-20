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
        Schema::create('cinemas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('phone')->nullable();
            $table->string('hours')->nullable();
            $table->integer('screens')->default(0);
            $table->integer('seats')->default(0);
            // Lưu dạng JSON: ["IMAX","4DX","Dolby","VIP"]
            $table->json('features')->nullable();
            $table->string('image')->nullable();
            $table->string('map')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('subtitles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('genre')->nullable();
            $table->integer('duration')->nullable();
            $table->date('release_date')->nullable();
            $table->string('director')->nullable();
            $table->text('description')->nullable();
            $table->string('poster')->nullable();
            $table->text('actors')->nullable();
            $table->integer('age_limit')->nullable();
            $table->string('trailer_link')->nullable();
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('cinema_id')->constrained('cinemas');
            $table->integer('seat_count')->default(0);
            $table->timestamps();
        });

        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms');
            $table->string('seat_name');
            $table->string('seat_type')->default('standard');
            $table->timestamps();
        });

        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies');
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('subtitle_id')->constrained('subtitles');
            $table->dateTime('start_time');
            $table->timestamps();
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('Users');
            $table->foreignId('movie_id')->constrained('movies');
            $table->integer('rate');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('Users');
            $table->string('title');
            $table->text('context');
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('Users');
            $table->foreignId('showtime_id')->constrained('showtimes');
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('booking_date')->useCurrent();
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('ticket_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets');
            $table->foreignId('seat_id')->constrained('seats');
            $table->decimal('price_at_booking', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_details');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('showtimes');
        Schema::dropIfExists('seats');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('movies');
        Schema::dropIfExists('subtitles');
        Schema::dropIfExists('cinemas');
        Schema::table('cinemas', function (Blueprint $table) {
            $table->dropColumn([
                'district', 'phone', 'hours', 'screens',
                'seats', 'features', 'image', 'map', 'status',
            ]);
        });
    }
};
