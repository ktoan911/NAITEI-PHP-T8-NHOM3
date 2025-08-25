# 📊 Hệ Thống Báo Cáo Doanh Thu Hàng Ngày

Hệ thống cronjob tự động gửi báo cáo doanh thu chi tiết qua email cho admin mỗi ngày.

## 🚀 Tính Năng

- ✅ Tự động tạo báo cáo doanh thu hàng ngày
- ✅ Gửi email báo cáo cho tất cả admin
- ✅ Giao diện web để xem báo cáo trực tuyến  
- ✅ Thống kê chi tiết: doanh thu, đơn hàng, khách hàng mới
- ✅ Top sản phẩm bán chạy
- ✅ Doanh thu theo giờ
- ✅ So sánh với ngày hôm trước
- ✅ Template email đẹp mắt và responsive

## 📦 Các File Đã Tạo

### Backend (Laravel)
- `app/Console/Commands/DailyRevenueReport.php` - Command tạo báo cáo
- `app/Mail/DailyRevenueReport.php` - Mailable class gửi email
- `app/Http/Controllers/RevenueReportController.php` - Controller cho API
- `app/Console/Kernel.php` - Đăng ký scheduler
- `resources/views/emails/daily-revenue-report.blade.php` - Template email

### Frontend (React)
- `resources/js/pages/RevenueReport.jsx` - Giao diện xem báo cáo

### Scripts & Config
- `setup-cronjob.sh` - Script thiết lập cronjob tự động
- Routes đã được thêm vào `routes/web.php`

## 🛠️ Cài Đặt & Cấu Hình

### 1. Cấu Hình Email (.env)

```env
# Cấu hình SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Báo cáo doanh thu"
```

### 2. Thiết Lập Cronjob

#### Tự động (Khuyến nghị):
```bash
chmod +x setup-cronjob.sh
./setup-cronjob.sh
```

#### Thủ công:
```bash
# Mở crontab
crontab -e

# Thêm dòng sau (chạy scheduler Laravel mỗi phút)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Tạo Admin User

Đảm bảo có ít nhất 1 user với role 'admin' trong database:

```sql
UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
```

## 📋 Cách Sử Dụng

### 1. Command Line

```bash
# Chạy báo cáo cho ngày hôm qua
php artisan report:daily-revenue

# Chạy báo cáo cho ngày cụ thể
php artisan report:daily-revenue 2024-01-15

# Kiểm tra scheduler
php artisan schedule:list

# Chạy scheduler thủ công (test)
php artisan schedule:run
```

### 2. Giao Diện Web

Truy cập: `http://your-domain/admin/reports/revenue`

- Chọn ngày cần xem báo cáo
- Xem thống kê chi tiết trực tuyến
- Gửi báo cáo qua email ngay lập tức

### 3. API Endpoints

```
GET  /admin/reports/revenue           - Trang báo cáo
GET  /admin/reports/revenue-data?date=2024-01-15 - Lấy dữ liệu báo cáo  
POST /admin/reports/send-revenue-report - Gửi email báo cáo
```

## 📊 Nội Dung Báo Cáo

### Thống Kê Tổng Quan
- 💰 Tổng doanh thu
- 📦 Tổng số đơn hàng  
- 👥 Khách hàng mới
- 📈 Tỷ lệ tăng trưởng so với ngày trước

### Chi Tiết
- 📋 Thống kê theo trạng thái đơn hàng
- 🏆 Top 5 sản phẩm bán chạy
- ⏰ Doanh thu theo từng giờ trong ngày
- 💳 Giá trị trung bình mỗi đơn hàng

## 🕐 Lịch Trình Mặc Định

- **Thời gian**: 8:00 AM mỗi ngày
- **Timezone**: Asia/Ho_Chi_Minh
- **Báo cáo**: Dữ liệu của ngày hôm qua

## 🐛 Troubleshooting

### Kiểm tra cronjob có chạy không:
```bash
# Xem log cronjob
tail -f /var/log/cron

# Xem log Laravel
tail -f storage/logs/laravel.log
```

### Kiểm tra email configuration:
```bash
# Test gửi email
php artisan tinker
Mail::raw('Test email', function($message) {
    $message->to('admin@example.com')->subject('Test');
});
```

### Lỗi thường gặp:

1. **Không có admin**: Tạo user với role 'admin'
2. **Email không gửi được**: Kiểm tra cấu hình SMTP trong .env
3. **Cronjob không chạy**: Kiểm tra đường dẫn PHP và project trong crontab
4. **Lỗi permission**: Đảm bảo user chạy cronjob có quyền truy cập project

## 🔧 Tùy Chỉnh

### Thay đổi thời gian chạy:
Sửa file `app/Console/Kernel.php`:
```php
$schedule->command('report:daily-revenue')
    ->dailyAt('09:30')  // Chạy lúc 9:30 AM
    ->timezone('Asia/Ho_Chi_Minh');
```

### Thêm CC email:
Sửa file `app/Mail/DailyRevenueReport.php`:
```php
public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Báo cáo doanh thu ngày ' . $this->reportDate,
        cc: ['manager@example.com', 'finance@example.com']
    );
}
```

### Tùy chỉnh template email:
Chỉnh sửa file `resources/views/emails/daily-revenue-report.blade.php`

## 📞 Hỗ Trợ

Nếu gặp vấn đề, hãy kiểm tra:
1. Log file: `storage/logs/laravel.log`
2. Cronjob log: `/var/log/cron`
3. Email configuration trong `.env`
4. Database có user admin

---
*Tạo bởi: Hệ thống quản lý bán hàng* 🛍️