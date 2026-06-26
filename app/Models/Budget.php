<?php

namespace App\Models;

use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return Attribute<float, never>
     */
    protected function spent(): Attribute
    {
        return Attribute::get(fn (): float => (float) Transaction::query()
            ->where('user_id', $this->user_id)
            ->when(
                $this->category_id !== null,
                fn ($query) => $query->where('category_id', $this->category_id),
            )
            ->where('type', 'expense')
            ->whereBetween('transacted_at', [
                $this->start_date,
                $this->end_date ?? now()->endOfMonth(),
            ])
            ->sum('amount'));
    }

    /**
     * @return Attribute<int<min, 100>, never>
     */
    protected function progressPercent(): Attribute
    {
        return Attribute::get(fn (): int => $this->amount > 0
            ? min(100, (int) (($this->spent / $this->amount) * 100))
            : 0);
    }
}
