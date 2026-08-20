<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subtitles')) {
            return;
        }

        foreach ([
            'Phụ Đề Tiếng Việt',
            'Lồng Tiếng Việt',
            'English',
            'Không Phụ Đề',
        ] as $name) {
            DB::table('subtitles')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('subtitles')
            ->whereIn('name', [
                'Lồng Tiếng Việt',
                'English',
                'Không Phụ Đề',
            ])
            ->delete();
    }
};