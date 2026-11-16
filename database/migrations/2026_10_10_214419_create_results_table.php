<?php

use App\Enums\AspirantOffice;
use App\Enums\ResultStatus;
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
        Schema::create('results', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Geographic hierarchy for fast scoping and ward/LGA level rollups
            $table->foreignUuid('pu_id')->constrained('pus')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('ward_id')->nullable()->constrained('wards')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('lga_id')->nullable()->constrained('lgas')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('zone_id')->nullable()->constrained('zones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('state_id')->nullable()->constrained('states')->nullOnDelete()->cascadeOnUpdate();

            $table->enum('office', array_column(AspirantOffice::cases(), 'value'));
            $table->date('election_date')->nullable();

            $table->unsignedInteger('registered_voters')->default(0);
            $table->unsignedInteger('accredited_voters')->default(0);
            $table->unsignedInteger('rejected_votes')->default(0);
            $table->json('party_votes'); // Key-value or array storing votes per party
            $table->unsignedInteger('total_valid_votes')->default(0);

            $table->enum('status', array_column(ResultStatus::cases(), 'value'))
                ->default(ResultStatus::PENDING->value);
            $table->string('result_sheet_url')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // One result per PU per office
            $table->unique(['pu_id', 'office']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
