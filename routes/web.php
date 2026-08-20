<?php

use App\Http\Controllers\SendEmailController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CinemaController;
use App\Models\Setting;
use App\Models\Feedback;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PostController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ReviewController;

Route::patch('/admin/posts/{id}/toggle', [PostController::class, 'toggle'])
    ->name('admin.posts.toggle');
Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('posts', PostController::class);
});
Route::post('/upload-image', [App\Http\Controllers\PostController::class, 'uploadImage'])
    ->name('upload.image');

// Reviews
Route::get('/movies/{movie}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('/movies/{movie}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
Route::get('/sendEmail', [SendEmailController::class, 'send'])->name('sendEmail');

// Trang chủ
Route::get('/', [MovieController::class, 'index'])->name('home');
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['vi', 'en'], true), 404);
    session(['locale' => $locale]);

    return back();
})->name('language.switch');

// Auth
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/system-owner-portal', [AuthController::class, 'showSystemOwnerLoginForm'])->name('system-owner.portal');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google Socialite Login Routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Movies
Route::get('/movies/suggestions', [MovieController::class, 'suggestions'])->name('movies.suggestions');
Route::get('/movies', [MovieController::class, 'list'])->name('movies.index');
Route::get('/movies/{id}', [MovieController::class, 'show'])->name('movies.show');

// Cinemas
Route::get('/cinemas', [CinemaController::class, 'theaters'])->name('cinemas.index');
Route::get('/cinemas/{id}', [CinemaController::class, 'show'])->name('cinemas.show');

// Booking
Route::get('/booking/{id}', [BookingController::class, 'show'])->name('booking.show');
Route::post('/booking/{id}/checkout', [BookingController::class, 'checkout'])->name('booking.checkout');
Route::get('/vnpay-return', [BookingController::class, 'vnpayReturn'])->name('vnpay.return');

// Feedback
Route::get('/feedback', function () {
    return view('feedback');
})->name('feedback');

Route::post('/feedback', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return back()->with('error', 'Vui lòng đăng nhập để gửi phản hồi!');
    }

    \App\Models\Feedback::create([
        'user_id' => Auth::id(),
        'title' => $request->topic,
        'context' => $request->message,
    ]);

    return back()->with('success', 'Cảm ơn sếp đã gửi phản hồi nha! Chúng tôi sẽ xem xét sớm nhất.');
});

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::post('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/account/password/otp', [AccountController::class, 'showPasswordOtpForm'])->name('account.password.otp');
    Route::post('/account/password/otp', [AccountController::class, 'verifyPasswordOtp'])->name('account.password.otp.verify');
    Route::get('/account/password/resend-otp', [AccountController::class, 'resendPasswordOtp'])->name('account.password.otp.resend');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read/{notification}', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

// OTP Routes
Route::get('/otp/send', [OtpController::class, 'sendOtp'])->name('otp.send');
Route::get('/otp/verify', [OtpController::class, 'showVerifyForm'])->name('otp.form');
Route::post('/otp/verify', [OtpController::class, 'verifyOtp'])->name('otp.verify');

$adminTabs = [
    'dashboard' => 'Dashboard',
    'management' => 'Quản lý',
    'posts' => 'Bài viết',
    'actions' => 'Action',
    'feedback' => 'Ý kiến phản hồi',
    'reviews' => 'Đánh giá phim',
];

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () use ($adminTabs) {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/management', function () use ($adminTabs) {
        return view('admin.home', [
            'activeTab' => 'management',
            'pageTitle' => $adminTabs['management'],
            'adminTabs' => $adminTabs,
        ]);
    })->name('management');

    Route::get('/actions', [VoucherController::class, 'index'])->name('actions');
    Route::post('/actions/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::put('/actions/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/actions/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
    Route::post('/posts/upload-thumbnail', [App\Http\Controllers\Admin\PostController::class, 'uploadThumbnail'])->name('posts.upload-thumbnail');

    Route::get('/feedback', function () use ($adminTabs) {
        $feedbacks = \App\Models\Feedback::with('user')->latest()->get();
        return view('admin.home', [
            'activeTab' => 'feedback',
            'pageTitle' => $adminTabs['feedback'],
            'adminTabs' => $adminTabs,
            'feedbacks' => $feedbacks,
        ]);
    })->name('feedback');

    Route::post('/feedback/{feedback}/reply', function (\Illuminate\Http\Request $request, \App\Models\Feedback $feedback) {
        $request->validate(['reply_message' => 'required|string']);

        if ($feedback->user && $feedback->user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($feedback->user->email)->send(
                    new \App\Mail\FeedbackReplyMail($feedback->title, $request->reply_message)
                );
                return back()->with('success', 'Đã gửi email phản hồi thành công đến ' . $feedback->user->email);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Mail Error: ' . $e->getMessage());
                return back()->with('error', 'Lỗi gửi email: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Người dùng này không có địa chỉ email hợp lệ.');
    })->name('feedback.reply');

    Route::delete('/feedback/{feedback}', function (\App\Models\Feedback $feedback) {
        $feedback->delete();
        return back()->with('success', 'Đã xóa phản hồi thành công.');
    })->name('feedback.destroy');

    // Admin Management Resources
    Route::resource('movies', App\Http\Controllers\Admin\MovieController::class);
    Route::resource('reviews', App\Http\Controllers\Admin\ReviewController::class)->only(['index', 'destroy']);
    Route::resource('cinemas', App\Http\Controllers\Admin\CinemaController::class);
    Route::resource('showtimes', App\Http\Controllers\Admin\ShowtimeController::class);
    Route::resource('tickets', App\Http\Controllers\Admin\TicketController::class);
    Route::get('/tickets-export', [App\Http\Controllers\Admin\TicketController::class, 'exportCsv'])->name('tickets.export');

    Route::resource('posts', App\Http\Controllers\Admin\PostController::class);

    // Dành riêng cho Quản trị viên cấp cao (System Owner)
    Route::middleware(['system_owner'])->group(function () {
        Route::resource('users', App\Http\Controllers\Admin\UserController::class);
        Route::get('/system-owner', [App\Http\Controllers\Admin\SystemOwnerController::class, 'index'])->name('system-owner.index');
        Route::put('/system-owner/users/{user}/role', [App\Http\Controllers\Admin\SystemOwnerController::class, 'updateRole'])->name('system-owner.update-role');
        
        // Quản lý Sub-Owners (Chỉ dành cho Root Owner)
        Route::post('/system-owner/sub-owners', [App\Http\Controllers\Admin\SystemOwnerController::class, 'addSubOwner'])->name('system-owner.sub-owners.store');
        Route::delete('/system-owner/sub-owners/{subOwner}', [App\Http\Controllers\Admin\SystemOwnerController::class, 'removeSubOwner'])
            ->name('system-owner.sub-owners.destroy');
    });
});
