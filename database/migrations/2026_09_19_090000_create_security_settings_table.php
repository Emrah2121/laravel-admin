<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('rate_limit_status')->default(false);
            $table->boolean('recaptcha_status')->default(false);
            $table->text('recaptcha_site_key')->nullable();
            $table->text('recaptcha_secret_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_settings');
    }
};
