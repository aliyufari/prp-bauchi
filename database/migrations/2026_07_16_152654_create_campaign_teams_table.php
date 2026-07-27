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
        Schema::create('campaign_teams', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('aspirant_id')
                ->constrained('aspirants')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('team_lead_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        Schema::create('campaign_team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('campaign_team_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('user_id')
                ->unique() // a user belongs to exactly one team at a time
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('role_in_team'); // e.g. Field Officer, Media Officer
            $table->boolean('is_active')->default(true);
            $table->date('joined_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_team_members');
        Schema::dropIfExists('campaign_teams');
    }
};
