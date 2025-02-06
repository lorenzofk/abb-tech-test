<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationCreated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Application extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => ApplicationStatus::class,
    ];

    protected $dispatchesEvents = [
        'created' => ApplicationCreated::class,
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope a query to only include applications with a plan of a given type.
     */
    public function scopePlanType(Builder $query, string $planType): Builder
    {
        return $query->withWhereHas('plan', fn ($query) => $query->where('type', $planType));
    }

    /**
     * Get the full address of the application.
     */
    protected function fullAddress(): Attribute
    {
        $fields = [
            $this->address_1,
            $this->address_2,
            $this->city,
            $this->state,
            $this->postcode,
        ];

        return Attribute::make(
            get: fn () => implode(', ', array_filter($fields)),
        );
    }
}
