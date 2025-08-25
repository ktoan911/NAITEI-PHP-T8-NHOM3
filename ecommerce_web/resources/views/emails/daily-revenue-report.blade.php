<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo doanh thu ngày {{ $reportDate }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 28px;
        }
        .header .date {
            color: #7f8c8d;
            font-size: 18px;
            margin-top: 5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card.revenue {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        .stat-card.orders {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        }
        .stat-card.customers {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        }
        .stat-card.growth {
            background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #2c3e50;
            border-left: 4px solid #4CAF50;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .table tr:hover {
            background-color: #f5f5f5;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-completed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-return { background: #e2e3e5; color: #383d41; }
        .growth-positive { color: #28a745; }
        .growth-negative { color: #dc3545; }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #7f8c8d;
        }
        .chart-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
        }
        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Báo Cáo Doanh Thu</h1>
            <div class="date">Ngày {{ $reportDate }}</div>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-value">{{ number_format($reportData['total_revenue'], 0, ',', '.') }} {{ $reportData['currency'] }}</div>
                <div class="stat-label">Tổng Doanh Thu</div>
            </div>
            <div class="stat-card orders">
                <div class="stat-value">{{ $reportData['total_orders'] }}</div>
                <div class="stat-label">Tổng Đơn Hàng</div>
            </div>
            <div class="stat-card customers">
                <div class="stat-value">{{ $reportData['new_customers'] }}</div>
                <div class="stat-label">Khách Hàng Mới</div>
            </div>
            <div class="stat-card growth">
                <div class="stat-value {{ $reportData['revenue_growth'] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                    {{ $reportData['revenue_growth'] >= 0 ? '+' : '' }}{{ number_format($reportData['revenue_growth'], 1) }}%
                </div>
                <div class="stat-label">Tăng Trưởng</div>
            </div>
        </div>

        <!-- Thống kê chi tiết -->
        <div class="section">
            <h2>📈 Thông Tin Chi Tiết</h2>
            <table class="table">
                <tr>
                    <td><strong>Đơn hàng hoàn thành:</strong></td>
                    <td>{{ $reportData['completed_orders'] }} / {{ $reportData['total_orders'] }}</td>
                </tr>
                <tr>
                    <td><strong>Giá trị trung bình mỗi đơn:</strong></td>
                    <td>{{ number_format($reportData['average_order_value'], 0, ',', '.') }} {{ $reportData['currency'] }}</td>
                </tr>
                <tr>
                    <td><strong>Doanh thu ngày hôm trước:</strong></td>
                    <td>{{ number_format($reportData['previous_day_revenue'], 0, ',', '.') }} {{ $reportData['currency'] }}</td>
                </tr>
            </table>
        </div>

        <!-- Thống kê theo trạng thái -->
        <div class="section">
            <h2>📋 Thống Kê Theo Trạng Thái</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Trạng Thái</th>
                        <th>Số Lượng</th>
                        <th>Tỷ Lệ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['orders_by_status'] as $status => $count)
                    <tr>
                        <td>
                            <span class="status-badge status-{{ $status }}">
                                @switch($status)
                                    @case('completed') Hoàn thành @break
                                    @case('pending') Chờ xử lý @break
                                    @case('processing') Đang xử lý @break
                                    @case('cancelled') Đã hủy @break
                                    @case('return') Trả hàng @break
                                    @default {{ ucfirst($status) }}
                                @endswitch
                            </span>
                        </td>
                        <td>{{ $count }}</td>
                        <td>{{ $reportData['total_orders'] > 0 ? number_format(($count / $reportData['total_orders']) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Top sản phẩm bán chạy -->
        @if(count($reportData['top_products']) > 0)
        <div class="section">
            <h2>🏆 Top Sản Phẩm Bán Chạy</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Sản Phẩm</th>
                        <th>Giá</th>
                        <th>Số Lượng Bán</th>
                        <th>Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['top_products'] as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ number_format($product->price, 0, ',', '.') }} {{ $reportData['currency'] }}</td>
                        <td>{{ $product->total_sold }}</td>
                        <td>{{ number_format($product->price * $product->total_sold, 0, ',', '.') }} {{ $reportData['currency'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Doanh thu theo giờ -->
        @if(count($reportData['revenue_by_hour']) > 0)
        <div class="section">
            <h2>⏰ Doanh Thu Theo Giờ</h2>
            <div class="chart-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Giờ</th>
                            <th>Số Đơn Hàng</th>
                            <th>Doanh Thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['revenue_by_hour'] as $hourData)
                        <tr>
                            <td>{{ $hourData['hour'] }}</td>
                            <td>{{ $hourData['orders_count'] }}</td>
                            <td>{{ number_format($hourData['revenue'], 0, ',', '.') }} {{ $reportData['currency'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="footer">
            <p>📧 Báo cáo được tạo tự động vào {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>💼 Hệ thống quản lý bán hàng</p>
        </div>
    </div>
</body>
</html>