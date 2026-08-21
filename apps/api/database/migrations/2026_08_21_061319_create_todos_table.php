<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('status', 20);
            $table->date('due_on')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
