<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'quotation_id',
        'customer_id',
        'invoice_number',
        'date',
        'due_date',
        'total_amount',
        'status',
        'is_proforma',
        'brand_name',
        'attention_to',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'designation',
        'currency',
        'heading',
        'terms',
        'special_terms',
        'advance_payment',
        'advance_percentage',
        'advance_received_amount',
        'invoice_type',
        'senior_manager',
        'sscl_applicable',
        'vat_applicable',
        'proforma_percentage',
        'proforma_tax',
        'proforma_with_tax',
        'date_of_delivery',
        'place_of_supply',
        'additional_information',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'quotation_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date', 'asc')->orderBy('id', 'asc');
    }

    public function getTotalPaidAmountAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function getDueAmountAttribute(): float
    {
        $total = (float) $this->total_amount;
        if ($this->payments->isNotEmpty()) {
            $paid = (float) $this->payments->sum('amount');
            return max(0.0, round($total - $paid, 2));
        }

        if ($this->status === 'paid') {
            return 0.0;
        }

        return $total;
    }

    public function isFullyPaidThroughPartial(): bool
    {
        return $this->payments->isNotEmpty() && $this->total_paid_amount >= (float) $this->total_amount;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payments->isNotEmpty() && $this->total_paid_amount > 0 && $this->total_paid_amount < (float) $this->total_amount;
    }

    public function getCurrencyCodeAttribute(): string
    {
        return $this->currency ?: ($this->estimate->deal->currency ?? ($this->estimate->currency ?? 'LKR'));
    }
}
