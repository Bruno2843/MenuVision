<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedSmallInteger('orden')->default(0)->index();
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->unsignedSmallInteger('orden')->default(0)->index();
        });

        $this->backfill();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropIndex(['orden']);
            $table->dropColumn('orden');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['orden']);
            $table->dropColumn('orden');
        });
    }

    /**
     * Seed the new column with the insertion order of the existing rows, so
     * categories and dishes keep the order they already had on screen.
     */
    private function backfill(): void
    {
        DB::table('categories')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id, int $index): void {
                DB::table('categories')->where('id', $id)->update(['orden' => $index + 1]);
            });

        DB::table('dishes')
            ->orderBy('category_id')
            ->orderBy('id')
            ->get(['id', 'category_id'])
            ->groupBy('category_id')
            ->each(function ($dishes): void {
                $dishes->values()->each(function ($dish, int $index): void {
                    DB::table('dishes')->where('id', $dish->id)->update(['orden' => $index + 1]);
                });
            });
    }
};
