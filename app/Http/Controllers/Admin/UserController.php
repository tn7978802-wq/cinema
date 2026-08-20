<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $hourExpression = match (DB::connection()->getDriverName()) {
            'pgsql' => 'EXTRACT(HOUR FROM showtimes.start_time)',
            'mysql', 'mariadb' => 'HOUR(showtimes.start_time)',
            default => "CAST(strftime('%H', showtimes.start_time) AS INTEGER)",
        };

        $users = User::query()
            ->addSelect([
                'total_spent' => DB::table('tickets')
                    ->whereColumn('user_id', 'Users.id')
                    ->selectRaw('COALESCE(SUM(total_price), 0)'),
                'favorite_genre' => DB::table('tickets')
                    ->join('showtimes', 'tickets.showtime_id', '=', 'showtimes.id')
                    ->join('movies', 'showtimes.movie_id', '=', 'movies.id')
                    ->whereColumn('tickets.user_id', 'Users.id')
                    ->whereNotNull('movies.genre')
                    ->select('movies.genre')
                    ->groupBy('movies.genre')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(1),
                'favorite_hour' => DB::table('tickets')
                    ->join('showtimes', 'tickets.showtime_id', '=', 'showtimes.id')
                    ->whereColumn('tickets.user_id', 'Users.id')
                    ->selectRaw($hourExpression)
                    ->groupByRaw($hourExpression)
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(1),
            ])
            ->paginate(10);

        $users->getCollection()->transform(function ($user) {
            // Format favorite time label
            if ($user->favorite_hour !== null) {
                $h = (int) $user->favorite_hour;
                if ($h >= 5 && $h < 12) {
                    $user->favorite_time = 'Sáng (' . sprintf('%02d:00', $h) . ')';
                } elseif ($h >= 12 && $h < 18) {
                    $user->favorite_time = 'Chiều (' . sprintf('%02d:00', $h) . ')';
                } else {
                    $user->favorite_time = 'Tối (' . sprintf('%02d:00', $h) . ')';
                }
            } else {
                $user->favorite_time = 'Chưa có dữ liệu';
            }

            $user->favorite_genre = $user->favorite_genre ?? 'Chưa có dữ liệu';
            
            return $user;
        });

        return view('admin.users.index', [
            'users' => $users,
            'activeTab' => 'management',
            'pageTitle' => 'Quản lý Khách Hàng'
        ]);
    }

    public function update(Request $request, User $user)
    {
        $actor = auth()->user();

        if (! $actor?->isAdmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Bạn không có quyền thực hiện hành động này!');
        }

        $request->validate([
            'admin_role' => 'required|integer|in:0,1,2,3',
        ]);

        $newRole = (int) $request->input('admin_role');

        if (! $actor->isSystemOwner() && $newRole > User::ROLE_CLIENT) {
            return redirect()->route('admin.users.index')->with('error', 'Chỉ Super Admin mới được cấp quyền Admin hoặc Super Admin.');
        }

        $user->admin_role = $newRole;
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Đã cập nhật quyền truy cập cho ' . ($user->name ?? $user->username ?? 'người dùng'));
    }
}
