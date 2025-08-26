<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address;
use App\Models\Category;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function authenticateAdmin()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->actingAs($admin);
        return $admin;
    }

    protected function authenticateUser()
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active'
        ]);

        $this->actingAs($user);
        return $user;
    }



    /** @test */
    public function admin_can_view_all_orders()
    {
        $this->authenticateAdmin();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address1 = Address::factory()->create(['user_id' => $user1->id]);
        $address2 = Address::factory()->create(['user_id' => $user2->id]);
        
        Order::factory()->create(['user_id' => $user1->id, 'address_id' => $address1->id]);
        Order::factory()->create(['user_id' => $user2->id, 'address_id' => $address2->id]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data', // paginated data
                         'current_page',
                         'per_page',
                         'total'
                     ],
                     'message'
                 ]);
    }

    /** @test */
    public function user_can_only_view_own_orders()
    {
        $user1 = $this->authenticateUser();
        $user2 = User::factory()->create();
        
        $address1 = Address::factory()->create(['user_id' => $user1->id]);
        $address2 = Address::factory()->create(['user_id' => $user2->id]);
        
        $userOrder = Order::factory()->create(['user_id' => $user1->id, 'address_id' => $address1->id]);
        $otherOrder = Order::factory()->create(['user_id' => $user2->id, 'address_id' => $address2->id]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200);
        
        $orders = $response->json('data.data');
        $orderIds = collect($orders)->pluck('id')->toArray();
        
        $this->assertContains($userOrder->id, $orderIds);
        $this->assertNotContains($otherOrder->id, $orderIds);
    }

    /** @test */
    public function user_can_view_own_order_details()
    {
        $user = $this->authenticateUser();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'user_id',
                         'address_id',
                         'total_price',
                         'status',
                         'user',
                         'address',
                         'order_items'
                     ],
                     'message'
                 ])
                 ->assertJsonFragment(['id' => $order->id]);
    }

    /** @test */
    public function user_cannot_view_other_users_order()
    {
        $user1 = $this->authenticateUser();
        $user2 = User::factory()->create();
        $address2 = Address::factory()->create(['user_id' => $user2->id]);
        $otherOrder = Order::factory()->create(['user_id' => $user2->id, 'address_id' => $address2->id]);

        $response = $this->getJson("/api/orders/{$otherOrder->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_any_order_status()
    {
        $admin = $this->authenticateAdmin();
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => Order::STATUS_PENDING
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => Order::STATUS_PROCESSING
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => Order::STATUS_PROCESSING]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PROCESSING
        ]);
    }

    /** @test */
    public function user_cannot_update_order_status()
    {
        $user = $this->authenticateUser();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => Order::STATUS_PENDING
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => Order::STATUS_PROCESSING
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_cancel_own_order()
    {
        $user = $this->authenticateUser();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => Order::STATUS_PENDING
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => Order::STATUS_CANCELLED]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CANCELLED
        ]);
    }

    /** @test */
    public function user_cannot_cancel_other_users_order()
    {
        $user1 = $this->authenticateUser();
        $user2 = User::factory()->create();
        $address2 = Address::factory()->create(['user_id' => $user2->id]);
        $otherOrder = Order::factory()->create([
            'user_id' => $user2->id,
            'address_id' => $address2->id,
            'status' => Order::STATUS_PENDING
        ]);

        $response = $this->postJson("/api/orders/{$otherOrder->id}/cancel");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_order()
    {
        $admin = $this->authenticateAdmin();
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        
        $order = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message'
                 ]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
    }





    /** @test */
    public function user_cannot_view_order_statistics()
    {
        $this->authenticateUser();

        $response = $this->getJson('/api/orders/statistics/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_order_routes()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id]);

        // Test list orders
        $response = $this->getJson('/api/orders');
        $response->assertStatus(401);

        // Test view order
        $response = $this->getJson("/api/orders/{$order->id}");
        $response->assertStatus(401);

        // Test create order
        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => Order::STATUS_PENDING
        ]);
        $response->assertStatus(401);

        // Test cancel order
        $response = $this->postJson("/api/orders/{$order->id}/cancel");
        $response->assertStatus(401);

        // Test delete order
        $response = $this->deleteJson("/api/orders/{$order->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function order_validation_works()
    {
        $user = $this->authenticateUser();

        // Test required fields
        $response = $this->postJson('/api/orders', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['address_id', 'order_items']);

        // Test invalid status
        $address = Address::factory()->create(['user_id' => $user->id]);
        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'invalid_status',
            'order_items' => []
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);

        // Test invalid address
        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'address_id' => 99999, // Non-existent address
            'status' => Order::STATUS_PENDING,
            'order_items' => []
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['address_id']);
    }

    /** @test */
    public function order_status_validation_works()
    {
        $admin = $this->authenticateAdmin();
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id]);

        // Test invalid status
        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'invalid_status'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);

        // Test valid statuses
        $validStatuses = Order::getStatuses();
        foreach ($validStatuses as $status) {
            $response = $this->postJson("/api/orders/{$order->id}/status", [
                'status' => $status
            ]);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function order_relationships_work()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);
        
        $order = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id]);
        $orderItem1 = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id]);
        $orderItem2 = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id]);

        // Test order belongs to user
        $this->assertEquals($user->id, $order->user->id);

        // Test order belongs to address
        $this->assertEquals($address->id, $order->address->id);

        // Test order has many order items
        $this->assertTrue($order->orderItems->contains($orderItem1));
        $this->assertTrue($order->orderItems->contains($orderItem2));
        $this->assertEquals(2, $order->orderItems->count());

        // Test order has products through order items
        $this->assertTrue($order->products->contains($product1));
        $this->assertTrue($order->products->contains($product2));
    }

    /** @test */
    public function order_scopes_work()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        
        $pendingOrder = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id, 'status' => Order::STATUS_PENDING]);
        $completedOrder = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id, 'status' => Order::STATUS_COMPLETED]);

        // Test status scope
        $pendingOrders = Order::status(Order::STATUS_PENDING)->get();
        $this->assertTrue($pendingOrders->contains($pendingOrder));
        $this->assertFalse($pendingOrders->contains($completedOrder));

        // Test forUser scope
        $userOrders = Order::forUser($user->id)->get();
        $this->assertTrue($userOrders->contains($pendingOrder));
        $this->assertTrue($userOrders->contains($completedOrder));
    }

    /** @test */
    public function order_calculate_total_price_works()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id, 'price' => 100]);
        $product2 = Product::factory()->create(['category_id' => $category->id, 'price' => 200]);
        
        $order = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id, 'total_price' => 0]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id, 'quantity' => 2]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id, 'quantity' => 1]);

        $calculatedTotal = $order->calculateTotalPrice();
        $this->assertEquals(400, $calculatedTotal); // (100*2) + (200*1)
    }

    /** @test */
    public function order_can_be_cancelled_check_works()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        
        $pendingOrder = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id, 'status' => Order::STATUS_PENDING]);
        $processingOrder = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id, 'status' => Order::STATUS_PROCESSING]);
        $completedOrder = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id, 'status' => Order::STATUS_COMPLETED]);
        $cancelledOrder = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id, 'status' => Order::STATUS_CANCELLED]);

        $this->assertTrue($pendingOrder->canBeCancelled());
        $this->assertTrue($processingOrder->canBeCancelled());
        $this->assertFalse($completedOrder->canBeCancelled());
        $this->assertFalse($cancelledOrder->canBeCancelled());
    }

    /** @test */
    public function order_belongs_to_user_check_works()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address1 = Address::factory()->create(['user_id' => $user1->id]);
        
        $order = Order::factory()->create(['user_id' => $user1->id, 'address_id' => $address1->id]);

        $this->assertTrue($order->belongsToUser($user1->id));
        $this->assertFalse($order->belongsToUser($user2->id));
    }
}