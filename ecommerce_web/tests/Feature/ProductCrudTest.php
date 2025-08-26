<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductCrudTest extends TestCase
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
    public function admin_can_create_product_via_web()
    {
        $this->authenticateAdmin();
        
        $category = Category::factory()->create();
        $productName = 'Sản phẩm web test ' . uniqid();

        $response = $this->post('/admin/products', [
            'name' => $productName,
            'description' => 'Mô tả sản phẩm web test',
            'price' => 149.99,
            'category_id' => $category->id,
            'stock' => 20,
            'image_url' => 'https://example.com/image.jpg',
            'author' => 'Tác giả web test'
        ]);

        $response->assertRedirect()
                 ->assertSessionHas('success', 'Product created successfully.');

        $this->assertDatabaseHas('products', [
            'name' => $productName,
            'price' => 149.99
        ]);
    }

    /** @test */
    public function admin_can_update_product_via_api()
    {
        $this->authenticateAdmin();
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Tên cũ',
            'category_id' => $category->id
        ]);

        $newName = 'Tên mới được cập nhật ' . uniqid();

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => $newName,
            'description' => 'Mô tả mới',
            'price' => 199.99,
            'category_id' => $category->id,
            'stock' => 15
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $newName]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $newName,
            'price' => 199.99
        ]);
    }

    /** @test */
    public function admin_can_update_product_via_web()
    {
        $this->authenticateAdmin();
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Tên cũ web',
            'category_id' => $category->id
        ]);

        $newName = 'Tên mới web ' . uniqid();

        $response = $this->put("/admin/products/{$product->id}", [
            'name' => $newName,
            'description' => 'Mô tả mới web',
            'price' => 299.99,
            'category_id' => $category->id,
            'stock' => 25
        ]);

        $response->assertRedirect()
                 ->assertSessionHas('success', 'Product updated successfully.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $newName,
            'price' => 299.99
        ]);
    }



    /** @test */
    public function admin_can_delete_product_via_web()
    {
        $this->authenticateAdmin();

        $product = Product::factory()->create([
            'name' => 'Sản phẩm web sẽ bị xóa ' . uniqid(),
        ]);

        $response = $this->delete("/admin/products/{$product->id}");

        $response->assertRedirect()
                 ->assertSessionHas('success', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function admin_can_list_products_via_api()
    {
        $this->authenticateAdmin();

        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /** @test */
    public function admin_can_view_single_product_via_api()
    {
        $this->authenticateAdmin();

        $product = Product::factory()->create([
            'name' => 'Sản phẩm xem chi tiết ' . uniqid(),
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $product->name]);
    }

    /** @test */
    public function user_can_view_products()
    {
        $this->authenticateUser();

        Product::factory()->count(2)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        // Don't assert exact count as there might be existing products
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    /** @test */
    public function user_cannot_create_product()
    {
        $this->authenticateUser();
        
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'name' => 'Sản phẩm không được phép',
            'price' => 99.99,
            'category_id' => $category->id,
            'stock' => 10
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_update_product()
    {
        $this->authenticateUser();

        $product = Product::factory()->create();

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Tên không được cập nhật'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_delete_product()
    {
        $this->authenticateUser();

        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_product_routes()
    {
        $product = Product::factory()->create();

        // Test create
        $response = $this->postJson('/api/products', [
            'name' => 'Test Product'
        ]);
        $response->assertStatus(401);

        // Test update
        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Name'
        ]);
        $response->assertStatus(401);

        // Test delete
        $response = $this->deleteJson("/api/products/{$product->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function product_validation_works()
    {
        $this->authenticateAdmin();

        // Test required fields
        $response = $this->postJson('/api/products', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'price', 'category_id', 'stock']);

        // Test unique name
        $existingProduct = Product::factory()->create(['name' => 'Tên đã tồn tại']);
        
        $response = $this->postJson('/api/products', [
            'name' => 'Tên đã tồn tại',
            'price' => 99.99,
            'category_id' => 1,
            'stock' => 10
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);

        // Test negative price
        $response = $this->postJson('/api/products', [
            'name' => 'Sản phẩm giá âm',
            'price' => -10,
            'category_id' => 1,
            'stock' => 10
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function product_relationships_work()
    {
        $category = Category::factory()->create(['name' => 'Danh mục test']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Test category relationship
        $this->assertEquals($category->id, $product->category->id);
        $this->assertEquals('Danh mục test', $product->category->name);

        // Test products in category
        $this->assertTrue($category->products->contains($product));
    }
}