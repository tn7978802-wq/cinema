@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-white mb-4">Góp Ý & Hỗ Trợ</h1>
        <p class="text-xl text-gray-400 max-w-2xl mx-auto">
            Chúng tôi luôn lắng nghe ý kiến của bạn để cải thiện dịch vụ. Vui lòng để lại thông tin hoặc yêu cầu hỗ trợ bên dưới.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 bg-gray-800 p-8 rounded-2xl border border-gray-700 shadow-xl">
        <!-- Contact Info -->
        <div class="space-y-8">
            <div>
                <h3 class="text-2xl font-bold text-white mb-6 border-l-4 border-red-500 pl-3">Thông Tin Liên Hệ</h3>
                <ul class="space-y-6 text-gray-300">
                    <li class="flex items-start">
                        <i class="fa-solid fa-map-location-dot text-red-500 text-xl mt-1 mr-4 w-6 text-center"></i>
                        <div>
                            <strong class="block text-white">Văn Phòng Chính</strong>
                            21 đường số 13,Khu phố 4,phường Hiêp Bình ,Thủ Đức, TP.HCM
                        </div>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-phone text-red-500 text-xl mt-1 mr-4 w-6 text-center"></i>
                        <div>
                            <strong class="block text-white">Hotline Hỗ Trợ</strong>
                            0943980066 (24/24)
                        </div>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-envelope text-red-500 text-xl mt-1 mr-4 w-6 text-center"></i>
                        <div>
                            <strong class="block text-white">Email CSKH</strong>
                            info@jga.com.vn
                        </div>
                    </li>
                </ul>
            </div>
            
            <div class="p-6 bg-gray-900 rounded-xl border border-gray-700">
                <h4 class="text-lg font-bold text-white mb-2"><i class="fa-solid fa-circle-info text-blue-500 mr-2"></i> Câu Hỏi Thường Gặp</h4>
                <p class="text-sm text-gray-400 mb-4">Bạn có thắc mắc về cách đặt vé hoặc hoàn tiền? Hãy xem các câu trả lời nhanh tại đây.</p>
                <a href="#" class="text-blue-500 hover:text-blue-400 font-medium text-sm flex items-center">
                    Xem Câu Hỏi Thường Gặp <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Feedback Form -->
        <div>
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('feedback') }}" method="POST" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Họ Tên</label>
                        <input type="text" id="name" name="name" value="{{ Auth::user()->name ?? '' }}" required class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                        <input type="email" id="email" name="email" value="{{ Auth::user()->email ?? '' }}" required class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                    </div>
                </div>
                
                <div>
                    <label for="topic" class="block text-sm font-medium text-gray-300 mb-1">Chủ đề</label>
                    <select id="topic" name="topic" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 appearance-none">
                        <option value="Hỗ trợ đặt vé / Thanh toán">Hỗ trợ đặt vé / Thanh toán</option>
                        <option value="Góp ý dịch vụ rạp">Góp ý dịch vụ rạp</option>
                        <option value="Báo lỗi website/ứng dụng">Báo lỗi website/ứng dụng</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-300 mb-1">Nội dung</label>
                    <textarea id="message" name="message" rows="5" required class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 resize-none" placeholder="Vui lòng mô tả chi tiết vấn đề hoặc góp ý của bạn..."></textarea>
                </div>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition-colors flex justify-center items-center">
                    <i class="fa-regular fa-paper-plane mr-2"></i> GỬI PHẢN HỒI
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
