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
        if (Schema::hasTable('zones') && !Schema::hasColumn('zones', 'state_id')) {
            Schema::table('zones', function (Blueprint $table) {
                $table->foreignUuid('state_id')->nullable()->after('id')
                    ->constrained('states')->nullOnDelete()->cascadeOnUpdate();
            });
        }

        if (Schema::hasTable('lgas') && !Schema::hasColumn('lgas', 'zone_id')) {
            Schema::table('lgas', function (Blueprint $table) {
                $table->foreignUuid('zone_id')->nullable()->after('id')
                    ->constrained('zones')->nullOnDelete()->cascadeOnUpdate();
            });
        }

        if (Schema::hasTable('wards') && !Schema::hasColumn('wards', 'lga_id')) {
            Schema::table('wards', function (Blueprint $table) {
                $table->foreignUuid('lga_id')->nullable()->after('id')
                    ->constrained('lgas')->nullOnDelete()->cascadeOnUpdate();
            });
        }

        if (Schema::hasTable('pus') && !Schema::hasColumn('pus', 'ward_id')) {
            Schema::table('pus', function (Blueprint $table) {
                $table->foreignUuid('ward_id')->nullable()->after('id')
                    ->constrained('wards')->nullOnDelete()->cascadeOnUpdate();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([['zones', 'state_id'], ['lgas', 'zone_id'], ['wards', 'lga_id']] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId($column));
            }
        }
    }
};
