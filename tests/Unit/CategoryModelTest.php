<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CategoryModelTest extends TestCase
{
  use RefreshDatabase;

  protected User $user;
  protected Category $category;
  protected Wallet $wallet;

  protected function setUp(): void
  {
    parent::setUp();
    $this->user = User::factory()->create();
    $this->wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $this->category = Category::factory()->create([
      'user_id' => $this->user->id,
      'budget_limit' => 500.00,
    ]);
  }

  public function test_category_belongs_to_user(): void
  {
    $this->assertInstanceOf(User::class, $this->category->user);
    $this->assertEquals($this->user->id, $this->category->user->id);
  }

  public function test_category_has_transactions_relationship(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
    ]);

    $this->assertTrue($this->category->transactions->contains($transaction));
    $this->assertInstanceOf(Transaction::class, $this->category->transactions->first());
  }

  public function test_category_has_parent_relationship(): void
  {
    $parentCategory = Category::factory()->create(['user_id' => $this->user->id]);
    $childCategory = Category::factory()->create([
      'user_id' => $this->user->id,
      'parent_id' => $parentCategory->id,
    ]);

    $this->assertInstanceOf(Category::class, $childCategory->parent);
    $this->assertEquals($parentCategory->id, $childCategory->parent->id);
  }

  public function test_category_has_children_relationship(): void
  {
    $childCategory = Category::factory()->create([
      'user_id' => $this->user->id,
      'parent_id' => $this->category->id,
    ]);

    $this->assertTrue($this->category->children->contains($childCategory));
    $this->assertInstanceOf(Category::class, $this->category->children->first());
  }

  public function test_get_monthly_spending_calculates_correctly(): void
  {
    // Create transactions for current month
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 100.00,
      'date' => now(),
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 50.00,
      'date' => now(),
    ]);

    // Create transaction for different month (should not be included)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 200.00,
      'date' => now()->subMonth(),
    ]);

    // Create income transaction (should not be included)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'income',
      'amount' => 300.00,
      'date' => now(),
    ]);

    $monthlySpending = $this->category->getMonthlySpending();

    $this->assertEquals(150.00, $monthlySpending);
  }

  public function test_get_yearly_spending_calculates_correctly(): void
  {
    $currentYear = now()->year;

    // Create transactions for current year
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 100.00,
      'date' => Carbon::create($currentYear, 1, 15),
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 200.00,
      'date' => Carbon::create($currentYear, 6, 15),
    ]);

    // Create transaction for different year (should not be included)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 300.00,
      'date' => Carbon::create($currentYear - 1, 6, 15),
    ]);

    $yearlySpending = $this->category->getYearlySpending();

    $this->assertEquals(300.00, $yearlySpending);
  }

  public function test_is_budget_exceeded_returns_correct_status(): void
  {
    // Test with spending under budget
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 400.00,
      'date' => now(),
    ]);

    $this->assertFalse($this->category->isBudgetExceeded());

    // Test with spending over budget
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 200.00,
      'date' => now(),
    ]);

    $this->assertTrue($this->category->isBudgetExceeded());
  }

  public function test_is_budget_exceeded_returns_false_when_no_budget_limit(): void
  {
    $categoryNoBudget = Category::factory()->create([
      'user_id' => $this->user->id,
      'budget_limit' => null,
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $categoryNoBudget->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 1000.00,
      'date' => now(),
    ]);

    $this->assertFalse($categoryNoBudget->isBudgetExceeded());
  }

  public function test_get_budget_utilization_calculates_correctly(): void
  {
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 250.00,
      'date' => now(),
    ]);

    $utilization = $this->category->getBudgetUtilization();

    $this->assertEquals(50.0, $utilization);
  }

  public function test_get_remaining_budget_calculates_correctly(): void
  {
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 200.00,
      'date' => now(),
    ]);

    $remaining = $this->category->getRemainingBudget();

    $this->assertEquals(300.00, $remaining);
  }

  public function test_get_remaining_budget_returns_zero_when_exceeded(): void
  {
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 600.00,
      'date' => now(),
    ]);

    $remaining = $this->category->getRemainingBudget();

    $this->assertEquals(0.00, $remaining);
  }

  public function test_get_budget_status_color_returns_correct_colors(): void
  {
    // Test success color (under 60%)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 200.00,
      'date' => now(),
    ]);

    $this->assertEquals('success', $this->category->getBudgetStatusColor());

    // Test info color (60-79%)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 150.00,
      'date' => now(),
    ]);

    $this->assertEquals('info', $this->category->getBudgetStatusColor());

    // Test warning color (80-99%)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 50.00,
      'date' => now(),
    ]);

    $this->assertEquals('warning', $this->category->getBudgetStatusColor());

    // Test danger color (100%+)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 100.00,
      'date' => now(),
    ]);

    $this->assertEquals('danger', $this->category->getBudgetStatusColor());
  }

  public function test_get_spending_trend_calculates_correctly(): void
  {
    // Current month spending
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 300.00,
      'date' => now(),
    ]);

    // Previous month spending
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 200.00,
      'date' => now()->subMonth(),
    ]);

    $trend = $this->category->getSpendingTrend();

    $this->assertEquals(300.00, $trend['current']);
    $this->assertEquals(200.00, $trend['previous']);
    $this->assertEquals(100.00, $trend['change']);
    $this->assertEquals(50.0, $trend['percentage_change']);
    $this->assertEquals('up', $trend['trend']);
  }

  public function test_active_scope_filters_correctly(): void
  {
    $activeCategory = Category::factory()->create([
      'user_id' => $this->user->id,
      'is_active' => true,
    ]);

    $inactiveCategory = Category::factory()->create([
      'user_id' => $this->user->id,
      'is_active' => false,
    ]);

    $activeCategories = Category::active()->get();

    $this->assertTrue($activeCategories->contains($activeCategory));
    $this->assertFalse($activeCategories->contains($inactiveCategory));
  }

  public function test_with_budget_scope_filters_correctly(): void
  {
    $categoryWithBudget = Category::factory()->create([
      'user_id' => $this->user->id,
      'budget_limit' => 100.00,
    ]);

    $categoryWithoutBudget = Category::factory()->create([
      'user_id' => $this->user->id,
      'budget_limit' => null,
    ]);

    $categoriesWithBudget = Category::withBudget()->get();

    $this->assertTrue($categoriesWithBudget->contains($categoryWithBudget));
    $this->assertFalse($categoriesWithBudget->contains($categoryWithoutBudget));
  }
}
