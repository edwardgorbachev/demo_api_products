<?php

namespace App\Contracts;

use App\DTOs\ProductFilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductSearchServiceInterface
{
    public function search(ProductFilterDTO $dto): LengthAwarePaginator;
}
