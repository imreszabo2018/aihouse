<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Partner relationships
            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('partners')
                ->restrictOnDelete();

            $table->foreignId('buyer_id')
                ->nullable()
                ->constrained('partners')
                ->restrictOnDelete();

            // Invoice information
            $table->string('invoice_number')->nullable();

            $table->date('issue_date')->nullable();
            $table->date('fulfillment_date')->nullable();
            $table->date('due_date')->nullable();

            // Financial information
            $table->char('currency', 3)->nullable();
            $table->decimal('gross_amount', 15, 2)->nullable();
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_huf', 15, 2)->nullable();

            // Invoice direction
            $table->string('direction', 10)->nullable();

            // Uploaded document
            $table->string('file_path');
            $table->string('original_filename');
            $table->char('file_hash', 64)->unique();

            // Processing information
            $table->string('processing_status')->default('pending');
            $table->text('processing_error')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
