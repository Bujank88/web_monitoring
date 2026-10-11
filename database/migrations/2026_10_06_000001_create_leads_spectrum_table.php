<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads_spectrum', function (Blueprint $table) {
            $table->id();
            foreach (['provider', 'last_name', 'company_account', 'mobile_phone', 'email', 'company_size', 'product', 'lead_source', 'pillar', 'industry_sector'] as $column) {
                $table->string($column)->nullable();
            }
            $table->date('create_date')->nullable()->index();
            $table->date('share_date')->nullable();
            foreach (['description', 'fu', 'response'] as $column) {
                $table->text($column)->nullable();
            }
            $table->char('import_hash', 64)->unique();
            $table->string('import_filename');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_spectrum');
    }
};
