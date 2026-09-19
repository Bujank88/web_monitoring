<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prizes_v4', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('img');
            $table->string('name');
            $table->integer('point');
            $table->integer('stock');
            $table->timestamps();
        });

        $source = Schema::hasTable('prizes_v3') ? 'prizes_v3' : 'prizes_v2';
        if (Schema::hasTable($source)) {
            $columns = ['id', 'img', 'name', 'point', 'stock', 'created_at', 'updated_at'];
            DB::table('prizes_v4')->insertUsing($columns, DB::table($source)->select($columns));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prizes_v4');
    }
};
