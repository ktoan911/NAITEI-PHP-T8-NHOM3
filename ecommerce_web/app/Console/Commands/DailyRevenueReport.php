<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Mail\DailyRevenueReport as DailyRevenueReportMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyRevenueReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:daily-revenue {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi báo cáo doanh thu hàng ngày qua email cho admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Bắt đầu tạo báo cáo doanh thu hàng ngày...');
            
            // Lấy ngày từ tham số hoặc sử dụng ngày hôm qua
            $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::yesterday();
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            
            $this->info("Tạo báo cáo cho ngày: {$date->format('d/m/Y')}");
            
            // Lấy dữ liệu đơn hàng trong ngày
            $ordersQuery = Order::whereBetween('created_at', [$startOfDay, $endOfDay])
                ->with(['user', 'orderItems.product']);
            
            $orders = $ordersQuery->get();
            $completedOrders = $ordersQuery->where('status', Order::STATUS_COMPLETED)->get();
            
            // Tính toán thống kê
            $totalOrders = $orders->count();
            $completedOrdersCount = $completedOrders->count();
            $totalRevenue = $completedOrders->sum('total_price');
            $averageOrderValue = $completedOrdersCount > 0 ? $totalRevenue / $completedOrdersCount : 0;
            
            // Thống kê theo trạng thái
            $ordersByStatus = $orders->groupBy('status')->map->count();
            
            // Top sản phẩm bán chạy
            $topProducts = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereBetween('orders.created_at', [$startOfDay, $endOfDay])
                ->where('orders.status', Order::STATUS_COMPLETED)
                ->select('products.name', 'products.price', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->groupBy('products.id', 'products.name', 'products.price')
                ->orderByDesc('total_sold')
                ->limit(5)
                ->get();
            
            // Doanh thu theo giờ
            $revenueByHour = $completedOrders->groupBy(function($order) {
                return $order->created_at->format('H');
            })->map(function($orders, $hour) {
                return [
                    'hour' => $hour . ':00',
                    'revenue' => $orders->sum('total_price'),
                    'orders_count' => $orders->count()
                ];
            })->sortKeys();
            
            // Thông tin khách hàng mới
            $newCustomers = User::whereBetween('created_at', [$startOfDay, $endOfDay])
                ->where('role', 'user')
                ->count();
            
            // So sánh với ngày hôm trước
            $previousDate = $date->copy()->subDay();
            $previousDayRevenue = Order::whereBetween('created_at', [
                $previousDate->startOfDay(), 
                $previousDate->endOfDay()
            ])
            ->where('status', Order::STATUS_COMPLETED)
            ->sum('total_price');
            
            $revenueGrowth = $previousDayRevenue > 0 ? 
                (($totalRevenue - $previousDayRevenue) / $previousDayRevenue) * 100 : 
                ($totalRevenue > 0 ? 100 : 0);
            
            $reportData = [
                'date' => $date->format('d/m/Y'),
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrdersCount,
                'total_revenue' => $totalRevenue,
                'average_order_value' => $averageOrderValue,
                'orders_by_status' => $ordersByStatus,
                'top_products' => $topProducts,
                'revenue_by_hour' => $revenueByHour,
                'new_customers' => $newCustomers,
                'previous_day_revenue' => $previousDayRevenue,
                'revenue_growth' => $revenueGrowth,
                'currency' => 'VNĐ'
            ];
            
            // Lấy danh sách admin
            $admins = User::where('role', 'admin')->get();
            
            if ($admins->isEmpty()) {
                $this->warn('Không tìm thấy admin nào để gửi báo cáo!');
                return 1;
            }
            
            // Gửi email cho từng admin
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new DailyRevenueReportMail($reportData, $date->format('d/m/Y')));
                $this->info("Đã gửi báo cáo cho admin: {$admin->email}");
            }
            
            $this->info("✅ Hoàn thành! Đã gửi báo cáo cho {$admins->count()} admin(s)");
            $this->info("📊 Tổng doanh thu: " . number_format($totalRevenue, 0, ',', '.') . " VNĐ");
            $this->info("📦 Tổng đơn hàng: {$totalOrders} ({$completedOrdersCount} hoàn thành)");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Lỗi khi tạo báo cáo: ' . $e->getMessage());
            return 1;
        }
    }
}
