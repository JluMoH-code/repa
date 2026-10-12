<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\FilterGroups\Pages\CreateFilterGroup;
use App\Filament\Resources\FilterGroups\Pages\ListFilterGroups;
use App\Filament\Resources\Manufacturers\Pages\CreateManufacturer;
use App\Filament\Resources\Manufacturers\Pages\ListManufacturers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogueEditorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_lists_and_create_forms_render(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([ListCategories::class, CreateCategory::class, ListManufacturers::class, CreateManufacturer::class, ListFilterGroups::class, CreateFilterGroup::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }
}
