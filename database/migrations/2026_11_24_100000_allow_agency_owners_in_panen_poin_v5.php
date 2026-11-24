<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['user_panen_poin_v5', 'akun_panen_poin_v5', 'summary_panen_poin_v5'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            foreach (Schema::getForeignKeys($name) as $foreign) {
                if ($foreign['columns'] === ['user_id']) {
                    Schema::table($name, fn (Blueprint $table) => $table->dropForeign(
                        DB::getDriverName() === 'sqlite' ? $foreign['columns'] : $foreign['name']
                    ));
                }
            }
            Schema::table($name, fn (Blueprint $table) => $table->bigInteger('user_id')->default(-1)->change());
        }
        DB::table('summary_panen_poin_v5')->whereIn('user_id', [0, -1])->update(['nama_canvasser' => 'Agency']);
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            if (DB::table($name)->whereNotIn('user_id', DB::table('users')->select('id'))->exists()) {
                throw new RuntimeException('Assign Agency records to valid users before rolling back this migration.');
            }
        }
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->default(null)->change();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};
