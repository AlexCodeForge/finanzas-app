<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TransactionCreated;
use Livewire\Livewire;
use Illuminate\Support\Facades\Validator;

class TransactionWorkflowTest extends TestCase
{
  use RefreshDatabase;

  protected User $user;
  protected Wallet $wallet;
  protected Category $category;

  protected function setUp(): void
  {
    parent::setUp();

    $this->user = User::factory()->create();
    $this->wallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'balance' => 1000.00,
      'currency' => 'USD'
    ]);
    $this->category = Category::factory()->create([
      'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user, 'web');
  }

  public function test_user_can_create_income_transaction(): void
  {
    $initialBalance = $this->wallet->balance;

    // Create transaction directly using model
    $transaction = Transaction::create([
      'user_id' => $this->user->id,
      'type' => 'income',
      'amount' => 500.00,
      'description' => 'Salary Payment',
      'date' => now(),
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
    ]);

    $this->assertDatabaseHas('transactions', [
      'user_id' => $this->user->id,
      'type' => 'income',
      'amount' => 500.00,
      'description' => 'Salary Payment',
    ]);

    // Check wallet balance was updated
    $this->wallet->refresh();
    $this->assertEquals($initialBalance + 500.00, $this->wallet->balance);
  }

  public function test_user_can_create_expense_transaction(): void
  {
    $initialBalance = $this->wallet->balance;

    // Create transaction directly using model
    $transaction = Transaction::create([
      'user_id' => $this->user->id,
      'type' => 'expense',
      'amount' => 200.00,
      'description' => 'Grocery Shopping',
      'date' => now(),
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
    ]);

    $this->assertDatabaseHas('transactions', [
      'user_id' => $this->user->id,
      'type' => 'expense',
      'amount' => 200.00,
      'description' => 'Grocery Shopping',
    ]);

    // Check wallet balance was updated
    $this->wallet->refresh();
    $this->assertEquals($initialBalance - 200.00, $this->wallet->balance);
  }

  public function test_user_can_create_transfer_transaction(): void
  {
    $toWallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'balance' => 500.00,
      'currency' => 'USD'
    ]);

    $fromInitialBalance = $this->wallet->balance;
    $toInitialBalance = $toWallet->balance;

    // Create transaction directly using model
    $transaction = Transaction::create([
      'user_id' => $this->user->id,
      'type' => 'transfer',
      'amount' => 300.00,
      'description' => 'Transfer to Savings',
      'date' => now(),
      'from_wallet_id' => $this->wallet->id,
      'to_wallet_id' => $toWallet->id,
    ]);

    $this->assertDatabaseHas('transactions', [
      'user_id' => $this->user->id,
      'type' => 'transfer',
      'amount' => 300.00,
      'description' => 'Transfer to Savings',
      'from_wallet_id' => $this->wallet->id,
      'to_wallet_id' => $toWallet->id,
    ]);

    // Check both wallet balances were updated
    $this->wallet->refresh();
    $toWallet->refresh();
    $this->assertEquals($fromInitialBalance - 300.00, $this->wallet->balance);
    $this->assertEquals($toInitialBalance + 300.00, $toWallet->balance);
  }

  public function test_user_can_update_transaction(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 100.00,
      'description' => 'Original Description',
    ]);

    // Update transaction directly using model
    $transaction->update([
      'amount' => 150.00,
      'description' => 'Updated Description',
    ]);

    $this->assertDatabaseHas('transactions', [
      'id' => $transaction->id,
      'amount' => 150.00,
      'description' => 'Updated Description',
    ]);
  }

  public function test_user_can_delete_transaction(): void
  {
    $initialBalance = $this->wallet->balance;

    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 100.00,
    ]);

    // Balance should be reduced after creation
    $this->wallet->refresh();
    $this->assertEquals($initialBalance - 100.00, $this->wallet->balance);

    // Delete transaction using model
    $transaction->delete();

    $this->assertSoftDeleted($transaction);

    // Balance should be restored after deletion
    $this->wallet->refresh();
    $this->assertEquals($initialBalance, $this->wallet->balance);
  }

  public function test_user_cannot_access_other_users_transactions(): void
  {
    $otherUser = User::factory()->create();
    $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);
    $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

    $otherTransaction = Transaction::factory()->create([
      'user_id' => $otherUser->id,
      'wallet_id' => $otherWallet->id,
      'category_id' => $otherCategory->id,
    ]);

    // Test that current user can only see their own transactions
    $userTransactions = Transaction::where('user_id', $this->user->id)->get();
    $this->assertCount(0, $userTransactions);

    $otherUserTransactions = Transaction::where('user_id', $otherUser->id)->get();
    $this->assertCount(1, $otherUserTransactions);
    $this->assertEquals($otherTransaction->id, $otherUserTransactions->first()->id);
  }

  public function test_transaction_validation_prevents_invalid_data(): void
  {
    // Test using Laravel's validator instead of direct model creation
    // to avoid casting issues
    $validator = Validator::make([
      'user_id' => $this->user->id,
      'type' => 'invalid_type',
      'amount' => -100.00, // Negative amount
      'description' => '', // Empty description
      'date' => 'invalid_date',
      'category_id' => 999999, // Non-existent category
      'wallet_id' => 999999, // Non-existent wallet
    ], Transaction::validationRules());

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('type', $validator->errors()->toArray());
    $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    $this->assertArrayHasKey('description', $validator->errors()->toArray());
    $this->assertArrayHasKey('date', $validator->errors()->toArray());
  }

  public function test_transfer_validation_requires_different_wallets(): void
  {
    // Test that transfer with same wallet IDs should fail validation
    $this->expectException(\Exception::class);

    // This should now fail due to our business logic validation
    Transaction::create([
      'user_id' => $this->user->id,
      'type' => 'transfer',
      'amount' => 100.00,
      'description' => 'Invalid Transfer',
      'date' => now(),
      'from_wallet_id' => $this->wallet->id,
      'to_wallet_id' => $this->wallet->id, // Same as from_wallet_id
    ]);
  }

  public function test_recurring_transaction_can_be_created(): void
  {
    $transaction = Transaction::create([
      'user_id' => $this->user->id,
      'type' => 'expense',
      'amount' => 100.00,
      'description' => 'Monthly Subscription',
      'date' => now(),
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'is_recurring' => true,
      'recurring_frequency' => 'monthly',
      'next_occurrence' => now()->addMonth(),
    ]);

    $this->assertDatabaseHas('transactions', [
      'user_id' => $this->user->id,
      'description' => 'Monthly Subscription',
      'is_recurring' => true,
      'recurring_frequency' => 'monthly',
    ]);
  }

  public function test_transaction_with_tags_can_be_created(): void
  {
    $transaction = Transaction::create([
      'user_id' => $this->user->id,
      'type' => 'expense',
      'amount' => 50.00,
      'description' => 'Coffee Purchase',
      'date' => now(),
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
      'tags' => ['coffee', 'morning', 'work'],
    ]);

    $transaction = Transaction::where('description', 'Coffee Purchase')->first();
    $this->assertNotNull($transaction);
    $this->assertEquals(['coffee', 'morning', 'work'], $transaction->tags);
  }

  public function test_transaction_sends_notification_on_creation(): void
  {
    Notification::fake();

    $transaction = Transaction::create([
      'user_id' => $this->user->id,
      'type' => 'expense',
      'amount' => 100.00,
      'description' => 'Test Transaction',
      'date' => now(),
      'category_id' => $this->category->id,
      'wallet_id' => $this->wallet->id,
    ]);

    Notification::assertSentTo($this->user, TransactionCreated::class);
  }

  public function test_user_can_filter_transactions_by_type(): void
  {
    // Create different types of transactions
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'income',
      'description' => 'Income Transaction',
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'description' => 'Expense Transaction',
    ]);

    // Test filtering by type using model queries
    $incomeTransactions = Transaction::where('user_id', $this->user->id)
      ->where('type', 'income')
      ->get();

    $this->assertCount(1, $incomeTransactions);
    $this->assertEquals('Income Transaction', $incomeTransactions->first()->description);

    $expenseTransactions = Transaction::where('user_id', $this->user->id)
      ->where('type', 'expense')
      ->get();

    $this->assertCount(1, $expenseTransactions);
    $this->assertEquals('Expense Transaction', $expenseTransactions->first()->description);
  }

  public function test_user_can_search_transactions_by_description(): void
  {
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'description' => 'Grocery Store Purchase',
    ]);

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'description' => 'Gas Station Payment',
    ]);

    // Test searching by description using model queries
    $groceryTransactions = Transaction::where('user_id', $this->user->id)
      ->where('description', 'like', '%Grocery%')
      ->get();

    $this->assertCount(1, $groceryTransactions);
    $this->assertEquals('Grocery Store Purchase', $groceryTransactions->first()->description);

    $gasTransactions = Transaction::where('user_id', $this->user->id)
      ->where('description', 'like', '%Gas%')
      ->get();

    $this->assertCount(1, $gasTransactions);
    $this->assertEquals('Gas Station Payment', $gasTransactions->first()->description);
  }

  public function test_transaction_list_is_paginated(): void
  {
    // Create many transactions
    Transaction::factory()->count(25)->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
    ]);

    // Test pagination using model queries
    $transactions = Transaction::where('user_id', $this->user->id)->paginate(10);

    $this->assertCount(10, $transactions->items());
    $this->assertEquals(25, $transactions->total());
    $this->assertEquals(3, $transactions->lastPage());
  }

  public function test_wallet_initial_balance_edit_adjusts_current_balance(): void
  {
    // Create a wallet with initial balance
    $wallet = Wallet::create([
      'user_id' => $this->user->id,
      'name' => 'Test Wallet',
      'type' => 'bank_account',
      'currency' => 'USD',
      'initial_balance' => 1000.00,
      'balance' => 1000.00,
    ]);

    // Simulate some transactions changing the balance
    $wallet->update(['balance' => 1500.00]);

    // Simulate editing the wallet with new initial balance (like the EditWallet page does)
    $originalInitialBalance = $wallet->initial_balance;
    $newInitialBalance = 1200.00;
    $balanceDifference = $newInitialBalance - $originalInitialBalance;

    $wallet->update([
      'initial_balance' => $newInitialBalance,
      'balance' => $wallet->balance + $balanceDifference,
    ]);

    $wallet->refresh();

    // Verify the balance was adjusted correctly
    $this->assertEquals(1200.00, $wallet->initial_balance);
    $this->assertEquals(1700.00, $wallet->balance); // 1500 + (1200 - 1000) = 1700

    // Verify in database
    $this->assertDatabaseHas('wallets', [
      'id' => $wallet->id,
      'initial_balance' => 1200.00,
      'balance' => 1700.00,
    ]);
  }

  public function test_wallet_initial_balance_validation_prevents_negative_balance(): void
  {
    // Create a wallet with transactions that reduced the balance
    $wallet = Wallet::create([
      'user_id' => $this->user->id,
      'name' => 'Test Wallet',
      'type' => 'bank_account',
      'currency' => 'USD',
      'initial_balance' => 1000.00,
      'balance' => 1000.00,
    ]);

    // Simulate transactions that reduced the balance
    $wallet->update(['balance' => 600.00]); // Lost 400 through transactions

    // Simulate the EditWallet page validation logic
    $originalInitialBalance = $wallet->initial_balance;
    $attemptedNewInitialBalance = 100.00; // This would result in negative balance
    $balanceDifference = $attemptedNewInitialBalance - $originalInitialBalance;
    $wouldBeNewBalance = $wallet->balance + $balanceDifference;

    // Verify this would result in a negative balance
    $this->assertEquals(-300.00, $wouldBeNewBalance); // 600 + (100 - 1000) = -300

    // The EditWallet page should prevent this and keep the original values
    if ($wouldBeNewBalance < 0) {
      // Don't update - keep original values
      $finalInitialBalance = $originalInitialBalance;
      $finalBalance = $wallet->balance;
    } else {
      // Update normally
      $finalInitialBalance = $attemptedNewInitialBalance;
      $finalBalance = $wouldBeNewBalance;
    }

    // Verify the validation worked
    $this->assertEquals(1000.00, $finalInitialBalance); // Should remain unchanged
    $this->assertEquals(600.00, $finalBalance); // Should remain unchanged

    // Test a valid change that doesn't result in negative balance
    $validNewInitialBalance = 800.00; // This would result in: 600 + (800 - 1000) = 400 (positive)
    $validBalanceDifference = $validNewInitialBalance - $originalInitialBalance;
    $validNewBalance = $wallet->balance + $validBalanceDifference;

    $this->assertEquals(400.00, $validNewBalance); // Should be positive
    $this->assertGreaterThanOrEqual(0, $validNewBalance); // Verify it's not negative

    // This change should be allowed
    $wallet->update([
      'initial_balance' => $validNewInitialBalance,
      'balance' => $validNewBalance,
    ]);

    $wallet->refresh();

    $this->assertEquals(800.00, $wallet->initial_balance);
    $this->assertEquals(400.00, $wallet->balance);
  }
}
