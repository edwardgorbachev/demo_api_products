<?php

// это просто вариант, чтобы показать, что если у нас полнотекстовый поиск будет проходить через elasticsearch
// чтобы не усложнять структуру тестового проекта, то это просто код "на показ"
// Требуется: composer require elastic/elasticsearch-php
// Требуется: добавить контейнер elasticsearch
// В .env добавить: ELASTICSEARCH_HOST=http://elasticsearch:9200

namespace App\Services;

use App\Contracts\ProductSearchServiceInterface;
use App\DTOs\ProductFilterDTO;
use App\QueryBuilders\ProductQueryBuilder;
use Elastic\Elasticsearch\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ProductElasticSearchService implements ProductSearchServiceInterface
{
    public function __construct(
        private readonly Client $elastic,
        private readonly ProductQueryBuilder $queryBuilder,
    ) {}

    public function search(ProductFilterDTO $dto): LengthAwarePaginator
    {
        $ids = $dto->q !== null
            ? $this->searchInElastic($dto->q)
            : null;

        if ($ids !== null && empty($ids)) {
            return (new LengthAwarePaginator([], 0, $dto->perPage, Paginator::resolveCurrentPage()))
                ->withQueryString();
        }

        $query = $ids !== null
            ? $this->queryBuilder->buildWithIds($dto, $ids)
            : $this->queryBuilder->build($dto);

        return $query->paginate($dto->perPage)->withQueryString();
    }

    /**
     * @return int[]
     */
    private function searchInElastic(string $q): array
    {
        $response = $this->elastic->search([
            'index' => 'products',
            'body'  => [
                'query' => [
                    'multi_match' => [
                        'query'     => $q,
                        'fields'    => ['name^3', 'description'],
                        'type'      => 'best_fields',
                        'fuzziness' => 'AUTO',
                    ],
                ],
                '_source' => false,
                'size'    => 1000,
            ],
        ]);

        return array_column($response['hits']['hits'], '_id');
    }
}
