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

            // Election relationship
            $table->foreignUuid('election_id')
                ->constrained('elections')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Geographic hierarchy
            $table->foreignUuid('pu_id')
                ->constrained('pus')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('ward_id')
                ->nullable()
                ->constrained('wards')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('lga_id')
                ->nullable()
                ->constrained('lgas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('zone_id')
                ->nullable()
                ->constrained('zones')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Election result details
            $table->enum(
                'office',
                array_column(AspirantOffice::cases(), 'value')
            );

            $table->date('date')->nullable();

            $table->unsignedInteger('registered_voters')->default(0);
            $table->unsignedInteger('accredited_voters')->default(0);
            $table->unsignedInteger('rejected_votes')->default(0);

            $table->json('party_votes');

            $table->unsignedInteger('total_valid_votes')->default(0);

            // Result verification
            $table->enum(
                'status',
                array_column(ResultStatus::cases(), 'value')
            )->default(ResultStatus::PENDING->value);

            $table->string('result_sheet_url')->nullable();
            $table->text('remarks')->nullable();

            // Users responsible for submission and verification
            $table->foreignUuid('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // One result per election, polling unit and office
            $table->unique([
                'election_id',
                'pu_id',
                'office',
            ], 'results_election_pu_office_unique');
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
