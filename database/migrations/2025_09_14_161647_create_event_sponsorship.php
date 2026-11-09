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
        Schema::create('event_sponsorship', function (Blueprint $table) {
            $table->id();
            $table->string('area')->nullable(); // AREA
            $table->string('regional')->nullable(); // Regional
            $table->string('nama_event')->nullable(); // Nama Event
            $table->string('lokasi_event')->nullable(); // Lokasi Event
            $table->date('tanggal_event')->nullable(); // Tanggal Event
            $table->string('pic_event')->nullable(); // PIC Event (EO)
            $table->string('telp_pic_event')->nullable(); // No. Telp PIC Event (EO)
            $table->string('pic_tsel')->nullable(); // Nama PIC Tsel
            $table->string('telp_pic_tsel')->nullable(); // No. Telp PIC Tsel
            $table->string('upload_proposal')->nullable(); // link proposal (pdf)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sponsorship');
    }
};
