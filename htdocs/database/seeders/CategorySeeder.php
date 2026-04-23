<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Электроника',
            'Одежда и обувь',
            'Дом и сад',
            'Спорт и отдых',
            'Детские товары',
            'Красота и здоровье',
            'Книги',
            'Автотовары',
            'Продукты питания',
            'Строительство и ремонт',
        ];

        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}
