<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->unsignedTinyInteger('edad');
            $table->unsignedTinyInteger('nivel_energia');
            $table->string('estado')->default('DISPONIBLE')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renos');
    }
};
