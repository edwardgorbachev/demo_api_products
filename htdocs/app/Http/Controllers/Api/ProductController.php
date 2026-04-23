<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ProductSearchServiceInterface;
use App\DTOs\ProductFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductFilterRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductSearchServiceInterface $searchService,
    ) {}

    public function index(ProductFilterRequest $request): AnonymousResourceCollection
    {
        $dto = ProductFilterDTO::fromRequest($request);

        return ProductResource::collection(
            $this->searchService->search($dto),
        );
    }
}
