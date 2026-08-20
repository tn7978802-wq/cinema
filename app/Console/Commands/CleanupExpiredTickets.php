<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-expired-tickets';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Xóa các vé đang chờ thanh toán nhưng đã quá 10 phút';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiryTime = now()->subMinutes(10);

        // Lấy danh sách ticket_id để log (nếu cần) và xóa
        $expiredTickets = DB::table('tickets')
            ->where('status', 'pending')
            ->where('created_at', '<', $expiryTime)
            ->get();

        if ($expiredTickets->isEmpty()) {
            return;
        }

        $ticketIds = $expiredTickets->pluck('id')->toArray();

        DB::transaction(function () use ($ticketIds) {
            // Xóa ticket_details trước (nếu không có cascade delete ở database)
            DB::table('ticket_details')->whereIn('ticket_id', $ticketIds)->delete();
            
            // Xóa voucher_usages liên quan (nếu có)
            DB::table('voucher_usages')->whereIn('ticket_id', $ticketIds)->delete();

            // Xóa payment_logs (tùy chọn, thường nên giữ log nhưng ở đây ta xóa vé lỗi/hết hạn)
            // DB::table('payment_logs')->whereIn('ticket_id', $ticketIds)->delete();

            // Cuối cùng xóa tickets
            DB::table('tickets')->whereIn('id', $ticketIds)->delete();
        });

        $count = count($ticketIds);
        $this->info("đã xóa {$count} vé hết hạn.");
        Log::info("CleanupExpiredTickets: Removed {$count} expired pending tickets.", ['ids' => $ticketIds]);
    }
}
