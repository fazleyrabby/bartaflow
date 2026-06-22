<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('color', 20)->default('#6b7280');
            $table->timestamps();

            $table->unique(['workspace_id', 'name']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_tags');
    }
};
