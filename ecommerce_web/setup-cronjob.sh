c #!/bin/bash

# Script thiết lập cronjob cho báo cáo doanh thu hàng ngày
# Chạy script này với quyền sudo để thiết lập cronjob

PROJECT_PATH="/media/DATA/Sun-Asterisk Project/NAITEI-PHP-T8-NHOM3/ecommerce_web"
PHP_PATH=$(which php)

echo "🔧 Thiết lập cronjob cho báo cáo doanh thu hàng ngày"
echo "📂 Đường dẫn project: $PROJECT_PATH"
echo "🐘 PHP path: $PHP_PATH"

# Kiểm tra xem cronjob đã tồn tại chưa
CRON_JOB="0 8 * * * cd $PROJECT_PATH && $PHP_PATH artisan schedule:run >> /dev/null 2>&1"
EXISTING_CRON=$(crontab -l 2>/dev/null | grep -F "artisan schedule:run")

if [ -z "$EXISTING_CRON" ]; then
    echo "➕ Thêm cronjob mới..."
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "✅ Đã thêm cronjob thành công!"
else
    echo "ℹ️  Cronjob đã tồn tại: $EXISTING_CRON"
fi

echo ""
echo "📋 Danh sách cronjob hiện tại:"
crontab -l

echo ""
echo "🚀 Hướng dẫn sử dụng:"
echo "1. Để chạy báo cáo thủ công: php artisan report:daily-revenue"
echo "2. Để chạy báo cáo cho ngày cụ thể: php artisan report:daily-revenue 2024-01-15"
echo "3. Cronjob sẽ tự động chạy lúc 8:00 AM hàng ngày"
echo ""
echo "📧 Cấu hình email:"
echo "- Cập nhật file .env với thông tin SMTP"
echo "- Đảm bảo có ít nhất 1 user với role 'admin' trong database"
echo ""
echo "🔍 Kiểm tra log: tail -f storage/logs/laravel.log"