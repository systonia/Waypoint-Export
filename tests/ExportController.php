<?php

namespace Waypoint\Export\Tests;

use Waypoint\Attributes\{Controller, Get, Summary};
use Waypoint\Export\Sheet;
use Waypoint\Http\View;

#[Controller]
class ExportController
{
    #[Get('/users.csv')]
    #[Summary('Download the users')]
    public function csv(): Sheet
    {
        return $this->sheet('users.csv');
    }

    #[Get('/users.xlsx')]
    public function xlsx(): Sheet
    {
        return $this->sheet('users.xlsx');
    }

    #[Get('/users.pdf')]
    public function pdf(): Sheet
    {
        return $this->sheet('users.pdf');
    }

    #[Get('/page')]
    public function page(): View
    {
        return new View('Page', ['sheet' => $this->sheet('inline.html')]);
    }

    #[Get('/plain')]
    public function plain(): array
    {
        return ['ok' => true];
    }

    private function sheet(string $filename): Sheet
    {
        return Sheet::file($filename)->columns(['id' => 'ID', 'name' => 'Name'])->rows([['id' => 1, 'name' => 'Ada'], ['id' => 2, 'name' => 'Grace']]);
    }
}
