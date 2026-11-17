<?php

use App\Enums\AspirantOffice;
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
        // Officials captured by the party agent (one row per PU).
        Schema::create('pu_officials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pu_id')->unique()->constrained('pus')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('po_name');
            $table->string('apo1_name');
            $table->string('apo2_name');
            $table->string('security_name');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        // Result evidence (counting video + result-sheet photos): one row per PU per office,
        // matching the unique key of the results table.
        Schema::create('pu_result_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pu_id')->constrained('pus')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('office', array_column(AspirantOffice::cases(), 'value'));
            $table->string('video_path')->nullable();
            $table->string('video_name')->nullable();
            $table->json('sheets')->nullable(); // [{path, name}]
            $table->text('remarks')->nullable();
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['pu_id', 'office']);
        });

        Schema::create('pu_task_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pu_id')->constrained('pus')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('office')->nullable();
            $table->string('action', 60);
            $table->json('details')->nullable();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['pu_id', 'created_at']);
            $table->index(['office', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pu_task_audits');
        Schema::dropIfExists('pu_result_submissions');
        Schema::dropIfExists('pu_officials');
    }
};
