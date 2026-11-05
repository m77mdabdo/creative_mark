<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('matrix')->label(__('Roles and permissions'))->icon('heroicon-o-table-cells')->color('gray')
                ->modalHeading(__('What each role can do'))->modalWidth('7xl')->modalSubmitAction(false)->modalCancelActionLabel(__('Close'))
                ->modalContent(fn () => view('filament.role-matrix', UserResource::matrix())),
            CreateAction::make(),
        ];
    }
}
