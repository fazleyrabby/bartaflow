<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            // scheduled_messages arrives in task 008; keep the column FK-less for now.
            $table->unsignedBigInteger('scheduled_message_id')->nullable();
            $table->string('recipient_phone', 20);
            $table->string('recipient_name', 120)->nullable();
            $table->text('body');
            $table->json('variables_used')->nullable();
            $table->string('direction', 10)->default('outbound');
            $table->string('status', 20)->default('queued');
            $table->string('provider_message_id', 120)->nullable();
            $table->string('error_code', 40)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'created_at']);
            $table->index(['workspace_id', 'contact_id']);
            $table->index('whatsapp_account_id');
            $table->index('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
