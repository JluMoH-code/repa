<?php

namespace App\Filament\Pages;

use App\Actions\Inventory\InventoryManager;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Inventory extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.inventory';

    protected static ?string $title = 'Остатки';

    protected static ?string $navigationLabel = 'Остатки';

    protected static string|\UnitEnum|null $navigationGroup = 'Магазин';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 2;

    #[Url]
    public string $operation = '';

    public bool $modalOpen = false;

    #[Url]
    public string $search = '';

    public bool $lowOnly = false;

    #[Url]
    public string $filter = 'all';

    public ?int $productId = null;

    public ?int $variantId = null;

    public string $quantity = '';

    public string $unitPrice = '';

    public string $note = '';

    public string $modalProductSearch = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() && ! auth()->user()->is_blocked;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        if (! in_array($this->filter, ['all', 'low', 'empty', 'unknown'], true)) {
            $this->filter = 'all';
        }
        if (in_array($this->operation, ['receive', 'correct', 'offline_sale'], true)) {
            $this->modalOpen = true;
        }
    }

    public function open(string $operation, ?int $productId = null, ?int $variantId = null): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(in_array($operation, ['receive', 'correct', 'offline_sale'], true), 422);
        $this->resetValidation();
        $this->operation = $operation;
        $this->modalOpen = true;
        $this->dispatch('open-modal', id: 'inventory-operation');
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->quantity = $this->note = '';
        $this->modalProductSearch = '';
        $product = $productId ? Product::findOrFail($productId) : null;
        $variant = $variantId ? $product?->variants()->findOrFail($variantId) : null;
        if ($product && ! $variant && $product->variants()->exists()) {
            $variant = $product->variants()->first();
            $this->variantId = $variant->id;
        }
        $kopecks = $variant?->price ?? $product?->price;
        $this->unitPrice = $kopecks !== null ? intdiv($kopecks, 100).','.str_pad((string) ($kopecks % 100), 2, '0', STR_PAD_LEFT) : '';
        if ($operation === 'correct' && $product) {
            $this->quantity = (string) (($variant ?? $product)->stock_quantity ?? '');
        }
    }

    public function manage(int $productId, ?int $variantId = null): void
    {
        abort_unless(static::canAccess(), 403);
        $product = Product::findOrFail($productId);
        $variant = $variantId ? $product->variants()->findOrFail($variantId) : $product->variants()->first();
        $stock = $variant ?? $product;
        $this->open($stock->stock_quantity === null ? 'correct' : 'receive', $productId, $variant?->id);
    }

    public function switchOperation(string $operation): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(in_array($operation, ['receive', 'correct', 'offline_sale'], true), 422);
        $this->operation = $operation;
        $this->quantity = '';
        $this->resetValidation();
        if ($operation === 'correct' && $this->productId) {
            $product = Product::findOrFail($this->productId);
            $stock = $this->variantId ? $product->variants()->findOrFail($this->variantId) : $product;
            $this->quantity = (string) ($stock->stock_quantity ?? '');
        }
    }

    public function setFilter(string $filter): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(in_array($filter, ['all', 'low', 'empty', 'unknown'], true), 422);
        $this->filter = $filter;
        $this->lowOnly = $filter === 'low';
        $this->resetPage();
    }

    public function close(): void
    {
        $this->operation = '';
        $this->modalOpen = false;
        $this->dispatch('close-modal', id: 'inventory-operation');
        $this->modalProductSearch = '';
        $this->resetValidation();
    }

    public function updatedModalOpen(): void
    {
        if (! $this->modalOpen) {
            $this->operation = '';
            $this->modalProductSearch = '';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLowOnly(): void
    {
        $this->filter = $this->lowOnly ? 'low' : 'all';
        $this->resetPage();
    }

    public function updatedProductId(): void
    {
        $this->variantId = $this->productId ? ProductVariant::where('product_id', $this->productId)->orderBy('sort_order')->value('id') : null;
        $this->resetTargetQuantity();
        $this->fillPrice();
    }

    public function updatedVariantId(): void
    {
        $this->resetTargetQuantity();
        $this->fillPrice();
    }

    private function resetTargetQuantity(): void
    {
        $this->quantity = '';
        $this->resetValidation();
        if ($this->operation === 'correct' && $this->productId) {
            $product = Product::find($this->productId);
            $stock = $this->variantId ? $product?->variants()->find($this->variantId) : $product;
            $this->quantity = (string) ($stock?->stock_quantity ?? '');
        }
    }

    private function fillPrice(): void
    {
        $product = $this->productId ? Product::find($this->productId) : null;
        $variant = $this->variantId ? $product?->variants()->find($this->variantId) : null;
        $price = $variant?->price ?? $product?->price;
        $this->unitPrice = $price === null ? '' : intdiv($price, 100).','.str_pad((string) ($price % 100), 2, '0', STR_PAD_LEFT);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->validate([
            'operation' => ['required', 'in:receive,correct,offline_sale'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'variantId' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', $this->operation === 'correct' ? 'min:0' : 'min:1', 'max:2147483647'],
            'unitPrice' => [$this->operation === 'offline_sale' ? 'required' : 'nullable', 'regex:/^\d{1,9}([.,]\d{1,2})?$/'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [], ['quantity' => 'количество упаковок', 'productId' => 'товар', 'unitPrice' => 'цена в рублях']);
        $product = Product::findOrFail($data['productId']);
        $variant = $data['variantId'] ? $product->variants()->findOrFail($data['variantId']) : null;
        $inventory = app(InventoryManager::class);
        match ($this->operation) {
            'receive' => $inventory->receive($product, (int) $data['quantity'], $variant, $this->note),
            'correct' => $inventory->correct($product, (int) $data['quantity'], $variant, $this->note),
            'offline_sale' => $inventory->offlineSale($product, (int) $data['quantity'], $this->priceInKopecks($data['unitPrice']), $variant, $this->note),
        };
        $this->close();
        Notification::make()->title('Остатки обновлены')->success()->send();
    }

    private function priceInKopecks(string $price): int
    {
        $parts = explode('.', str_replace(',', '.', $price));

        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    protected function getViewData(): array
    {
        $products = Product::query()->with(['variants', 'images'])->when($this->search !== '', fn ($q) => $q->where(function ($q) {
            $q->where('name', 'like', '%'.$this->search.'%')->orWhere('sku', 'like', '%'.$this->search.'%')->orWhereHas('variants', fn ($variants) => $variants->where('label', 'like', '%'.$this->search.'%'));
        }))->when($this->filter !== 'all', fn ($q) => $q->where(function ($q) {
            $q->where(function ($base) {
                $base->whereDoesntHave('variants');
                $this->applyStockFilter($base, $this->filter);
            })->orWhereHas('variants', fn ($variants) => $this->applyStockFilter($variants, $this->filter));
        }))->orderBy('name')->paginate(20);

        $rows = $products->getCollection()->flatMap(function ($product) {
            return ($product->variants->isNotEmpty() ? $product->variants : collect([$product]))
                ->filter(fn ($stock) => match ($this->filter) {
                    'unknown' => $stock->stock_quantity === null,
                    'empty' => $stock->availableQuantity() === 0,
                    'low' => $stock->stock_quantity !== null && $stock->availableQuantity() <= 5,
                    default => true,
                })->map(fn ($stock) => ['product' => $product, 'stock' => $stock]);
        });
        $base = Product::whereDoesntHave('variants');
        $variants = ProductVariant::query();
        $counts = [];
        foreach (['all', 'low', 'empty', 'unknown'] as $filter) {
            $counts[$filter] = $this->applyStockFilter(clone $base, $filter)->count() + $this->applyStockFilter(clone $variants, $filter)->count();
        }
        $trackedBase = (clone $base)->whereNotNull('stock_quantity');
        $trackedVariants = (clone $variants)->whereNotNull('stock_quantity');
        $reserved = (int) (clone $base)->sum('reserved_quantity') + (int) (clone $variants)->sum('reserved_quantity');
        $physical = (int) $trackedBase->sum('stock_quantity') + (int) $trackedVariants->sum('stock_quantity');
        $selectedProduct = $this->productId ? Product::find($this->productId) : null;
        $selectedStock = $this->variantId ? $selectedProduct?->variants()->find($this->variantId) : $selectedProduct;
        $term = '%'.mb_strtolower(trim($this->modalProductSearch)).'%';
        $options = Product::query()
            ->when(trim($this->modalProductSearch) !== '', fn ($query) => $query->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(sku) LIKE ?', [$term]);
            }))
            ->when($selectedProduct, fn ($query) => $query->where('id', '!=', $selectedProduct->id))
            ->orderBy('name')->limit($selectedProduct ? 29 : 30)->get(['id', 'name', 'sku']);
        if ($selectedProduct) {
            $options->prepend($selectedProduct);
        }
        $preview = null;
        if ($selectedStock && preg_match('/^\d+$/', $this->quantity)) {
            $quantity = (int) $this->quantity;
            $preview = match ($this->operation) {
                'correct' => $quantity,
                'receive' => ($selectedStock->stock_quantity ?? 0) + $quantity,
                'offline_sale' => $selectedStock->stock_quantity === null ? null : $selectedStock->stock_quantity - $quantity,
                default => null,
            };
        }

        return [
            'products' => $products,
            'rows' => $rows,
            'counts' => $counts,
            'available' => $physical - $reserved,
            'reserved' => $reserved,
            'selectedProduct' => $selectedProduct,
            'selectedStock' => $selectedStock,
            'preview' => $preview,
            'options' => $options,
            'variants' => $this->productId ? ProductVariant::where('product_id', $this->productId)->orderBy('sort_order')->get() : collect(),
            'movements' => InventoryMovement::with(['product', 'variant', 'user'])->latest('id')->limit(30)->get(),
        ];
    }

    private function applyStockFilter($query, string $filter)
    {
        return match ($filter) {
            'unknown' => $query->whereNull('stock_quantity'),
            'empty' => $query->whereNotNull('stock_quantity')->whereRaw('stock_quantity - reserved_quantity = 0'),
            'low' => $query->whereNotNull('stock_quantity')->whereRaw('stock_quantity - reserved_quantity <= 5'),
            default => $query,
        };
    }
}
