<?php

namespace App\QueryBuilders;

use App\DTOs\ProductFilterDTO;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductQueryBuilder
{
    private const array SORT_MAP = [
        'price_asc'   => ['price', 'asc'],
        'price_desc'  => ['price', 'desc'],
        'rating_desc' => ['rating', 'desc'],
        'newest'      => ['created_at', 'desc'],
    ];

    public function build(ProductFilterDTO $dto): Builder
    {
        $query = Product::query()->with('category');

        $this->applySearch($query, $dto->q);
        $this->applyPriceFilter($query, $dto->priceFrom, $dto->priceTo);
        $this->applyCategoryFilter($query, $dto->categoryId);
        $this->applyStockFilter($query, $dto->inStock);
        $this->applyRatingFilter($query, $dto->ratingFrom);
        $this->applySort($query, $dto->sort);

        return $query;
    }

    public function buildWithIds(ProductFilterDTO $dto, array $ids): Builder
    {
        $query = Product::query()->with('category');

        $query->whereIn('id', $ids);

        $this->applyPriceFilter($query, $dto->priceFrom, $dto->priceTo);
        $this->applyCategoryFilter($query, $dto->categoryId);
        $this->applyStockFilter($query, $dto->inStock);
        $this->applyRatingFilter($query, $dto->ratingFrom);

        // Сохраняем порядок релевантности из ES
        $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');

        return $query;
    }

    private function applySearch(Builder $query, ?string $q): void
    {
        if ($q === null) {
            return;
        }

        $query->where('name', 'like', '%' . $q . '%');
    }

    private function applyPriceFilter(Builder $query, ?float $from, ?float $to): void
    {
        if ($from !== null) {
            $query->where('price', '>=', $from);
        }

        if ($to !== null) {
            $query->where('price', '<=', $to);
        }
    }

    private function applyCategoryFilter(Builder $query, ?int $categoryId): void
    {
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
    }

    private function applyStockFilter(Builder $query, ?bool $inStock): void
    {
        if ($inStock !== null) {
            $query->where('in_stock', $inStock);
        }
    }

    private function applyRatingFilter(Builder $query, ?float $ratingFrom): void
    {
        if ($ratingFrom !== null) {
            $query->where('rating', '>=', $ratingFrom);
        }
    }

    private function applySort(Builder $query, ?string $sort): void
    {
        [$column, $direction] = self::SORT_MAP[$sort] ?? ['created_at', 'desc'];
        $query->orderBy($column, $direction);
    }
}
