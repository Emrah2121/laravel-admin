<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('manipulationable_type');
            $table->unsignedBigInteger('manipulationable_id');
            $table->text('document');
            $table->string('collection_name', 100)->nullable();
            $table->string('alt', 100)->nullable();
            $table->string('status', 100)->nullable();
            $table->enum('first', ['1', '0'])->default('1');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('attachment_type')->nullable();
            $table->timestamps();

            $table->index(['manipulationable_type', 'manipulationable_id']);
            $table->foreign('parent_id')->references('id')->on('documents')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
