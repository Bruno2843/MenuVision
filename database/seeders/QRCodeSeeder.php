<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\QRCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QRCodeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a QR code for every table.
     */
    public function run(): void
    {
        Table::all()->each(function (Table $table): void {
            QRCode::firstOrCreate(
                ['table_id' => $table->id],
                ['code' => Str::random(16)]
            );
        });
    }
}