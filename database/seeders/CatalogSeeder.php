<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\FilterGroup;
use App\Models\FilterValue;
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

        $this->seedFilters();
        $this->seedVariants();
        $this->seedRatings();
    }

    /**
     * Группы фильтров на корневые категории + назначение значений товарам
     * по их полям (ripening / growing_place / is_hybrid).
     */
    private function seedFilters(): void
    {
        $definitions = [
            'Овощи' => [
                'Срок созревания' => ['Ранние' => 'early', 'Средние' => 'mid', 'Поздние' => 'late'],
                'Место выращивания' => ['Открытый грунт' => 'open_ground', 'Теплица' => 'greenhouse', 'Универсальное' => 'universal'],
                'Гибрид' => ['Да (F1)' => 'yes', 'Нет' => 'no'],
            ],
            'Зелень и пряности' => [
                'Срок созревания' => ['Ранние' => 'early', 'Средние' => 'mid', 'Поздние' => 'late'],
            ],
            'Цветы' => [
                'Период цветения' => ['Лето' => 'summer', 'Весь сезон' => 'all_season'],
            ],
        ];

        foreach ($definitions as $rootName => $groups) {
            $root = Category::whereNull('parent_id')->where('name', $rootName)->first();

            if ($root === null) {
                continue;
            }

            foreach ($groups as $groupName => $values) {
                $group = FilterGroup::create([
                    'category_id' => $root->id,
                    'name' => $groupName,
                    'slug' => \Illuminate\Support\Str::slug($groupName),
                    'sort_order' => 0,
                ]);

                foreach ($values as $valueName => $key) {
                    $filterValue = FilterValue::create([
                        'filter_group_id' => $group->id,
                        'value' => $valueName,
                        'slug' => \Illuminate\Support\Str::slug($valueName),
                        'sort_order' => 0,
                    ]);

                    // Назначаем товарам, у которых соответствующее поле заполнено.
                    $products = match ($groupName) {
                        'Срок созревания' => Product::whereNotNull('ripening')
                            ->where('ripening', $key)
                            ->whereIn('category_id', $root->selfAndChildrenIds()),
                        'Место выращивания' => Product::whereNotNull('growing_place')
                            ->where('growing_place', $key)
                            ->whereIn('category_id', $root->selfAndChildrenIds()),
                        'Гибрид' => Product::whereNotNull('is_hybrid')
                            ->where('is_hybrid', $key === 'yes')
                            ->whereIn('category_id', $root->selfAndChildrenIds()),
                        'Период цветения' => Product::whereIn('category_id', $root->selfAndChildrenIds())
                            ->inRandomOrder()->take(40),
                        default => null,
                    };

                    $products?->each(
                        fn (Product $product) => $product->filterValues()->syncWithoutDetaching([$filterValue->id])
                    );
                }
            }
        }
    }

    /**
     * Варианты товара: ~10% товаров получают 2–3 варианта («10/20/50 семян»).
     * Цены вариантов выше основной — основная остаётся "ценой от".
     */
    private function seedVariants(): void
    {
        Product::query()
            ->where('status', ProductStatus::Published)
            ->inRandomOrder()
            ->take(29)
            ->each(function (Product $product) {
                $base = $product->price;

                $variants = [
                    ['label' => '10 семян', 'price' => $base, 'in_stock' => true],
                    ['label' => '20 семян', 'price' => (int) ($base * 1.6), 'in_stock' => random_int(1, 100) <= 80],
                    ['label' => '50 семян', 'price' => (int) ($base * 2.5), 'in_stock' => random_int(1, 100) <= 80],
                ];

                foreach ($variants as $i => $variant) {
                    $product->variants()->create([
                        'label' => $variant['label'],
                        'price' => $variant['price'],
                        'in_stock' => $variant['in_stock'],
                        'sort_order' => $i,
                    ]);
                }
            });
    }

    /**
     * Тестовые рейтинги (~20% товаров) — задел под этап отзывов.
     */
    private function seedRatings(): void
    {
        Product::query()
            ->where('status', ProductStatus::Published)
            ->inRandomOrder()
            ->take(58)
            ->each(function (Product $product) {
                $product->forceFill(['rating' => round(random_int(35, 50) / 10, 1)])->saveQuietly();
            });
    }
}
