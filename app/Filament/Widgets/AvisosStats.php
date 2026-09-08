<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AvisosStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Avisos', Post::count())
                ->description(Post::publicados()->count() . ' publicados')
                ->color('success'),
            Stat::make('Borradores', Post::where('publicado', false)->count())
                ->description('pendientes de revisar')
                ->color('warning'),
        ];
    }
}
