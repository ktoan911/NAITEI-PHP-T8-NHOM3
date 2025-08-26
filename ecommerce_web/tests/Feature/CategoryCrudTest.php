<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryCrudTest extends TestCase
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
    public function admin_can_create_category_via_api()
    {
        $this->authenticateAdmin();

        $categoryName = 'Danh mục test ' . uniqid();

        $response = $this->postJson('/api/categories', [
            'name' => $categoryName,
            'description' => 'Mô tả danh mục test'
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => $categoryName]);

        $this->assertDatabaseHas('categories', [
            'name' => $categoryName,
            'description' => 'Mô tả danh mục test'
        ]);
    }

    /** @test */
    public function admin_can_create_category_via_web()
    {
        $this->authenticateAdmin();

        $categoryName = 'Danh mục web test ' . uniqid();

        $response = $this->post('/admin/categories', [
            'name' => $categoryName,
            'description' => 'Mô tả danh mục web test'
        ]);

        $response->assertRedirect()
                 ->assertSessionHas('success', 'Category created successfully.');

        $this->assertDatabaseHas('categories', [
            'name' => $categoryName,
            'description' => 'Mô tả danh mục web test'
        ]);
    }

    /** @test */
    public function admin_can_update_category_via_api()
    {
        $this->authenticateAdmin();

        $category = Category::factory()->create([
            'name' => 'Tên cũ',
            'description' => 'Mô tả cũ'
        ]);

        $newName = 'Tên mới được cập nhật ' . uniqid();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => $newName,
            'description' => 'Mô tả mới'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $newName]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $newName,
            'description' => 'Mô tả mới'
        ]);
    }

    /** @test */
    public function admin_can_update_category_via_web()
    {
        $this->authenticateAdmin();

        $category = Category::factory()->create([
            'name' => 'Tên cũ web',
            'description' => 'Mô tả cũ web'
        ]);

        $newName = 'Tên mới web ' . uniqid();

        $response = $this->put("/admin/categories/{$category->id}", [
            'name' => $newName,
            'description' => 'Mô tả mới web'
        ]);

        $response->assertRedirect()
                 ->assertSessionHas('success', 'Category updated successfully.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $newName,
            'description' => 'Mô tả mới web'
        ]);
    }

    /** @test */
    public function admin_can_delete_category_via_api()
    {
        $this->authenticateAdmin();

        $category = Category::factory()->create([
            'name' => 'Danh mục sẽ bị xóa ' . uniqid(),
        ]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Delete category successfully',
                 ]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /** @test */
    public function admin_can_delete_category_via_web()
    {
        $this->authenticateAdmin();

        $category = Category::factory()->create([
            'name' => 'Danh mục web sẽ bị xóa ' . uniqid(),
        ]);

        $response = $this->delete("/admin/categories/{$category->id}");

        $response->assertRedirect()
                 ->assertSessionHas('success', 'Category deleted successfully.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /** @test */
    public function admin_can_list_categories_via_api()
    {
        $this->authenticateAdmin();

        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /** @test */
    public function admin_can_view_single_category_via_api()
    {
        $this->authenticateAdmin();

        $category = Category::factory()->create([
            'name' => 'Danh mục xem chi tiết ' . uniqid(),
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $category->name]);
    }

    /** @test */
    public function user_can_view_categories()
    {
        $this->authenticateUser();

        Category::factory()->count(2)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
        // Don't assert exact count as there might be existing categories
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    /** @test */
    public function user_can_view_single_category()
    {
        $this->authenticateUser();

        $category = Category::factory()->create([
            'name' => 'Danh mục người dùng xem',
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $category->name]);
    }

    /** @test */
    public function user_cannot_create_category()
    {
        $this->authenticateUser();

        $response = $this->postJson('/api/categories', [
            'name' => 'Danh mục không được phép',
            'description' => 'Mô tả không được phép'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_update_category()
    {
        $this->authenticateUser();

        $category = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Tên không được cập nhật'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_delete_category()
    {
        $this->authenticateUser();

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_category_routes()
    {
        $category = Category::factory()->create();

        // Test create
        $response = $this->postJson('/api/categories', [
            'name' => 'Test Category'
        ]);
        $response->assertStatus(401);

        // Test update
        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name'
        ]);
        $response->assertStatus(401);

        // Test delete
        $response = $this->deleteJson("/api/categories/{$category->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function guest_can_view_public_category_routes()
    {
        Category::factory()->count(2)->create();

        // Test list categories
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);
        // Don't assert exact count as there might be existing categories
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        // Test view single category
        $category = Category::factory()->create();
        $response = $this->getJson("/api/categories/{$category->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $category->name]);
    }

    /** @test */
    public function category_validation_works()
    {
        $this->authenticateAdmin();

        // Test required fields
        $response = $this->postJson('/api/categories', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);

        // Test unique name
        $existingCategory = Category::factory()->create(['name' => 'Tên đã tồn tại']);
        
        $response = $this->postJson('/api/categories', [
            'name' => 'Tên đã tồn tại'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);

        // Test max length
        $response = $this->postJson('/api/categories', [
            'name' => str_repeat('a', 256) // Vượt quá 255 ký tự
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function category_relationships_work()
    {
        $category = Category::factory()->create(['name' => 'Danh mục có sản phẩm']);
        $product1 = Product::factory()->create(['category_id' => $category->id, 'name' => 'Sản phẩm 1']);
        $product2 = Product::factory()->create(['category_id' => $category->id, 'name' => 'Sản phẩm 2']);

        // Test products relationship
        $this->assertTrue($category->products->contains($product1));
        $this->assertTrue($category->products->contains($product2));
        $this->assertEquals(2, $category->products->count());

        // Test product belongs to category
        $this->assertEquals($category->id, $product1->category->id);
        $this->assertEquals('Danh mục có sản phẩm', $product1->category->name);
    }

    /** @test */
    public function cannot_delete_category_with_products()
    {
        $this->authenticateAdmin();

        $category = Category::factory()->create(['name' => 'Danh mục có sản phẩm']);
        Product::factory()->create(['category_id' => $category->id]);

        // Test that category with products cannot be deleted
        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    /** @test */
    public function category_name_update_preserves_uniqueness()
    {
        $this->authenticateAdmin();

        $category1 = Category::factory()->create(['name' => 'Danh mục 1']);
        $category2 = Category::factory()->create(['name' => 'Danh mục 2']);

        // Try to update category2 with category1's name
        $response = $this->putJson("/api/categories/{$category2->id}", [
            'name' => 'Danh mục 1'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);

        // Verify original name is preserved
        $this->assertDatabaseHas('categories', [
            'id' => $category2->id,
            'name' => 'Danh mục 2'
        ]);
    }
}