<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $status
 * @property Carbon|null $due_date
 * @property string $remaining
 */
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'remaining' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LoanPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->due_date !== null && $this->due_date->isPast();
    }
}
