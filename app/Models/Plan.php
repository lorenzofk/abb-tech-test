<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get the monthly cost of the plan in human readable format.
     */
    protected function monthlyCostFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->monthly_cost / 100, 2),
        );
    }
}
