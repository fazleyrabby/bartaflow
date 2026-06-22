<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('provider', 30)->default('cloud_api');
            $table->string('phone_number', 20);
            $table->string('phone_number_id', 64)->nullable();
            $table->string('business_account_id', 64)->nullable();
            $table->text('access_token');
            $table->string('webhook_verify_token', 64)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('status_reason', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index('workspace_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
