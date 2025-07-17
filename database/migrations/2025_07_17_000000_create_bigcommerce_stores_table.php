<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bigcommerce_stores', function (Blueprint $table) {
            $table->id();
            $table->string('store_hash')->unique();
            $table->string('store_name')->nullable();
            $table->text('access_token');
            $table->string('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('owner_id')->nullable();
            $table->string('owner_email')->nullable();
            $table->json('scope')->nullable(); // Store the granted scopes
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bigcommerce_stores');
    }
};
