<?php

namespace App\Filament\Pages;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class ImportProducts extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.import-products';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Импорт товаров';

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 4;

    public $csvFile;

    public bool $previewReady = false;

    /** @var array<int, array<string, mixed>> */
    public array $preview = [];

    public ?array $report = null;

    /**
     * Ожидаемые заголовки колонок (регистронезависимо) и их внутренние ключи.
     */
    private const COLUMN_MAP = [
        'артикул' => 'sku',
        'штрихкод' => 'barcode',
        'название' => 'name',
        'цена' => 'price',
        'категория' => 'category',
        'производитель' => 'manufacturer',
    ];

    public function runPreview(): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt'],
        ], [], ['csvFile' => 'файл']);

        $rows = $this->parseCsv($this->csvFile->getRealPath());

        $existingSkus = Product::query()->pluck('id', 'sku');
        $categories = Category::query()->get()->keyBy(fn (Category $c) => Str::lower($c->name));

        $preview = [];

        foreach ($rows as $lineNumber => $row) {
            $errors = [];

            $sku = trim((string) ($row['sku'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $priceRaw = trim((string) ($row['price'] ?? ''));
            $categoryName = trim((string) ($row['category'] ?? ''));
            $manufacturerName = trim((string) ($row['manufacturer'] ?? ''));
            $barcode = trim((string) ($row['barcode'] ?? '')) ?: null;

            if ($sku === '') {
                $errors[] = 'не заполнен артикул';
            }

            if ($name === '') {
                $errors[] = 'не заполнено название';
            }

            $price = null;
            if ($priceRaw === '') {
                $errors[] = 'не заполнена цена';
            } else {
                $normalized = str_replace(',', '.', $priceRaw);
                if (! is_numeric($normalized) || (float) $normalized < 0) {
                    $errors[] = 'некорректная цена';
                } else {
                    $price = (int) round(((float) $normalized) * 100);
                }
            }

            $category = $categoryName !== '' ? $categories->get(Str::lower($categoryName)) : null;
            if ($categoryName !== '' && ! $category) {
                $errors[] = "категория «{$categoryName}» не найдена";
            } elseif ($categoryName === '' && ! isset($existingSkus[$sku])) {
                // Категория обязательна только для новых товаров — у существующих
                // при обновлении категорию менять через импорт не обязываем.
                $errors[] = 'не указана категория (обязательна для нового товара)';
            }

            $action = isset($existingSkus[$sku]) ? 'update' : 'create';

            $preview[] = [
                'line' => $lineNumber,
                'sku' => $sku,
                'name' => $name,
                'price_raw' => $priceRaw,
                'category' => $categoryName,
                'manufacturer' => $manufacturerName,
                'barcode' => $barcode,
                'action' => $errors ? 'error' : $action,
                'errors' => $errors,
                '_category_id' => $category?->id,
                '_price' => $price,
            ];
        }

        $this->preview = $preview;
        $this->previewReady = true;
        $this->report = null;
    }

    public function applyImport(): void
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->preview as $row) {
            if ($row['action'] === 'error') {
                $skipped++;

                continue;
            }

            $manufacturerId = null;
            if (! empty($row['manufacturer'])) {
                $manufacturerId = Manufacturer::query()->firstOrCreate(
                    ['name' => $row['manufacturer']],
                )->id;
            }

            if ($row['action'] === 'create') {
                Product::create([
                    'category_id' => $row['_category_id'],
                    'manufacturer_id' => $manufacturerId,
                    'name' => $row['name'],
                    'sku' => $row['sku'],
                    'barcode' => $row['barcode'],
                    'price' => $row['_price'],
                    'status' => ProductStatus::Draft,
                ]);
                $created++;
            } else {
                // Импорт не затирает поля, которых нет в файле (description,
                // images, attributes и т.д.) — обновляем только колонки из CSV.
                $product = Product::where('sku', $row['sku'])->first();

                if (! $product) {
                    $skipped++;

                    continue;
                }

                $product->update(array_filter([
                    'name' => $row['name'],
                    'price' => $row['_price'],
                    'barcode' => $row['barcode'],
                    'manufacturer_id' => $manufacturerId ?: $product->manufacturer_id,
                    'category_id' => $row['_category_id'] ?: $product->category_id,
                ], fn ($value) => $value !== null));
                $updated++;
            }
        }

        $this->report = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];

        $this->previewReady = false;
        $this->preview = [];
        $this->csvFile = null;

        Notification::make()
            ->title("Импорт завершён: создано {$created}, обновлено {$updated}, пропущено {$skipped}")
            ->success()
            ->send();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        $header = array_map(fn ($h) => Str::lower(trim((string) $h, "\xEF\xBB\xBF ")), $header);

        $keys = array_map(fn ($h) => self::COLUMN_MAP[$h] ?? null, $header);

        $rows = [];
        $lineNumber = 1;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;

            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($keys as $index => $key) {
                if ($key !== null) {
                    $row[$key] = $data[$index] ?? '';
                }
            }

            $rows[$lineNumber] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
