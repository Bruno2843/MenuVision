<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed some example tables.
     */
    public function run(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            Table::updateOrCreate(
                ['numero' => (string) $number],
                ['estado' => 'libre']
            );
        }
    }
}
