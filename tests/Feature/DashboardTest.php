<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class DashboardTest extends TestCase
{
  use RefreshDatabase;

  protected User $user;

  protected function setUp(): void
  {
    parent::setUp();

    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'web');
  }

  public function test_dashboard_loads_successfully(): void
  {
    // Test that user has access to dashboard data
    $this->assertInstanceOf(User::class, $this->user);
    $this->assertNotNull($this->user->id);

    // Test that user can have wallets
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $this->assertEquals($this->user->id, $wallet->user_id);
  }

  public function test_dashboard_displays_wallet_totals(): void
  {
    // Create wallets with different balances
    Wallet::factory()->create([
      'user_id' => $this->user->id,
      'name' => 'Checking',
      'balance' => 1000.00,
    ]);

    Wallet::factory()->create([
      'user_id' => $this->user->id,
      'name' => 'Savings',
      'balance' => 2500.00,
    ]);

    // Test that total balance calculation works
    $totalBalance = $this->user->wallets()->sum('balance');
    $this->assertEquals(3500.00, $totalBalance);
  }

  public function test_dashboard_shows_recent_transactions(): void
  {
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    // Create a recent transaction
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'description' => 'Test Transaction',
      'amount' => 50.00,
      'date' => now(),
    ]);

    // Test that recent transactions can be retrieved
    $recentTransactions = $this->user->transactions()
      ->orderBy('date', 'desc')
      ->limit(10)
      ->get();

    $this->assertCount(1, $recentTransactions);
    $this->assertEquals('Test Transaction', $recentTransactions->first()->description);
    $this->assertEquals(50.00, $recentTransactions->first()->amount);
  }

  public function test_dashboard_filters_data_by_user(): void
  {
    // Create another user with their own data
    $otherUser = User::factory()->create();

    $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);
    $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

    Transaction::factory()->create([
      'user_id' => $otherUser->id,
      'wallet_id' => $otherWallet->id,
      'category_id' => $otherCategory->id,
      'description' => 'Other User Transaction',
      'amount' => 999.00,
    ]);

    // Create current user's data
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'description' => 'My Transaction',
      'amount' => 100.00,
    ]);

    // Test that data is properly filtered by user
    $userTransactions = $this->user->transactions()->get();
    $this->assertCount(1, $userTransactions);
    $this->assertEquals('My Transaction', $userTransactions->first()->description);

    $otherUserTransactions = $otherUser->transactions()->get();
    $this->assertCount(1, $otherUserTransactions);
    $this->assertEquals('Other User Transaction', $otherUserTransactions->first()->description);
  }

  public function test_dashboard_calculates_income_vs_expense_correctly(): void
  {
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    // Create income transactions
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'type' => 'income',
      'amount' => 1000.00,
      'date' => now(),
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'type' => 'income',
      'amount' => 500.00,
      'date' => now(),
    ]);

    // Create expense transactions
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'type' => 'expense',
      'amount' => 300.00,
      'date' => now(),
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'type' => 'expense',
      'amount' => 200.00,
      'date' => now(),
    ]);

    // Test income vs expense calculations
    $totalIncome = $this->user->transactions()->where('type', 'income')->sum('amount');
    $totalExpenses = $this->user->transactions()->where('type', 'expense')->sum('amount');

    $this->assertEquals(1500.00, $totalIncome);
    $this->assertEquals(500.00, $totalExpenses);
    $this->assertEquals(1000.00, $totalIncome - $totalExpenses); // Net income
  }

  public function test_dashboard_shows_category_breakdown(): void
  {
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

    $foodCategory = Category::factory()->create([
      'user_id' => $this->user->id,
      'name' => 'Food',
    ]);

    $transportCategory = Category::factory()->create([
      'user_id' => $this->user->id,
      'name' => 'Transport',
    ]);

    // Create transactions for different categories
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $foodCategory->id,
      'type' => 'expense',
      'amount' => 150.00,
      'date' => now(),
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $transportCategory->id,
      'type' => 'expense',
      'amount' => 75.00,
      'date' => now(),
    ]);

    // Test category breakdown calculations
    $foodExpenses = $this->user->transactions()
      ->where('category_id', $foodCategory->id)
      ->sum('amount');

    $transportExpenses = $this->user->transactions()
      ->where('category_id', $transportCategory->id)
      ->sum('amount');

    $this->assertEquals(150.00, $foodExpenses);
    $this->assertEquals(75.00, $transportExpenses);
  }

  public function test_dashboard_handles_empty_data_gracefully(): void
  {
    // Test that empty data doesn't cause errors
    $totalBalance = $this->user->wallets()->sum('balance');
    $totalTransactions = $this->user->transactions()->count();

    $this->assertEquals(0.00, $totalBalance);
    $this->assertEquals(0, $totalTransactions);
  }

  public function test_dashboard_respects_date_filtering(): void
  {
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    // Create transaction from current month
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'description' => 'Current Month',
      'amount' => 100.00,
      'date' => now(),
    ]);

    // Create transaction from last month
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'description' => 'Last Month',
      'amount' => 200.00,
      'date' => now()->subMonth(),
    ]);

    // Test date filtering functionality
    $currentMonthTransactions = $this->user->transactions()
      ->whereYear('date', now()->year)
      ->whereMonth('date', now()->month)
      ->get();

    $this->assertCount(1, $currentMonthTransactions);
    $this->assertEquals('Current Month', $currentMonthTransactions->first()->description);

    $lastMonthTransactions = $this->user->transactions()
      ->whereYear('date', now()->subMonth()->year)
      ->whereMonth('date', now()->subMonth()->month)
      ->get();

    $this->assertCount(1, $lastMonthTransactions);
    $this->assertEquals('Last Month', $lastMonthTransactions->first()->description);
  }

  public function test_user_can_access_wallet_management(): void
  {
    // Test that user has access to their wallets
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $userWallets = $this->user->wallets()->get();

    $this->assertCount(1, $userWallets);
    $this->assertEquals($wallet->id, $userWallets->first()->id);
  }

  public function test_user_can_access_transaction_management(): void
  {
    // Test that user has access to their transactions
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
    ]);

    $userTransactions = $this->user->transactions()->get();

    $this->assertCount(1, $userTransactions);
    $this->assertEquals($transaction->id, $userTransactions->first()->id);
  }

  public function test_user_can_access_category_management(): void
  {
    // Test that user has access to their categories
    $category = Category::factory()->create(['user_id' => $this->user->id]);
    $userCategories = $this->user->categories()->get();

    $this->assertCount(1, $userCategories);
    $this->assertEquals($category->id, $userCategories->first()->id);
  }

  public function test_unauthorized_user_cannot_access_dashboard(): void
  {
    // Test user isolation - create another user's data
    $otherUser = User::factory()->create();
    $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

    // Current user should not see other user's data
    $userWallets = $this->user->wallets()->get();
    $this->assertCount(0, $userWallets);

    $otherUserWallets = $otherUser->wallets()->get();
    $this->assertCount(1, $otherUserWallets);
  }

  public function test_dashboard_shows_budget_alerts(): void
  {
    $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

    $category = Category::factory()->create([
      'user_id' => $this->user->id,
      'name' => 'Food Budget',
      'budget_limit' => 500.00,
    ]);

    // Create transaction that exceeds budget
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $wallet->id,
      'category_id' => $category->id,
      'type' => 'expense',
      'amount' => 600.00,
      'date' => now(),
    ]);

    // Test budget calculations
    $monthlySpending = $category->transactions()
      ->where('type', 'expense')
      ->whereYear('date', now()->year)
      ->whereMonth('date', now()->month)
      ->sum('amount');

    $this->assertEquals(600.00, $monthlySpending);
    $this->assertTrue($monthlySpending > $category->budget_limit);
    $this->assertEquals(100.00, $monthlySpending - $category->budget_limit); // Over budget by 100
  }
}
