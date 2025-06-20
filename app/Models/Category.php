<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'color',
        'icon',
        'description',
        'budget_limit',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'budget_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the category.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for this category.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get monthly spending for this category.
     */
    public function getMonthlySpending(?int $year = null, ?int $month = null): float
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        return $this->transactions()
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');
    }

    /**
     * Get yearly spending for this category.
     */
    public function getYearlySpending(?int $year = null): float
    {
        $year = $year ?? now()->year;

        return $this->transactions()
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->sum('amount');
    }

    /**
     * Check if budget is exceeded for the current month.
     */
    public function isBudgetExceeded(): bool
    {
        if (!$this->budget_limit || $this->budget_limit <= 0) {
            return false;
        }

        return $this->getMonthlySpending() > $this->budget_limit;
    }

    /**
     * Get budget utilization percentage for current month.
     */
    public function getBudgetUtilization(): float
    {
        if (!$this->budget_limit || $this->budget_limit <= 0) {
            return 0;
        }

        $spent = $this->getMonthlySpending();
        return ($spent / $this->budget_limit) * 100;
    }

    /**
     * Get remaining budget for current month.
     */
    public function getRemainingBudget(): float
    {
        if (!$this->budget_limit || $this->budget_limit <= 0) {
            return 0;
        }

        $spent = $this->getMonthlySpending();
        return max(0, $this->budget_limit - $spent);
    }

    /**
     * Get budget status color based on utilization.
     */
    public function getBudgetStatusColor(): string
    {
        $utilization = $this->getBudgetUtilization();

        return match (true) {
            $utilization >= 100 => 'danger',
            $utilization >= 80 => 'warning',
            $utilization >= 60 => 'info',
            default => 'success',
        };
    }

    /**
     * Get formatted budget limit.
     */
    public function getFormattedBudgetLimitAttribute(): string
    {
        return $this->budget_limit ? '$' . number_format($this->budget_limit, 2) : 'No limit';
    }

    /**
     * Get spending trend (comparing current month to previous month).
     */
    public function getSpendingTrend(): array
    {
        $currentMonth = $this->getMonthlySpending();
        $previousMonth = $this->getMonthlySpending(
            now()->subMonth()->year,
            now()->subMonth()->month
        );

        $change = $currentMonth - $previousMonth;
        $percentageChange = $previousMonth > 0 ? ($change / $previousMonth) * 100 : 0;

        return [
            'current' => $currentMonth,
            'previous' => $previousMonth,
            'change' => $change,
            'percentage_change' => $percentageChange,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get categories with budget limits.
     */
    public function scopeWithBudget($query)
    {
        return $query->whereNotNull('budget_limit')
            ->where('budget_limit', '>', 0);
    }

    /**
     * Scope to get categories that exceeded their budget this month.
     */
    public function scopeBudgetExceeded($query)
    {
        return $query->withBudget()
            ->whereHas('transactions', function ($query) {
                $query->where('type', 'expense')
                    ->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year);
            });
    }
}
