<?php

namespace App\Filament\Resources\RoleApplications\Pages;

use App\Filament\Resources\RoleApplications\RoleApplicationResource;
use App\Filament\Widgets\PartnersOwedStats;
use Filament\Resources\Pages\ListRecords;

class ListRoleApplications extends ListRecords
{
    protected static string $resource = RoleApplicationResource::class;

    protected function getHeaderWidgets(): array
    {
        return [PartnersOwedStats::class];
    }
}
