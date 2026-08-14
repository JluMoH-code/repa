<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ImportProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ImportProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_previews_and_applies_a_csv_import(): void
    {
        $category = Category::factory()->create(['name' => 'Томаты']);
        $existing = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'EXIST-1',
            'name' => 'Старое название',
            'price' => 10000,
        ]);

        $csv = "Артикул,Штрихкод,Название,Цена,Категория,Производитель\n"
            ."NEW-1,,Новый товар,199.90,Томаты,Гавриш\n"
            ."EXIST-1,,Обновлённое название,250.00,Томаты,\n"
            .",,Товар без артикула,100.00,Томаты,\n"
            ."NEW-2,,Товар без категории,100.00,Несуществующая категория,\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $component = Livewire::test(ImportProducts::class)
            ->set('csvFile', $file)
            ->call('runPreview');

        $preview = $component->get('preview');

        $this->assertCount(4, $preview);
        $this->assertSame('create', $preview[0]['action'], json_encode($preview[0], JSON_UNESCAPED_UNICODE));
        $this->assertSame('update', $preview[1]['action']);
        $this->assertSame('error', $preview[2]['action']);
        $this->assertSame('error', $preview[3]['action']);

        $component->call('applyImport');

        $this->assertDatabaseHas('products', [
            'sku' => 'NEW-1',
            'name' => 'Новый товар',
            'price' => 19990,
        ]);

        $existing->refresh();
        $this->assertSame('Обновлённое название', $existing->name);
        $this->assertSame(25000, $existing->price);

        $report = $component->get('report');
        $this->assertSame(1, $report['created']);
        $this->assertSame(1, $report['updated']);
        $this->assertSame(2, $report['skipped']);
    }
}
