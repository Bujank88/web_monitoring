<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goto', function (Blueprint $table) {
            $table->id();
            $table->string('reg_id')->nullable();
            $table->string('email_myads');
            $table->string('email_parent')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->index('reg_id');
            $table->index('email_myads');
            $table->index('remark');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goto');
    }
};
