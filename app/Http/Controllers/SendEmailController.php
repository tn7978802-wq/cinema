<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendEmailController extends Controller
{
    public function send(Request $request)
    {
        // Truyền dữ liệu vào template
        $data = [
            'name' => ' Văn Toàn',
        ];

        // Dùng Mail::send thay vì Mail::raw để gọi template HTML (resources/views/emails/business.blade.php)
        Mail::send('emails.business', $data, function ($message) {
            $message->to('tn7410311@gmail.com') // Có thể thay bằng email sếp muốn test
                ->subject('🎬 [CINEBOOK] Thư chào mừng từ dự án Cinema của sếp Văn Toàn!');
        });

        return 'Đã gửi email HTML thành công rực rỡ! Sếp check inbox ngay nha!';
    }
}
