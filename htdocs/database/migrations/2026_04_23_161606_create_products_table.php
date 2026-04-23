<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->foreignId('category_id')->constrained();
            $table->boolean('in_stock')->default(true);
            $table->float('rating')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // FULLTEXT-индексы для полнотекстового поиска в MySQL/MariaDB.
            // В текущей реализации поиск выполняется через LIKE, чтобы сохранить
            // совместимость с SQLite в feature-тестах без усложнения кода в рамках тестового задания.
            // Раздельные индексы на name и description позволяют задавать веса полей через
            // вычисляемый score: MATCH(name) * 3 + MATCH(description).
            // При переходе на Elasticsearch индексы можно удалить за ненадобностью.

            // $table->fullText('name');
            // $table->fullText('description');

            $table->index('price');
            $table->index('rating');
            $table->index('in_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
