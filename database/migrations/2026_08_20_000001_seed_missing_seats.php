<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms') || ! Schema::hasTable('seats')) {
            return;
        }

        foreach (DB::table('rooms')->select(['id', 'seat_count'])->get() as $room) {
            $seatsPerRow = $room->seat_count >= 100 ? 13 : 10;
            $existingNames = DB::table('seats')
                ->where('room_id', $room->id)
                ->pluck('seat_name')
                ->map(fn ($name) => strtoupper(trim((string) $name)))
                ->all();

            $existingNames = array_flip($existingNames);
            $rows = [];

            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $row) {
                for ($number = 1; $number <= $seatsPerRow; $number++) {
                    $seatName = $row . $number;

                    if (isset($existingNames[$seatName])) {
                        continue;
                    }

                    $rows[] = [
                        'room_id' => $room->id,
                        'seat_name' => $seatName,
                        'seat_type' => 'standard',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($rows) {
                DB::table('seats')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        // Keep existing seats and ticket references intact on rollback.
    }
};