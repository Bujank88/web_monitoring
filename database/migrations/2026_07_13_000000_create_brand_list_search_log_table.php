<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_list_search_log', function (Blueprint $table) {
            $table->id();
            $table->string('brand_filter', 255);
            $table->string('user_name', 255)->nullable();
            $table->timestamp('searched_at')->useCurrent();

            $table->index('brand_filter');
            $table->index('searched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_list_search_log');
    }
};
