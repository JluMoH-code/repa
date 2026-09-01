<?php

namespace App\Http\Controllers;

use App\Models\Category;

class DemoProductPageController extends Controller
{
    /**
     * Демо-страница карточки товара для категории "Средства защиты растений".
     * Эта товарная категория пока не описана в схеме БД (products заточен под
     * семена), поэтому здесь используются статичные демо-данные — страница
     * показывает вёрстку/стиль, а не реальную карточку из каталога.
     */
    public function show()
    {
        $footerCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $breadcrumbs = [
            ['label' => 'Главная', 'url' => route('storefront')],
            ['label' => 'Средства защиты растений', 'url' => '#'],
            ['label' => 'Гербициды', 'url' => '#'],
            ['label' => 'Саунар'],
        ];

        $galleryImages = [
            ['src' => asset('storage/demo/product-main.svg'), 'label' => 'САУНАР 0,5 КГ'],
            ['src' => asset('storage/demo/product-thumb-1.svg'), 'label' => 'САУНАР 0,5 КГ'],
            ['src' => asset('storage/demo/product-thumb-2.svg'), 'label' => 'САУНАР 1 КГ'],
            ['src' => asset('storage/demo/product-thumb-3.svg'), 'label' => 'САУНАР 5 КГ'],
        ];

        $characteristics = [
            ['label' => 'Производитель', 'value' => 'ООО «Пример Агрохим»'],
            ['label' => 'Тип препарата', 'value' => 'Гербицид сплошного действия'],
            ['label' => 'Действующее вещество', 'value' => 'Глифосат, 500 г/л', 'highlight' => true],
            ['label' => 'Химический класс', 'value' => 'Производные глицина'],
            ['label' => 'Класс опасности для человека', 'value' => '3 класс (умеренно опасный)'],
            ['label' => 'Класс опасности для пчёл', 'value' => '3 класс (малоопасный)'],
            ['label' => 'Препаративная форма', 'value' => 'Водорастворимый концентрат (ВР)'],
            ['label' => 'Область применения', 'value' => 'Личные подсобные хозяйства, сельхозпредприятия'],
        ];

        $packaging = [
            ['label' => 'Флакон 0,5 кг', 'price' => 890],
            ['label' => 'Канистра 1 кг', 'price' => 1590],
            ['label' => 'Канистра 5 кг', 'price' => 6990],
        ];

        $similarProducts = [
            ['src' => asset('storage/demo/similar-1.svg'), 'name' => 'Гербицид А'],
            ['src' => asset('storage/demo/similar-2.svg'), 'name' => 'Гербицид Б'],
            ['src' => asset('storage/demo/similar-3.svg'), 'name' => 'Гербицид В'],
        ];

        $descriptionHtml = <<<'HTML'
            <div class="space-y-4 text-sm leading-relaxed text-slate-600">
                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Описание</h4>
                    <p>Гербицид сплошного действия для уничтожения однолетних и многолетних сорных растений на садовых участках, огородах и вдоль ограждений.</p>
                </div>

                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Культуры применения</h4>
                    <p>Пары, участки перед посадкой овощных и плодовых культур, приствольные круги.</p>
                </div>

                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Принцип действия</h4>
                    <p>Действующее вещество проникает через листья и перемещается по всему растению, включая корневую систему, вызывая полное отмирание сорняка.</p>
                </div>

                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Эффективность</h4>
                    <p>Первые видимые признаки — через 5–7 дней после обработки, полная гибель растения — через 10–20 дней в зависимости от вида сорняка и погодных условий.</p>
                </div>

                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Класс опасности</h4>
                    <p>3 класс опасности для человека (умеренно опасное вещество), 3 класс опасности для пчёл.</p>
                </div>

                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Механизм действия</h4>
                    <p>Ингибирует фермент, необходимый для синтеза аминокислот в растении, что приводит к остановке роста и последующей гибели сорняка.</p>
                </div>

                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Рекомендации по применению</h4>
                    <p>Обработку проводить в сухую безветренную погоду при температуре воздуха от +10°C до +25°C. Не рекомендуется обработка перед дождём.</p>
                </div>

                <div>
                    <h4 class="mb-1 font-semibold text-slate-900">Подавляемые сорняки</h4>
                    <p>Пырей ползучий, осот, одуванчик, полынь, вьюнок полевой и другие однолетние и многолетние сорные растения.</p>
                </div>

                <h4 class="mt-6 font-semibold text-slate-900">Регламент применения</h4>
            </div>

            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full border-collapse overflow-hidden rounded-lg border border-slate-200 text-sm">
                    <thead>
                        <tr class="bg-brand-50 text-brand-700">
                            <th class="border border-slate-200 px-3 py-2 text-left font-semibold">Норма применения</th>
                            <th class="border border-slate-200 px-3 py-2 text-left font-semibold">Культура</th>
                            <th class="border border-slate-200 px-3 py-2 text-left font-semibold">Вредный объект</th>
                            <th class="border border-slate-200 px-3 py-2 text-left font-semibold">Способ обработки</th>
                            <th class="border border-slate-200 px-3 py-2 text-left font-semibold">Срок ожидания (кратность обработок)</th>
                            <th class="border border-slate-200 px-3 py-2 text-left font-semibold">Срок выхода для ручных (механизированных) работ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-white">
                            <td class="border border-slate-200 px-3 py-2">80–120 мл/10 л воды</td>
                            <td class="border border-slate-200 px-3 py-2">Пары, участки под посадку</td>
                            <td class="border border-slate-200 px-3 py-2">Однолетние сорняки</td>
                            <td class="border border-slate-200 px-3 py-2">Опрыскивание по вегетирующим сорнякам</td>
                            <td class="border border-slate-200 px-3 py-2">1 обработка за сезон</td>
                            <td class="border border-slate-200 px-3 py-2">3 (—) дня</td>
                        </tr>
                        <tr class="bg-[#F5F5F5]">
                            <td class="border border-slate-200 px-3 py-2">100–150 мл/10 л воды</td>
                            <td class="border border-slate-200 px-3 py-2">Приствольные круги</td>
                            <td class="border border-slate-200 px-3 py-2">Многолетние сорняки</td>
                            <td class="border border-slate-200 px-3 py-2">Направленное опрыскивание</td>
                            <td class="border border-slate-200 px-3 py-2">1 обработка за сезон</td>
                            <td class="border border-slate-200 px-3 py-2">3 (—) дня</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            HTML;

        $tabs = [
            ['label' => 'Описание', 'content' => $descriptionHtml],
            ['label' => 'Отзывы', 'content' => '<p class="text-sm text-slate-500">Отзывов пока нет.</p>'],
            ['label' => 'Схема применения', 'content' => '<p class="text-sm text-slate-500">Информация появится позже.</p>'],
            ['label' => 'Доставка', 'content' => '<p class="text-sm text-slate-500">Доставка по всей России, подробности — при подтверждении заказа.</p>'],
        ];

        return view('demo-product', [
            'footerCategories' => $footerCategories,
            'breadcrumbs' => $breadcrumbs,
            'galleryImages' => $galleryImages,
            'characteristics' => $characteristics,
            'packaging' => $packaging,
            'similarProducts' => $similarProducts,
            'tabs' => $tabs,
        ]);
    }
}
