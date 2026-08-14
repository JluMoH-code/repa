<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Дерево категорий: "Родитель" => ["Дочерняя 1", "Дочерняя 2", ...].
     * Даёт ~25 категорий с двумя уровнями вложенности.
     */
    private const CATEGORY_TREE = [
        'Овощи' => ['Томаты', 'Огурцы', 'Перец', 'Баклажаны', 'Капуста', 'Морковь и свёкла', 'Лук и чеснок', 'Тыквенные'],
        'Зелень и пряности' => ['Укроп и петрушка', 'Базилик', 'Салаты', 'Пряные травы'],
        'Цветы' => ['Однолетние', 'Многолетние', 'Ампельные'],
        'Бобовые' => ['Горох', 'Фасоль'],
        'Сопутствующие товары' => ['Грунт и удобрения', 'Инвентарь'],
    ];

    public function run(): void
    {
        $manufacturers = Manufacturer::factory()->count(12)->create();

        $categories = collect();

        foreach (self::CATEGORY_TREE as $parentName => $children) {
            $parent = Category::factory()->create([
                'name' => $parentName,
                'is_active' => true,
            ]);

            $categories->push($parent);

            foreach ($children as $childName) {
                $child = Category::factory()->create([
                    'name' => $childName,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]);

                $categories->push($child);
            }
        }

        // Товары раскладываем только по листовым (дочерним) категориям —
        // в родительских категориях-разделах их не заводим.
        $leafCategories = $categories->filter(fn (Category $category) => $category->parent_id !== null);

        Product::factory()
            ->count(260)
            ->published()
            ->create([
                'category_id' => fn () => $leafCategories->random()->id,
                'manufacturer_id' => fn () => random_int(0, 4) === 0 ? null : $manufacturers->random()->id,
            ])
            ->each(function (Product $product) {
                $imagesCount = random_int(1, 4);

                for ($i = 0; $i < $imagesCount; $i++) {
                    ProductImage::factory()->create([
                        'product_id' => $product->id,
                        'sort_order' => $i,
                        'is_main' => $i === 0,
                    ]);
                }

                // ~15% товаров попадают в "Хиты продаж" и ~15% в "Рекомендуем"
                // (для витрины на этом этапе — без реальной статистики продаж).
                $product->forceFill([
                    'is_bestseller' => random_int(1, 100) <= 15,
                    'is_recommended' => random_int(1, 100) <= 15,
                ])->saveQuietly();
            });

        // Небольшая доля черновиков и архивных — чтобы фильтры в админке было на чём проверять.
        Product::factory()->count(20)->draft()->create([
            'category_id' => fn () => $leafCategories->random()->id,
            'manufacturer_id' => fn () => random_int(0, 4) === 0 ? null : $manufacturers->random()->id,
        ]);

        Product::factory()->count(10)->create([
            'status' => ProductStatus::Archived,
            'category_id' => fn () => $leafCategories->random()->id,
            'manufacturer_id' => fn () => random_int(0, 4) === 0 ? null : $manufacturers->random()->id,
        ]);
    }
}
