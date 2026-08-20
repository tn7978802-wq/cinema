<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\User;
use App\Models\Cinema;
use App\Models\Room;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'fullname' => 'Test User',
                'password' => bcrypt('password'),
                'phone' => '0987654321',
            ]
        );

        // Movie::truncate(); // PostgreSQL khong cho truncate neu co khoa ngoai

        $movies = [];

        $movies['Avengers: Secret Wars'] = Movie::updateOrCreate(
            ['name' => 'Avengers: Secret Wars'],
            [
                'genre' => 'Hành Động, Viễn Tưởng',
                'duration' => 150,
                'release_date' => now(),
                'director' => 'Joe Russo, Anthony Russo',
                'description' => 'Siêu anh hùng phải hợp lực để đối đầu với mối đe doạ xuyên vũ trụ.',
                'poster' => '/images/pic1.jpg',
                'actors' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo',
                'age_limit' => 18,
                'trailer_link' => 'https://www.youtube.com/watch?v=6ZfuNTqbHE8',
            ]
        );

        $movies['The Dark Knight'] = Movie::updateOrCreate(
            ['name' => 'The Dark Knight'],
            [
                'genre' => 'Hành Động, Giật Gân',
                'duration' => 152,
                'release_date' => now(),
                'director' => 'Christopher Nolan',
                'description' => 'Batman đối đầu với Joker trong một trận chiến tâm lý đầy kịch tính.',
                'poster' => '/images/batmanpic.jpg',
                'actors' => 'Christian Bale, Heath Ledger, Aaron Eckhart',
                'age_limit' => 16,
                'trailer_link' => 'https://www.youtube.com/watch?v=example2',
            ]
        );

        $movies['Spider-Man: No Way Home'] = Movie::updateOrCreate(
            ['name' => 'Spider-Man: No Way Home'],
            [
                'genre' => 'Hành Động, Phiêu Lưu',
                'duration' => 148,
                'release_date' => now(),
                'director' => 'Jon Watts',
                'description' => 'Peter Parker phải đối mặt với hậu quả khi danh tính bị bại lộ.',
                'poster' => '/images/pic3nowayhome.jpg',
                'actors' => 'Tom Holland, Zendaya, Benedict Cumberbatch',
                'age_limit' => 13,
                'trailer_link' => 'https://www.youtube.com/watch?v=example3',
            ]
        );

        $movies['Dune: Part Two'] = Movie::updateOrCreate(
            ['name' => 'Dune: Part Two'],
            [
                'genre' => 'Khoa Học Viễn Tưởng, Phiêu Lưu',
                'duration' => 165,
                'release_date' => now(),
                'director' => 'Denis Villeneuve',
                'description' => 'Hành trình của Paul Atreides tiếp tục trong sa mạc hành tinh Arrakis.',
                'poster' => '/images/pic2dune.jpg',
                'actors' => 'Timothée Chalamet, Zendaya, Rebecca Ferguson',
                'age_limit' => 18,
                'trailer_link' => 'https://www.youtube.com/watch?v=example4',
            ]
        );

        // Seed Cinemas
       $cinemasData = [
            [
                'name'     => 'CineBook Landmark 81',
                'address'  => 'Tầng B1, Vincom Center Landmark 81, 772 Điện Biên Phủ',
                'district' => 'Bình Thạnh',
                'phone'    => '123456',
                'hours'    => '08:00 – 24:00',
                'screens'  => 12,
                'seats'    => 1800,
                'features' => ['IMAX', 'Dolby', 'VIP'],
                'image'    => '/images/unnamed.jpg',
                'map'      => 'https://maps.app.goo.gl/wVHQdGW3qttfNCdG6',
                'status'   => 'open',
            ],
            [
                'name'     => 'CineBook Aeon Tân Phú',
                'address'  => 'Tầng 3, Aeon Mall Tân Phú Celadon, 30 Bờ Bao Tân Thắng',
                'district' => 'Tân Phú',
                'phone'    => '123456',
                'hours'    => '09:00 – 23:00',
                'screens'  => 8,
                'seats'    => 1200,
                'features' => ['IMAX', 'VIP'],
                'image'    => '/images/aontanphu.jpg',
                'map'      => 'https://maps.app.goo.gl/e9dVD3wvPcT6nkoSA',
                'status'   => 'open',
            ],
            [
                'name'     => 'CineBook Sư Vạn Hạnh',
                'address'  => 'Tầng 6, Vạn Hạnh Mall, 11 Sư Vạn Hạnh',
                'district' => 'Quận 10',
                'phone'    => '123456',
                'hours'    => '09:00 – 23:30',
                'screens'  => 8,
                'seats'    => 1200,
                'features' => ['IMAX', 'VIP'],
                'image'    => '/images/suvanhanh.jpg',
                'map'      => 'https://maps.app.goo.gl/e9dVD3wvPcT6nkoSA',
                'status'   => 'open',
            ],
            [
                'name'     => 'CineBook Giga Mall',
                'address'  => 'Tầng 6,  ',
                'district' => 'Thủ Đức',
                'phone'    => '123456',
                'hours'    => '09:00 – 23:00',
                'screens'  => 6,
                'seats'    => 900,
                'features' => ['4DX', 'Dolby'],
                'image'    => '/images/Gigamall.jpg',
                'map'      => 'https://maps.app.goo.gl/2kzHNRUrFb1uBAYM6',
                'status'   => 'open',
            ],
            [
                'name'     => 'CineBook Quận 7',
                'address'  => 'Tầng 4, SC VivoCity, 1058 Nguyễn Văn Linh',
                'district' => 'Quận 7',
                'phone'    => '123456',
                'hours'    => '09:00 – 23:30',
                'screens'  => 10,
                'seats'    => 1500,
                'features' => ['Dolby', 'VIP'],
                'image'    => '/images/1058.jpg',
                'map'      => 'https://maps.app.goo.gl/5NSThhp1CqzywyKE6',
                'status'   => 'open',
            ],
            [
                'name'     => 'CineBook Thủ Đức',
                'address'  => 'Tầng 3, Vincom Plaza, Võ Văn Ngân',
                'district' => 'Thủ Đức',
                'phone'    => '123456',
                'hours'    => '09:00 – 22:30',
                'screens'  => 5,
                'seats'    => 750,
                'features' => ['IMAX', '4DX', 'Dolby', 'VIP'],
                'image'    => '/images/thuduc.jpg',
                'map'      => 'https://maps.app.goo.gl/dgJ4JX3nKsVRA5SBA',
                'status'   => 'open',
            ],
        ];
 
        $cinemas = [];
        foreach ($cinemasData as $data) {
            // store features as array (Eloquent will JSON encode via cast)
            $cinemas[$data['name']] = Cinema::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'features' => $data['features'],
                ])
            );
        }
 
        // ===================== SUBTITLES =====================
        $subtitle = \App\Models\Subtitle::updateOrCreate(['name' => 'Phụ Đề Tiếng Việt']);
 
        // ===================== ROOMS (mỗi rạp 2 phòng) =====================
        $rooms = [];
        foreach ($cinemas as $cinemaName => $cinema) {
            $rooms[$cinemaName] = [
                Room::updateOrCreate(
                    ['name' => 'Phòng 1', 'cinema_id' => $cinema->id],
                    ['seat_count' => 100]
                ),
                Room::updateOrCreate(
                    ['name' => 'Phòng 2', 'cinema_id' => $cinema->id],
                    ['seat_count' => 80]
                ),
            ];
        }
 
        // ===================== SHOWTIMES =====================
        Showtime::query()->delete();
 
        // Lịch chiếu theo phim ngày → rạp → giờ
        $schedule = [
            'Avengers: Secret Wars' => [
                '2026-08-19' => [
                    'CineBook Landmark 81'  => ['09:00', '12:00', '15:30', '19:00', '22:00'],
                    'CineBook Aeon Tân Phú' => ['11:00', '14:30', '18:00', '21:30'],
                    'CineBook Sư Vạn Hạnh' => ['10:00', '13:30', '17:00', '20:30'],
                ],
                '2026-08-20' => [
                    'CineBook Aeon Tân Phú' => ['11:00', '14:30', '18:00', '21:30'],
                    'CineBook Quận 7' => ['10:00', '13:30', '17:00', '20:30'],
                ],
            ],

            'The Dark Knight' => [
                '2026-08-19' => [
                    'CineBook Landmark 81'  => ['08:30', '11:30', '14:30', '18:00', '21:00'],
                    'CineBook Thủ Đức'  => ['08:30', '11:30', '14:30', '18:00', '21:00'],
                ],
                '2026-08-20' => [
                    'CineBook Giga Mall'    => ['09:30', '13:00', '16:30', '20:00'],
                    'CineBook Quận 7'    => ['09:30', '13:00', '16:30', '20:00'],
                    
                ],
            ],
            'Spider-Man: No Way Home' => [
                '2026-08-21' => [
                    'CineBook Aeon Tân Phú'  => ['08:30', '11:30', '14:30', '18:00', '21:00'],
                    'CineBook Quận 7'  => ['08:30', '11:30', '14:30', '18:00', '21:00'],
                ],
                '2026-08-18' => [
                    'CineBook Giga Mall'    => ['09:30', '13:00', '16:30', '20:00'],
                    'CineBook Landmark 81'    => ['09:30', '13:00', '16:30', '20:00'],
                    
                ],
            ],
            'Dune: Part Two' => [
                '2026-08-18' => [
                    'CineBook Thủ Đức'  => ['10:30', '14:00', '17:30', '21:00'],
                    'CineBook Giga Mall'    => ['09:00', '12:30', '16:00', '19:30', '22:30'],
                ],
            ],
        ];
 
        foreach ($schedule as $movieName => $dateSchedule) {
            $movie = $movies[$movieName] ?? null;
            if (!$movie) continue;

            foreach ($dateSchedule as $date => $cinemaSchedule) {

                foreach ($cinemaSchedule as $cinemaName => $times) {
                    $cinema = $cinemas[$cinemaName] ?? null;
                    if (!$cinema) continue;

                    $room = $rooms[$cinemaName][0] ?? null;
                    if (!$room) continue;

                    foreach ($times as $time) {
                        $startTime = Carbon::parse($date)->setTimeFromTimeString($time);

                        Showtime::create([
                            'movie_id'    => $movie->id,
                            'room_id'     => $room->id,
                            'subtitle_id' => $subtitle->id,
                            'start_time'  => $startTime,
                        ]);
                    }
                }
            }
        }
    }
}