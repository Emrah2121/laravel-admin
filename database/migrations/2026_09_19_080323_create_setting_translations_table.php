<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('setting_id')->index();
            $table->text('content')->nullable();
            $table->string('locale');

            $table->foreign('setting_id')->references('id')->on('settings')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_translations');
    }
};
