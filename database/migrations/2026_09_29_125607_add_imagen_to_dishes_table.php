<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the photograph the admin uploaded for a dish.
     *
     * Before this column the photograph was looked up on disk by slugging the
     * dish name, which broke as soon as the dish was renamed and made two
     * dishes with the same name share one file. The path is relative to the
     * `public` disk, exactly like `model3ds.archivo`, and nullable so a dish
     * without a photograph keeps rendering its fallback scene.
     */
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
