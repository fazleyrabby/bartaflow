<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120)->nullable();
            $table->string('recipient_type', 20)->default('contacts');
            $table->json('recipient_payload');
            $table->json('variables_override')->nullable();
            $table->timestamp('run_at');
            $table->string('timezone', 64)->default('Asia/Dhaka');
            $table->string('recurrence', 20)->default('none');
            $table->json('recurrence_meta')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('last_error', 255)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'run_at']);
            $table->index(['workspace_id', 'status']);
            $table->index('next_run_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
    }
};
