<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $range = in_array($request->query('range'), ['day', 'week', 'month'], true)
            ? $request->query('range')
            : 'day';

        [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->resolveRangeWindow($range);

        $summary = $this->buildSummaryMetrics($currentStart, $currentEnd, $previousStart, $previousEnd);
        $detail = $this->buildDetailMetrics($range, $currentStart, $currentEnd);
        $activity = $this->buildLiveShowtimes();
        $customers = $this->buildCustomerMetrics($currentStart, $currentEnd, $previousStart, $previousEnd);
        $payments = $this->buildPaymentMetrics($currentStart, $currentEnd, $previousStart, $previousEnd);
        $promo = $this->buildPromoMetrics($currentStart, $currentEnd);
        $quickActions = $this->buildQuickActions();

        return view('admin.home', [
            'activeTab' => 'dashboard',
            'pageTitle' => 'Dashboard',
            'dashboardFilter' => $range,
            'dashboardFilterLabel' => $this->rangeLabel($range),
            'dashboardWindowLabel' => $this->windowLabel($range, $currentStart, $currentEnd),
            'summaryMetrics' => $summary,
            'detailMetrics' => $detail,
            'liveShowtimes' => $activity,
            'customerMetrics' => $customers,
            'paymentMetrics' => $payments,
            'promoMetrics' => $promo,
            'quickActions' => $quickActions,
        ]);
    }

    private function resolveRangeWindow(string $range): array
    {
        $now = now();

        return match ($range) {
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
        };
    }

    private function buildSummaryMetrics(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd): array
    {
        $currentRevenue = $this->ticketRevenue($currentStart, $currentEnd);
        $previousRevenue = $this->ticketRevenue($previousStart, $previousEnd);

        $currentTickets = $this->ticketCount($currentStart, $currentEnd);
        $previousTickets = $this->ticketCount($previousStart, $previousEnd);

        $currentShowtimes = $this->showtimeCount($currentStart, $currentEnd);
        $previousShowtimes = $this->showtimeCount($previousStart, $previousEnd);

        $currentActiveCinemas = $this->activeCinemaCount($currentStart, $currentEnd);
        $previousActiveCinemas = $this->activeCinemaCount($previousStart, $previousEnd);

        return [
            [
                'label' => 'Tổng doanh thu',
                'value' => $this->formatCurrency($currentRevenue),
                'delta' => $this->deltaPayload($currentRevenue, $previousRevenue),
                'icon' => 'fa-sack-dollar',
            ],
            [
                'label' => 'Tổng vé bán',
                'value' => number_format($currentTickets),
                'delta' => $this->deltaPayload($currentTickets, $previousTickets),
                'icon' => 'fa-ticket',
            ],
            [
                'label' => 'Suất chiếu vận hành',
                'value' => number_format($currentShowtimes),
                'delta' => $this->deltaPayload($currentShowtimes, $previousShowtimes),
                'icon' => 'fa-calendar-days',
            ],
            [
                'label' => 'Rạp hoạt động',
                'value' => number_format($currentActiveCinemas),
                'delta' => $this->deltaPayload($currentActiveCinemas, $previousActiveCinemas),
                'icon' => 'fa-building',
            ],
        ];
    }

    private function buildDetailMetrics(string $range, Carbon $currentStart, Carbon $currentEnd): array
    {
        return [
            'revenue_chart' => $this->revenueSeries($range, $currentStart, $currentEnd),
            'top_movies' => $this->topMovies($currentStart, $currentEnd),
            'top_cinemas' => $this->topCinemas($currentStart, $currentEnd),
        ];
    }

    private function buildLiveShowtimes(): Collection
    {
        $driver = DB::connection()->getDriverName();
        $showtimeEndExpression = match ($driver) {
            'pgsql' => 'st.start_time + make_interval(mins => COALESCE(m.duration, 120))',
            'mysql', 'mariadb' => 'DATE_ADD(st.start_time, INTERVAL COALESCE(m.duration, 120) MINUTE)',
            default => "datetime(st.start_time, '+' || COALESCE(m.duration, 120) || ' minutes')",
        };

        $rows = DB::table('showtimes as st')
            ->join('movies as m', 'm.id', '=', 'st.movie_id')
            ->join('rooms as r', 'r.id', '=', 'st.room_id')
            ->join('cinemas as c', 'c.id', '=', 'r.cinema_id')
            ->leftJoin('tickets as t', 't.showtime_id', '=', 'st.id')
            ->leftJoin('ticket_details as td', 'td.ticket_id', '=', 't.id')
            ->where('st.start_time', '<=', now())
            ->whereRaw($showtimeEndExpression.' >= ?', [now()])
            ->groupBy('st.id', 'st.start_time', 'm.name', 'm.duration', 'r.name', 'r.seat_count', 'c.name')
            ->orderBy('st.start_time')
            ->selectRaw('
                st.id,
                st.start_time,
                m.name as movie_name,
                m.duration,
                r.name as room_name,
                r.seat_count,
                c.name as cinema_name,
                COUNT(td.id) as sold_seats
            ')
            ->limit(8)
            ->get();

        return $rows->map(function ($row) {
            $seatCount = max((int) $row->seat_count, 1);
            $soldSeats = (int) $row->sold_seats;
            $fillRate = round(($soldSeats / $seatCount) * 100, 1);

            return (object) [
                'movie_name' => $row->movie_name,
                'cinema_name' => $row->cinema_name,
                'room_name' => $row->room_name,
                'start_time' => Carbon::parse($row->start_time),
                'end_time' => Carbon::parse($row->start_time)->addMinutes((int) ($row->duration ?: 120)),
                'sold_seats' => $soldSeats,
                'seat_count' => $seatCount,
                'fill_rate' => $fillRate,
            ];
        });
    }

    private function buildCustomerMetrics(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd): array
    {
        $currentNewUsers = $this->newUsersCount($currentStart, $currentEnd);
        $previousNewUsers = $this->newUsersCount($previousStart, $previousEnd);

        $currentRepeatRate = $this->repeatCustomerRate($currentStart, $currentEnd);
        $previousRepeatRate = $this->repeatCustomerRate($previousStart, $previousEnd);

        $popularBookingHour = $this->popularBookingHour($currentStart, $currentEnd);
        $hourDistribution = $this->bookingHourDistribution($currentStart, $currentEnd);

        return [
            'new_users' => [
                'label' => 'Khách hàng mới đăng ký',
                'value' => number_format($currentNewUsers),
                'delta' => $this->deltaPayload($currentNewUsers, $previousNewUsers),
            ],
            'turnover_rate' => [
                'label' => 'Turn over rate',
                'value' => number_format($currentRepeatRate, 1).'%',
                'subtext' => 'Tỷ lệ khách quay lại mua vé trong kỳ.',
                'delta' => $this->deltaPayload($currentRepeatRate, $previousRepeatRate),
            ],
            'popular_booking_hour' => [
                'label' => 'Giờ đặt vé phổ biến',
                'value' => $popularBookingHour['label'],
                'subtext' => $popularBookingHour['tickets'].' lượt đặt vé trong khung giờ này.',
            ],
            'hour_distribution' => $hourDistribution,
        ];
    }

    private function buildPaymentMetrics(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd): array
    {
        $currentSuccess = $this->paymentLogCount('success', $currentStart, $currentEnd);
        $previousSuccess = $this->paymentLogCount('success', $previousStart, $previousEnd);
        $currentFailed = $this->paymentLogCount('failed', $currentStart, $currentEnd);
        $previousFailed = $this->paymentLogCount('failed', $previousStart, $previousEnd);
        $currentTotal = max($currentSuccess + $currentFailed, 1);

        return [
            'success' => [
                'label' => 'Thanh toán thành công',
                'value' => number_format($currentSuccess),
                'rate' => round(($currentSuccess / $currentTotal) * 100, 1),
                'delta' => $this->deltaPayload($currentSuccess, $previousSuccess),
                'tone' => 'success',
            ],
            'failed' => [
                'label' => 'Thanh toán thất bại',
                'value' => number_format($currentFailed),
                'rate' => 0,
                'delta' => $this->deltaPayload($currentFailed, $previousFailed),
                'tone' => 'danger',
            ],
        ];
    }

    private function buildPromoMetrics(Carbon $currentStart, Carbon $currentEnd): Collection
    {
        $rows = DB::table('voucher_usages as vu')
            ->join('tickets as t', 't.id', '=', 'vu.ticket_id')
            ->whereBetween('vu.used_at', [$currentStart, $currentEnd])
            ->groupBy('vu.voucher_code')
            ->orderByDesc(DB::raw('COUNT(vu.id)'))
            ->selectRaw('
                vu.voucher_code,
                COUNT(vu.id) as usages,
                COALESCE(SUM(vu.discount_amount), 0) as total_discount,
                COALESCE(SUM(COALESCE(t.final_price, t.total_price)), 0) as attributed_revenue
            ')
            ->limit(8)
            ->get();

        $maxUsage = max((int) ($rows->max('usages') ?? 0), 1);

        return $rows->map(function ($row) use ($maxUsage) {
            return (object) [
                'code' => $row->voucher_code,
                'usages' => (int) $row->usages,
                'usage_width' => round(((int) $row->usages / $maxUsage) * 100, 1),
                'discount' => $this->formatCurrency((float) $row->total_discount),
                'revenue' => $this->formatCurrency((float) $row->attributed_revenue),
            ];
        });
    }

    private function buildQuickActions(): array
    {
        return [
            [
                'label' => 'Tạo suất chiếu nhanh',
                'description' => 'UI tạm, chờ merge module tạo nhanh.',
                'icon' => 'fa-calendar-plus',
                'href' => null,
            ],
            [
                'label' => 'Thêm phim',
                'description' => 'UI tạm, chờ flow tạo phim nhanh.',
                'icon' => 'fa-film',
                'href' => null,
            ],
            [
                'label' => 'Tạo voucher',
                'description' => 'Đi tới tab Action để tạo ưu đãi mới.',
                'icon' => 'fa-ticket',
                'href' => route('admin.actions'),
            ],
            [
                'label' => 'Tạo bài đăng',
                'description' => 'Đi tới khu quản lý bài viết.',
                'icon' => 'fa-pen-nib',
                'href' => route('admin.posts.index'),
            ],
        ];
    }

    private function revenueSeries(string $range, Carbon $start, Carbon $end): Collection
    {
        $driver = DB::connection()->getDriverName();
        $bucketExpression = match ($driver) {
            'pgsql' => $range === 'day'
                ? 'EXTRACT(HOUR FROM booking_date)'
                : 'DATE(booking_date)',
            'mysql', 'mariadb' => $range === 'day'
                ? 'HOUR(booking_date)'
                : 'DATE(booking_date)',
            default => $range === 'day'
                ? "CAST(strftime('%H', booking_date) AS INTEGER)"
                : 'date(booking_date)',
        };

        $rows = DB::table('tickets')
            ->whereBetween('booking_date', [$start, $end])
            ->selectRaw($bucketExpression.' as bucket_key, COALESCE(SUM(COALESCE(final_price, total_price)), 0) as revenue')
            ->groupBy('bucket_key')
            ->orderBy('bucket_key')
            ->get()
            ->keyBy(fn ($row) => (string) $row->bucket_key);

        $points = collect();

        if ($range === 'day') {
            foreach (range(0, 23) as $hour) {
                $key = (string) $hour;
                $revenue = (float) ($rows[$key]->revenue ?? 0);
                $points->push((object) [
                    'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                    'value' => $revenue,
                ]);
            }
        } else {
            $cursor = $start->copy();
            while ($cursor <= $end) {
                $key = $cursor->toDateString();
                $revenue = (float) ($rows[$key]->revenue ?? 0);
                $points->push((object) [
                    'label' => $range === 'week' ? ucfirst($cursor->translatedFormat('D')) : $cursor->format('d'),
                    'value' => $revenue,
                ]);
                $cursor->addDay();
            }
        }

        $max = max((float) ($points->max('value') ?? 0), 1);

        return $points->map(fn ($point) => (object) [
            'label' => $point->label,
            'value' => $this->formatCurrency($point->value),
            'height' => max(round(($point->value / $max) * 100, 1), $point->value > 0 ? 12 : 4),
        ]);
    }

    private function topMovies(Carbon $start, Carbon $end): Collection
    {
        $rows = DB::table('ticket_details as td')
            ->join('tickets as t', 't.id', '=', 'td.ticket_id')
            ->join('showtimes as st', 'st.id', '=', 't.showtime_id')
            ->join('movies as m', 'm.id', '=', 'st.movie_id')
            ->whereBetween('t.booking_date', [$start, $end])
            ->groupBy('m.name')
            ->orderByDesc(DB::raw('COUNT(td.id)'))
            ->selectRaw('m.name, COUNT(td.id) as viewers')
            ->limit(5)
            ->get();

        return $this->normalizeTopList($rows, 'viewers');
    }

    private function topCinemas(Carbon $start, Carbon $end): Collection
    {
        $rows = DB::table('ticket_details as td')
            ->join('tickets as t', 't.id', '=', 'td.ticket_id')
            ->join('showtimes as st', 'st.id', '=', 't.showtime_id')
            ->join('rooms as r', 'r.id', '=', 'st.room_id')
            ->join('cinemas as c', 'c.id', '=', 'r.cinema_id')
            ->whereBetween('t.booking_date', [$start, $end])
            ->groupBy('c.name')
            ->orderByDesc(DB::raw('COUNT(td.id)'))
            ->selectRaw('c.name, COUNT(td.id) as viewers')
            ->limit(5)
            ->get();

        return $this->normalizeTopList($rows, 'viewers');
    }

    private function normalizeTopList(Collection $rows, string $metricKey): Collection
    {
        $max = max((int) ($rows->max($metricKey) ?? 0), 1);

        return $rows->values()->map(function ($row, $index) use ($metricKey, $max) {
            $value = (int) $row->{$metricKey};

            return (object) [
                'rank' => $index + 1,
                'label' => $row->name,
                'value' => number_format($value).' lượt',
                'width' => round(($value / $max) * 100, 1),
            ];
        });
    }

    private function bookingHourDistribution(Carbon $start, Carbon $end): Collection
    {
        $hourExpression = match (DB::connection()->getDriverName()) {
            'pgsql' => 'EXTRACT(HOUR FROM booking_date)',
            'mysql', 'mariadb' => 'HOUR(booking_date)',
            default => "CAST(strftime('%H', booking_date) AS INTEGER)",
        };

        $rows = DB::table('tickets')
            ->whereBetween('booking_date', [$start, $end])
            ->selectRaw($hourExpression.' as booking_hour, COUNT(*) as total')
            ->groupBy('booking_hour')
            ->orderBy('booking_hour')
            ->get()
            ->keyBy(fn ($row) => (int) $row->booking_hour);

        $max = max((int) ($rows->max('total') ?? 0), 1);

        return collect(range(8, 23))->map(function ($hour) use ($rows, $max) {
            $total = (int) ($rows[$hour]->total ?? 0);

            return (object) [
                'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).'h',
                'total' => $total,
                'width' => max(round(($total / $max) * 100, 1), $total > 0 ? 10 : 0),
            ];
        });
    }

    private function popularBookingHour(Carbon $start, Carbon $end): array
    {
        $hourExpression = match (DB::connection()->getDriverName()) {
            'pgsql' => 'EXTRACT(HOUR FROM booking_date)',
            'mysql', 'mariadb' => 'HOUR(booking_date)',
            default => "CAST(strftime('%H', booking_date) AS INTEGER)",
        };

        $row = DB::table('tickets')
            ->whereBetween('booking_date', [$start, $end])
            ->selectRaw($hourExpression.' as booking_hour, COUNT(*) as total')
            ->groupBy('booking_hour')
            ->orderByDesc('total')
            ->orderBy('booking_hour')
            ->first();

        if (! $row) {
            return [
                'label' => '--:--',
                'tickets' => 0,
            ];
        }

        $hour = (int) $row->booking_hour;

        return [
            'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00 - '.str_pad((string) (($hour + 1) % 24), 2, '0', STR_PAD_LEFT).':00',
            'tickets' => (int) $row->total,
        ];
    }

    private function repeatCustomerRate(Carbon $start, Carbon $end): float
    {
        $buyers = DB::table('tickets')
            ->whereBetween('booking_date', [$start, $end])
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($buyers->isEmpty()) {
            return 0.0;
        }

        $repeatBuyers = DB::table('tickets')
            ->whereIn('user_id', $buyers)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->select('user_id')
            ->get()
            ->count();

        return round(($repeatBuyers / $buyers->count()) * 100, 1);
    }

    private function ticketRevenue(Carbon $start, Carbon $end): float
    {
        return (float) DB::table('tickets')
            ->whereBetween('booking_date', [$start, $end])
            ->selectRaw('COALESCE(SUM(COALESCE(final_price, total_price)), 0) as aggregate')
            ->value('aggregate');
    }

    private function ticketCount(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('tickets')
            ->whereBetween('booking_date', [$start, $end])
            ->count();
    }

    private function showtimeCount(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('showtimes')
            ->whereBetween('start_time', [$start, $end])
            ->count();
    }

    private function activeCinemaCount(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('showtimes as st')
            ->join('rooms as r', 'r.id', '=', 'st.room_id')
            ->whereBetween('st.start_time', [$start, $end])
            ->distinct('r.cinema_id')
            ->count('r.cinema_id');
    }

    private function newUsersCount(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('Users')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function paymentLogCount(string $status, Carbon $start, Carbon $end): int
    {
        try {
            return (int) DB::table('payment_logs')
                ->where('status', $status)
                ->whereBetween('attempted_at', [$start, $end])
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function deltaPayload(float|int $current, float|int $previous): array
    {
        if ((float) $previous === 0.0) {
            if ((float) $current === 0.0) {
                return ['text' => '0%', 'tone' => 'neutral', 'value' => 0.0];
            }

            return ['text' => '+100%', 'tone' => 'up', 'value' => 100.0];
        }

        $delta = round((($current - $previous) / $previous) * 100, 1);

        return [
            'text' => ($delta > 0 ? '+' : '').number_format($delta, 1).'%',
            'tone' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
            'value' => $delta,
        ];
    }

    private function formatCurrency(float|int $amount): string
    {
        return number_format((float) $amount, 0, ',', '.').'đ';
    }

    private function rangeLabel(string $range): string
    {
        return match ($range) {
            'week' => 'Tuần',
            'month' => 'Tháng',
            default => 'Ngày',
        };
    }

    private function windowLabel(string $range, CarbonInterface $start, CarbonInterface $end): string
    {
        return match ($range) {
            'week' => 'Tuần '.$start->format('d/m').' - '.$end->format('d/m/Y'),
            'month' => 'Tháng '.$start->format('m/Y'),
            default => $start->format('d/m/Y'),
        };
    }
}
