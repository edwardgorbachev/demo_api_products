# Demo Products API

REST API для поиска и фильтрации товаров маркетплейса.

## Требования

- Docker
- Docker Compose

## Запуск

```bash
cp htdocs/.env.example htdocs/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Приложение доступно по адресу: http://localhost:8080

## Документация API

Swagger UI: http://localhost:8080/docs

## Тесты

```bash
docker compose exec app php artisan test
```

## Примеры запросов

```bash
# Все товары
curl http://localhost:8080/api/products

# Поиск по названию или описанию
curl "http://localhost:8080/api/products?q=телефон"

# Фильтр по цене
curl "http://localhost:8080/api/products?price_from=1000&price_to=5000"

# Только в наличии, рейтинг от 4, сортировка по цене
curl "http://localhost:8080/api/products?in_stock=1&rating_from=4&sort=price_asc"

# Фильтр по категории с пагинацией
curl "http://localhost:8080/api/products?category_id=1&per_page=10&page=2"
```

## Параметры фильтрации

| Параметр | Тип | Описание |
|---|---|---|
| `q` | string | Поиск по названию и описанию |
| `price_from` | number | Цена от |
| `price_to` | number | Цена до |
| `category_id` | integer | ID категории |
| `in_stock` | boolean (1/0) | Наличие |
| `rating_from` | number (0–5) | Рейтинг от |
| `sort` | string | Сортировка: `price_asc`, `price_desc`, `rating_desc`, `newest` |
| `per_page` | integer (1–100) | Записей на странице (по умолчанию 15) |
| `page` | integer | Номер страницы |

## Отличия от ТЗ

Реализация намеренно расширена в нескольких местах — чтобы показать чуть более реалистичный подход к production-коду.

**Дополнительные поля товара:**
- `description (text)` — описание товара.
- `deleted_at` (soft deletes) — физическое удаление товара недопустимо: он может присутствовать в истории заказов, избранном, аналитике. Каскадного удаления при удалении категории нет по той же причине

**Поиск по `q`:**
- ТЗ требует поиск по `name`. Реализован поиск по `name` и `description` — логичное поведение для покупателя

**Поиск:**
- Реализован через `LIKE` по полям `name` и `description` с приоритетом совпадений в `name`
- В миграции FULLTEXT-индекс закомментирован — в текущей реализации он не используется, чтобы не усложнять совместимость с SQLite в feature-тестах. Комментарий в коде показывает как переключиться на `MATCH ... AGAINST` при необходимости
- В production-проекте для полнотекстового поиска предпочтительнее использовать выделенный поисковой движок

**Elasticsearch (референс-реализация):**
- `ProductElasticSearchService` — набросок альтернативной реализации поиска через Elasticsearch, без инфраструктурной части (контейнер ES, индексирование данных). Показывает архитектурный подход: ES выполняет полнотекстовый поиск и возвращает релевантные `id`, MySQL применяет фильтры и пагинацию по этим `id`
- Оба сервиса реализуют `ProductSearchServiceInterface` — контроллер не зависит от конкретной реализации. Переключение через `.env`: `SEARCH_DRIVER=elastic`
