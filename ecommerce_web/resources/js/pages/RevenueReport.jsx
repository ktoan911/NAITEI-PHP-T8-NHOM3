import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
    TrendingUp, 
    TrendingDown, 
    DollarSign, 
    ShoppingCart, 
    Users, 
    BarChart3,
    Calendar,
    Mail,
    Download
} from 'lucide-react';

export default function RevenueReport({ auth }) {
    const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
    const [reportData, setReportData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const fetchReportData = async (date) => {
        setLoading(true);
        setError(null);
        
        try {
            const response = await fetch(`/admin/revenue-report?date=${date}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            
            if (!response.ok) {
                throw new Error('Không thể tải báo cáo');
            }
            
            const data = await response.json();
            setReportData(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const sendEmailReport = async () => {
        setLoading(true);
        try {
            const response = await fetch('/admin/send-revenue-report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({ date: selectedDate }),
            });
            
            if (response.ok) {
                alert('Đã gửi báo cáo qua email thành công!');
            } else {
                throw new Error('Không thể gửi email');
            }
        } catch (err) {
            alert('Lỗi: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchReportData(selectedDate);
    }, [selectedDate]);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    };

    const getStatusBadgeVariant = (status) => {
        switch (status) {
            case 'completed': return 'default';
            case 'pending': return 'secondary';
            case 'processing': return 'outline';
            case 'cancelled': return 'destructive';
            case 'return': return 'secondary';
            default: return 'secondary';
        }
    };

    const getStatusLabel = (status) => {
        switch (status) {
            case 'completed': return 'Hoàn thành';
            case 'pending': return 'Chờ xử lý';
            case 'processing': return 'Đang xử lý';
            case 'cancelled': return 'Đã hủy';
            case 'return': return 'Trả hàng';
            default: return status;
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Báo cáo doanh thu</h2>}
        >
            <Head title="Báo cáo doanh thu" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header Controls */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BarChart3 className="h-5 w-5" />
                                Báo cáo doanh thu chi tiết
                            </CardTitle>
                            <CardDescription>
                                Xem và quản lý báo cáo doanh thu hàng ngày
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col sm:flex-row gap-4 items-end">
                                <div className="flex-1">
                                    <Label htmlFor="date">Chọn ngày</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={selectedDate}
                                        onChange={(e) => setSelectedDate(e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button 
                                        onClick={() => fetchReportData(selectedDate)}
                                        disabled={loading}
                                    >
                                        <Calendar className="h-4 w-4 mr-2" />
                                        Xem báo cáo
                                    </Button>
                                    <Button 
                                        variant="outline"
                                        onClick={sendEmailReport}
                                        disabled={loading}
                                    >
                                        <Mail className="h-4 w-4 mr-2" />
                                        Gửi email
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {error && (
                        <Card className="border-red-200">
                            <CardContent className="pt-6">
                                <div className="text-red-600 text-center">
                                    ⚠️ {error}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {loading && (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                    <p className="mt-2 text-gray-600">Đang tải báo cáo...</p>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {reportData && (
                        <>
                            {/* Key Metrics */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <Card>
                                    <CardContent className="pt-6">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Tổng doanh thu</p>
                                                <p className="text-2xl font-bold text-green-600">
                                                    {formatCurrency(reportData.total_revenue)}
                                                </p>
                                            </div>
                                            <DollarSign className="h-8 w-8 text-green-600" />
                                        </div>
                                        {reportData.revenue_growth !== undefined && (
                                            <div className="flex items-center mt-2">
                                                {reportData.revenue_growth >= 0 ? (
                                                    <TrendingUp className="h-4 w-4 text-green-500 mr-1" />
                                                ) : (
                                                    <TrendingDown className="h-4 w-4 text-red-500 mr-1" />
                                                )}
                                                <span className={`text-sm ${reportData.revenue_growth >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                    {reportData.revenue_growth >= 0 ? '+' : ''}{reportData.revenue_growth.toFixed(1)}%
                                                </span>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardContent className="pt-6">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Tổng đơn hàng</p>
                                                <p className="text-2xl font-bold">{reportData.total_orders}</p>
                                            </div>
                                            <ShoppingCart className="h-8 w-8 text-blue-600" />
                                        </div>
                                        <p className="text-sm text-gray-500 mt-2">
                                            {reportData.completed_orders} hoàn thành
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardContent className="pt-6">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Khách hàng mới</p>
                                                <p className="text-2xl font-bold">{reportData.new_customers}</p>
                                            </div>
                                            <Users className="h-8 w-8 text-purple-600" />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardContent className="pt-6">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Giá trị TB/đơn</p>
                                                <p className="text-2xl font-bold">
                                                    {formatCurrency(reportData.average_order_value)}
                                                </p>
                                            </div>
                                            <BarChart3 className="h-8 w-8 text-orange-600" />
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Order Status Breakdown */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Thống kê theo trạng thái</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                                        {Object.entries(reportData.orders_by_status || {}).map(([status, count]) => (
                                            <div key={status} className="text-center">
                                                <Badge variant={getStatusBadgeVariant(status)} className="mb-2">
                                                    {getStatusLabel(status)}
                                                </Badge>
                                                <p className="text-2xl font-bold">{count}</p>
                                                <p className="text-sm text-gray-500">
                                                    {reportData.total_orders > 0 ? 
                                                        ((count / reportData.total_orders) * 100).toFixed(1) : 0
                                                    }%
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Top Products */}
                            {reportData.top_products && reportData.top_products.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Top sản phẩm bán chạy</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <table className="w-full">
                                                <thead>
                                                    <tr className="border-b">
                                                        <th className="text-left py-2">Sản phẩm</th>
                                                        <th className="text-right py-2">Giá</th>
                                                        <th className="text-right py-2">Số lượng bán</th>
                                                        <th className="text-right py-2">Doanh thu</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {reportData.top_products.map((product, index) => (
                                                        <tr key={index} className="border-b">
                                                            <td className="py-2 font-medium">{product.name}</td>
                                                            <td className="py-2 text-right">{formatCurrency(product.price)}</td>
                                                            <td className="py-2 text-right">{product.total_sold}</td>
                                                            <td className="py-2 text-right font-semibold">
                                                                {formatCurrency(product.price * product.total_sold)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Revenue by Hour */}
                            {reportData.revenue_by_hour && Object.keys(reportData.revenue_by_hour).length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Doanh thu theo giờ</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                            {Object.entries(reportData.revenue_by_hour).map(([hour, data]) => (
                                                <div key={hour} className="text-center p-3 bg-gray-50 rounded-lg">
                                                    <p className="font-semibold">{data.hour}</p>
                                                    <p className="text-sm text-gray-600">{data.orders_count} đơn</p>
                                                    <p className="text-sm font-medium">{formatCurrency(data.revenue)}</p>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}