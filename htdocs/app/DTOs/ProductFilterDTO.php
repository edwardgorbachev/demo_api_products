<?php

namespace App\DTOs;

use App\Http\Requests\ProductFilterRequest;

final readonly class ProductFilterDTO
{
    public function __construct(
        public ?string $q = null,
        public ?float $priceFrom = null,
        public ?float $priceTo = null,
        public ?int $categoryId = null,
        public ?bool $inStock = null,
        public ?float $ratingFrom = null,
        public ?string $sort = null,
        public int $perPage = 15,
    ) {}

    public static function fromRequest(ProductFilterRequest $request): self
    {
        return new self(
            q:          $request->filled('q')           ? $request->str('q')->toString()    : null,
            priceFrom:  $request->filled('price_from')  ? $request->float('price_from')     : null,
            priceTo:    $request->filled('price_to')    ? $request->float('price_to')       : null,
            categoryId: $request->filled('category_id') ? $request->integer('category_id')  : null,
            inStock:    $request->has('in_stock')       ? $request->boolean('in_stock')     : null,
            ratingFrom: $request->filled('rating_from') ? $request->float('rating_from')    : null,
            sort:       $request->filled('sort')        ? $request->str('sort')->toString()  : null,
            perPage:    $request->integer('per_page', 15),
        );
    }
}
