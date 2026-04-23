<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Электроника']);
    }

    public function test_returns_paginated_list(): void
    {
        Product::factory(3)->create(['category_id' => $this->category->id]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'price', 'category', 'in_stock', 'rating', 'created_at']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }

    public function test_empty_result_returns_200(): void
    {
        $this->getJson('/api/products?rating_from=5&in_stock=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // --- Поиск ---

    public function test_search_by_name(): void
    {
        Product::factory()->create(['name' => 'Смартфон Xiaomi', 'category_id' => $this->category->id]);
        Product::factory()->create(['name' => 'Холодильник Bosch', 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?q=Смартфон')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Смартфон Xiaomi', $response->json('data.0.name'));
    }

    // --- Фильтры ---

    public function test_filter_by_price_from(): void
    {
        Product::factory()->create(['price' => 500, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 1500, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?price_from=1000')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('1500.00', $response->json('data.0.price'));
    }

    public function test_filter_by_price_to(): void
    {
        Product::factory()->create(['price' => 500, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 1500, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?price_to=1000')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('500.00', $response->json('data.0.price'));
    }

    public function test_filter_by_price_range(): void
    {
        Product::factory()->create(['price' => 500,  'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 1500, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 3000, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?price_from=1000&price_to=2000')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('1500.00', $response->json('data.0.price'));
    }

    public function test_filter_by_category(): void
    {
        $other = Category::create(['name' => 'Другая']);
        Product::factory()->create(['category_id' => $this->category->id]);
        Product::factory()->create(['category_id' => $other->id]);

        $response = $this->getJson('/api/products?category_id=' . $this->category->id)->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($this->category->id, $response->json('data.0.category.id'));
    }

    public function test_filter_in_stock_true(): void
    {
        Product::factory()->create(['in_stock' => true,  'category_id' => $this->category->id]);
        Product::factory()->create(['in_stock' => false, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?in_stock=1')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.in_stock'));
    }

    public function test_filter_in_stock_false(): void
    {
        Product::factory()->create(['in_stock' => true,  'category_id' => $this->category->id]);
        Product::factory()->create(['in_stock' => false, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?in_stock=0')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertFalse($response->json('data.0.in_stock'));
    }

    public function test_filter_by_rating_from(): void
    {
        Product::factory()->create(['rating' => 3.0, 'category_id' => $this->category->id]);
        Product::factory()->create(['rating' => 4.5, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?rating_from=4')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertGreaterThanOrEqual(4, $response->json('data.0.rating'));
    }

    // --- Сортировка ---

    public function test_sort_price_asc(): void
    {
        Product::factory()->create(['price' => 3000, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 500,  'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 1500, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?sort=price_asc')->assertOk();

        $prices = array_column($response->json('data'), 'price');
        $this->assertEquals(['500.00', '1500.00', '3000.00'], $prices);
    }

    public function test_sort_price_desc(): void
    {
        Product::factory()->create(['price' => 3000, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 500,  'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 1500, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?sort=price_desc')->assertOk();

        $prices = array_column($response->json('data'), 'price');
        $this->assertEquals(['3000.00', '1500.00', '500.00'], $prices);
    }

    public function test_sort_rating_desc(): void
    {
        Product::factory()->create(['rating' => 3.0, 'category_id' => $this->category->id]);
        Product::factory()->create(['rating' => 5.0, 'category_id' => $this->category->id]);
        Product::factory()->create(['rating' => 4.0, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?sort=rating_desc')->assertOk();

        $ratings = array_column($response->json('data'), 'rating');
        $this->assertEquals([5.0, 4.0, 3.0], $ratings);
    }

    public function test_sort_newest(): void
    {
        Product::factory()->create(['category_id' => $this->category->id, 'created_at' => now()->subDays(2)]);
        Product::factory()->create(['category_id' => $this->category->id, 'created_at' => now()->subDays(1)]);
        /** @var Product $newest */
        $newest = Product::factory()->create(['category_id' => $this->category->id, 'created_at' => now()]);

        $response = $this->getJson('/api/products?sort=newest')->assertOk();

        $this->assertSame($newest->id, $response->json('data.0.id'));
    }

    // --- Валидация ---

    public function test_invalid_sort_returns_422(): void
    {
        $this->getJson('/api/products?sort=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_invalid_price_range_returns_422(): void
    {
        $this->getJson('/api/products?price_from=1000&price_to=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price_to']);
    }

    public function test_rating_out_of_range_returns_422(): void
    {
        $this->getJson('/api/products?rating_from=6')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating_from']);
    }

    public function test_nonexistent_category_returns_422(): void
    {
        $this->getJson('/api/products?category_id=99999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    // --- Комбинации ---

    public function test_combined_filters(): void
    {
        $other = Category::create(['name' => 'Другая']);

        Product::factory()->create(['price' => 1500, 'in_stock' => true,  'rating' => 4.5, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 1500, 'in_stock' => false, 'rating' => 4.5, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 500,  'in_stock' => true,  'rating' => 4.5, 'category_id' => $this->category->id]);
        Product::factory()->create(['price' => 1500, 'in_stock' => true,  'rating' => 4.5, 'category_id' => $other->id]);

        $response = $this->getJson(
            '/api/products?price_from=1000&in_stock=1&category_id=' . $this->category->id
        )->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    // --- Soft delete ---

    public function test_deleted_products_not_in_results(): void
    {
        Product::factory()->create(['category_id' => $this->category->id]);
        Product::factory()->create(['category_id' => $this->category->id]);

        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $product->delete();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
