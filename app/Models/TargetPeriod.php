<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetPeriod extends Model
{
    protected $fillable = [
        'year',
        'month',
        'name',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
    ];

    /**
     * Get formatted month name.
     */
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 10, $this->year));
    }

    /**
     * Get short month name (e.g. Jan).
     */
    public function getShortMonthAttribute(): string
    {
        return date('M', mktime(0, 0, 0, $this->month, 10, $this->year));
    }

    /**
     * Get label (e.g. "January 2026").
     */
    public function getLabelAttribute(): string
    {
        return $this->name ?: "{$this->month_name} {$this->year}";
    }
}
