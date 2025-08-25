<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Models\User;
use App\Mail\DailyRevenueReport as DailyRevenueReportMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RevenueReportController extends Controller
{
    /**
     * Display the revenue report page
     */
    public function index()
    {
        return Inertia::render('RevenueReport');
    }

    /**
     * Get revenue report data for a specific date
     */
    public function getReportData(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        try {
            $date = Carbon::parse($request->date);
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            
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

            return response()->json($reportData);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Không thể tạo báo cáo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send revenue report via email
     */
    public function sendEmailReport(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        try {
            $date = Carbon::parse($request->date);
            
            // Lấy dữ liệu báo cáo
            $reportRequest = new Request(['date' => $request->date]);
            $reportResponse = $this->getReportData($reportRequest);
            $reportData = $reportResponse->getData(true);
            
            if (isset($reportData['error'])) {
                return response()->json([
                    'error' => $reportData['error']
                ], 500);
            }
            
            // Lấy danh sách admin
            $admins = User::where('role', 'admin')->get();
            
            if ($admins->isEmpty()) {
                return response()->json([
                    'error' => 'Không tìm thấy admin nào để gửi báo cáo!'
                ], 404);
            }
            
            // Gửi email cho từng admin
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new DailyRevenueReportMail($reportData, $date->format('d/m/Y')));
            }
            
            return response()->json([
                'success' => true,
                'message' => "Đã gửi báo cáo cho {$admins->count()} admin(s)"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Không thể gửi email: ' . $e->getMessage()
            ], 500);
        }
    }
}