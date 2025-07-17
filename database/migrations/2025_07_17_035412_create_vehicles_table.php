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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->integer('year_start'); // Start of year range
            $table->integer('year_end');   // End of year range  
            $table->string('make');
            $table->string('model');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add indexes for performance
            $table->index(['year_start', 'year_end']);
            $table->index(['make']);
            $table->index(['model']);
            $table->index(['make', 'model']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
