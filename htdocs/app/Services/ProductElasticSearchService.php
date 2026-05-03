<?php

// это просто вариант, чтобы показать, что если у нас полнотекстовый поиск будет проходить через elasticsearch
// чтобы не усложнять структуру тестового проекта, то это просто код "на показ"
// Требуется: composer require elasticsearch/elasticsearch
// Требуется: добавить контейнер elasticsearch
// В .env добавить: ELASTICSEARCH_HOST=http://elasticsearch:9200

namespace App\Services;

use App\Contracts\ProductSearchServiceInterface;
use App\DTOs\ProductFilterDTO;
use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ProductElasticSearchService implements ProductSearchServiceInterface
{
    private const SORT_MAP = [
        'price_asc'   => [['price'      => ['order' => 'asc']],  ['_score' => ['order' => 'desc']]],
        'price_desc'  => [['price'      => ['order' => 'desc']], ['_score' => ['order' => 'desc']]],
        'rating_desc' => [['rating'     => ['order' => 'desc']], ['_score' => ['order' => 'desc']]],
        'newest'      => [['created_at' => ['order' => 'desc']], ['_score' => ['order' => 'desc']]],
    ];

    public function __construct(
        private readonly Client $elastic,
    ) {}

    public function search(ProductFilterDTO $dto): LengthAwarePaginator
    {
        $ids = $this->searchInElastic($dto);

        if (empty($ids)) {
            return (new LengthAwarePaginator([], 0, $dto->perPage, Paginator::resolveCurrentPage()))
                ->withQueryString();
        }

        return $this->fetchByIds($ids)->paginate($dto->perPage)->withQueryString();
    }

    private function fetchByIds(array $ids): Builder
    {
        return Product::query()
            ->with('category')
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
    }

    /**
     * Поиск/листинг через ES: фильтрация и сортировка на стороне ES.
     * MySQL получает только отсортированные/отфильтрованные ID и делает SELECT по ним.
     *
     * @return int[]
     */
    private function searchInElastic(ProductFilterDTO $dto): array
    {
        $must = $dto->q !== null
            ? [['multi_match' => ['query' => $dto->q, 'fields' => ['name^3', 'description'], 'type' => 'best_fields', 'fuzziness' => 'AUTO']]]
            : [['match_all'   => new \stdClass()]];

        $body = [
            'query' => [
                'bool' => [
                    'must'   => $must,
                    'filter' => $this->buildFilters($dto),
                ],
            ],
            'sort'    => self::SORT_MAP[$dto->sort] ?? [['_score' => ['order' => 'desc']]],
            '_source' => false,
            'size'    => 1000,
        ];

        $response = $this->elastic->search([
            'index' => 'products',
            'body'  => $body,
        ]);

        return array_map('intval', array_column($response['hits']['hits'], '_id'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFilters(ProductFilterDTO $dto): array
    {
        $filters = [];

        if ($dto->priceFrom !== null) {
            $filters[] = ['range' => ['price' => ['gte' => $dto->priceFrom]]];
        }
        if ($dto->priceTo !== null) {
            $filters[] = ['range' => ['price' => ['lte' => $dto->priceTo]]];
        }
        if ($dto->categoryId !== null) {
            $filters[] = ['term' => ['category_id' => $dto->categoryId]];
        }
        if ($dto->inStock !== null) {
            $filters[] = ['term' => ['in_stock' => $dto->inStock]];
        }
        if ($dto->ratingFrom !== null) {
            $filters[] = ['range' => ['rating' => ['gte' => $dto->ratingFrom]]];
        }

        return $filters;
    }
}
