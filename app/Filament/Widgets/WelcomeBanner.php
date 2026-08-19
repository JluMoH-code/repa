<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeBanner extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.welcome-banner';

    protected int|string|array $columnSpan = 'full';
}
