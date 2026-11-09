<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logbook_daily', function (Blueprint $table) {
            $table->id();
            $table->integer('leads_master_id');
            $table->enum('komitmen', ['100%', '50%', '<50%', 'New Leads'])->default('New Leads');   
            $table->integer('plan_min_topup')->default(100000);    
            $table->enum('status', ['Initial', 'Prospect', 'Register', 'Topup', 'Repeat', 'No Response', 'Reject'])->default('Prospect'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbook_daily');
    }
};
