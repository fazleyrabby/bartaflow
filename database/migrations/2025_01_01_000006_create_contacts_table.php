<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('email', 255)->nullable();
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_opted_out')->default(false);
            $table->timestamp('opted_out_at')->nullable();
            $table->string('source', 20)->default('manual');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['workspace_id', 'phone']);
            $table->index('workspace_id');
            $table->index(['workspace_id', 'is_opted_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
