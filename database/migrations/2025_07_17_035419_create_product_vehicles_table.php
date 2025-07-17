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
        Schema::create('product_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('bigcommerce_product_id'); // BigCommerce product ID
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(['bigcommerce_product_id', 'vehicle_id']);
            
            // Add indexes for performance
            $table->index(['bigcommerce_product_id']);
            $table->index(['vehicle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_vehicles');
    }
};
