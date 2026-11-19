<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'seller_id',
        'buyer_id',
        'invoice_number',
        'issue_date',
        'fulfillment_date',
        'due_date',
        'currency',
        'gross_amount',
        'exchange_rate',
        'amount_huf',
        'direction',
        'file_path',
        'original_filename',
        'file_hash',
        'processing_status',
        'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'fulfillment_date' => 'date',
            'due_date' => 'date',
            'gross_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'amount_huf' => 'decimal:2',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'buyer_id');
    }
}