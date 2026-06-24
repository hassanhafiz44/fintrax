<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->date('loaned_at')->nullable()->after('contact_name');
        });

        DB::table('loans')->whereNull('loaned_at')->orderBy('id')->each(function (object $loan): void {
            DB::table('loans')->where('id', $loan->id)->update([
                'loaned_at' => Carbon::parse($loan->created_at)->toDateString(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('loaned_at');
        });
    }
};
