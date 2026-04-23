<?php

namespace App\Services;

use App\Contracts\ProductSearchServiceInterface;
use App\DTOs\ProductFilterDTO;
use App\QueryBuilders\ProductQueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductSearchService implements ProductSearchServiceInterface
{
    public function __construct(
        private readonly ProductQueryBuilder $queryBuilder,
    ) {}

    public function search(ProductFilterDTO $dto): LengthAwarePaginator
    {
        return $this->queryBuilder
            ->build($dto)
            ->paginate($dto->perPage);
    }
}
