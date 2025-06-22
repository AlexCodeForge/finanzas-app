<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Category;
use App\Notifications\TransactionCreated;
use App\Notifications\LowBalanceAlert;
use App\Notifications\BudgetExceeded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class TransactionModelTest extends TestCase
{
  use RefreshDatabase;

  protected User $user;
  protected Wallet $wallet;
  protected Category $category;

  protected function setUp(): void
  {
    parent::setUp();

    Notification::fake();

    $this->user = User::factory()->create();
    $this->wallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'balance' => 1000.00,
      'currency' => 'USD'
    ]);
    $this->category = Category::factory()->create([
      'user_id' => $this->user->id,
      'budget_limit' => 500.00,
    ]);
  }

  public function test_transaction_belongs_to_user(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
    ]);

    $this->assertInstanceOf(User::class, $transaction->user);
    $this->assertEquals($this->user->id, $transaction->user->id);
  }

  public function test_transaction_belongs_to_category(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
    ]);

    $this->assertInstanceOf(Category::class, $transaction->category);
    $this->assertEquals($this->category->id, $transaction->category->id);
  }

  public function test_transaction_belongs_to_wallet(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
    ]);

    $this->assertInstanceOf(Wallet::class, $transaction->wallet);
    $this->assertEquals($this->wallet->id, $transaction->wallet->id);
  }

  public function test_transaction_has_transfer_relationships(): void
  {
    $fromWallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'currency' => 'USD'
    ]);
    $toWallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'currency' => 'USD'
    ]);

    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'from_wallet_id' => $fromWallet->id,
      'to_wallet_id' => $toWallet->id,
      'type' => 'transfer',
    ]);

    $this->assertInstanceOf(Wallet::class, $transaction->fromWallet);
    $this->assertInstanceOf(Wallet::class, $transaction->toWallet);
    $this->assertEquals($fromWallet->id, $transaction->fromWallet->id);
    $this->assertEquals($toWallet->id, $transaction->toWallet->id);
  }

  public function test_transaction_has_parent_child_relationships(): void
  {
    $parentTransaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'is_recurring' => true,
    ]);

    $childTransaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'parent_transaction_id' => $parentTransaction->id,
    ]);

    $this->assertInstanceOf(Transaction::class, $childTransaction->parentTransaction);
    $this->assertEquals($parentTransaction->id, $childTransaction->parent_transaction_id);
    $this->assertTrue($parentTransaction->childTransactions->contains($childTransaction));
  }

  public function test_transaction_generates_reference_on_creation(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
    ]);

    $this->assertNotNull($transaction->reference);
    $this->assertStringStartsWith('EXP-', $transaction->reference);
  }

  public function test_generate_reference_creates_correct_prefixes(): void
  {
    $transaction = new Transaction(['type' => 'income']);
    $reference = $transaction->generateReference();
    $this->assertStringStartsWith('INC-', $reference);

    $transaction = new Transaction(['type' => 'expense']);
    $reference = $transaction->generateReference();
    $this->assertStringStartsWith('EXP-', $reference);

    $transaction = new Transaction(['type' => 'transfer']);
    $reference = $transaction->generateReference();
    $this->assertStringStartsWith('TRF-', $reference);
  }

  public function test_income_transaction_increases_wallet_balance(): void
  {
    $initialBalance = $this->wallet->balance;

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'income',
      'amount' => 200.00,
    ]);

    $this->wallet->refresh();
    $this->assertEquals($initialBalance + 200.00, $this->wallet->balance);
  }

  public function test_expense_transaction_decreases_wallet_balance(): void
  {
    $initialBalance = $this->wallet->balance;

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 300.00,
    ]);

    $this->wallet->refresh();
    $this->assertEquals($initialBalance - 300.00, $this->wallet->balance);
  }

  public function test_transfer_transaction_updates_both_wallets(): void
  {
    $toWallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'balance' => 500.00,
      'currency' => 'USD'
    ]);

    $fromInitialBalance = $this->wallet->balance;
    $toInitialBalance = $toWallet->balance;

    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'from_wallet_id' => $this->wallet->id,
      'to_wallet_id' => $toWallet->id,
      'type' => 'transfer',
      'amount' => 150.00,
    ]);

    $this->wallet->refresh();
    $toWallet->refresh();

    $this->assertEquals($fromInitialBalance - 150.00, $this->wallet->balance);
    $this->assertEquals($toInitialBalance + 150.00, $toWallet->balance);
  }

  public function test_transaction_deletion_reverses_balance_changes(): void
  {
    $initialBalance = $this->wallet->balance;

    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 100.00,
    ]);

    $this->wallet->refresh();
    $this->assertEquals($initialBalance - 100.00, $this->wallet->balance);

    $transaction->delete();

    $this->wallet->refresh();
    $this->assertEquals($initialBalance, $this->wallet->balance);
  }

  public function test_transaction_update_triggers_balance_recalculation(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 100.00,
    ]);

    $this->wallet->refresh();
    $balanceAfterCreation = $this->wallet->balance;

    $transaction->update(['amount' => 200.00]);

    $this->wallet->refresh();
    $this->assertEquals($balanceAfterCreation - 100.00, $this->wallet->balance);
  }

  public function test_transaction_sends_notification_on_creation(): void
  {
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 100.00,
    ]);

    Notification::assertSentTo($this->user, TransactionCreated::class);
  }

  public function test_low_balance_alert_is_sent_when_threshold_reached(): void
  {
    // Set wallet balance to just above threshold
    $this->wallet->update(['balance' => 150.00]);

    // Create expense that brings balance below threshold (100)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 75.00,
    ]);

    Notification::assertSentTo($this->user, LowBalanceAlert::class);
  }

  public function test_budget_exceeded_alert_is_sent_when_budget_exceeded(): void
  {
    // Create expense that exceeds category budget (500.00)
    Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
      'amount' => 600.00,
      'date' => now(), // Ensure it's in the current month
    ]);

    Notification::assertSentTo($this->user, BudgetExceeded::class);
  }

  public function test_is_transfer_method_returns_correct_value(): void
  {
    $transferTransaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $this->category->id,
      'type' => 'transfer',
    ]);

    $expenseTransaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'type' => 'expense',
    ]);

    $this->assertTrue($transferTransaction->isTransfer());
    $this->assertFalse($expenseTransaction->isTransfer());
  }

  public function test_is_recurring_method_returns_correct_value(): void
  {
    $recurringTransaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'is_recurring' => true,
    ]);

    $regularTransaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'is_recurring' => false,
    ]);

    $this->assertTrue($recurringTransaction->isRecurring());
    $this->assertFalse($regularTransaction->isRecurring());
  }

  public function test_formatted_amount_attribute_formats_correctly(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'amount' => 1234.56,
    ]);

    $this->assertEquals('$1,234.56', $transaction->formatted_amount);
  }

  public function test_transaction_casts_attributes_correctly(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
      'amount' => 123.45,
      'date' => '2024-01-15',
      'tags' => ['food', 'restaurant'],
      'is_recurring' => true,
      'next_occurrence' => '2024-02-15',
    ]);

    $this->assertEquals('123.45', $transaction->amount);
    $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->date);
    $this->assertIsArray($transaction->tags);
    $this->assertEquals(['food', 'restaurant'], $transaction->tags);
    $this->assertTrue($transaction->is_recurring);
    $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->next_occurrence);
  }

  public function test_validation_rules_are_correct(): void
  {
    $rules = Transaction::validationRules();

    $expectedRules = [
      'amount' => ['required', 'numeric', 'min:0.01'],
      'type' => ['required', 'in:income,expense,transfer'],
      'description' => ['required', 'string', 'max:255'],
      'date' => ['required', 'date', 'before_or_equal:today'],
      'category_id' => ['nullable', 'exists:categories,id'],
      'wallet_id' => ['nullable', 'exists:wallets,id'],
      'from_wallet_id' => ['nullable', 'exists:wallets,id'],
      'to_wallet_id' => ['nullable', 'exists:wallets,id'],
      'tags' => ['nullable', 'array'],
      'tags.*' => ['string', 'max:50'],
      'notes' => ['nullable', 'string', 'max:1000'],
    ];

    $this->assertEquals($expectedRules, $rules);
  }

  public function test_soft_deletes_are_enabled(): void
  {
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $this->category->id,
    ]);

    $transaction->delete();

    $this->assertSoftDeleted($transaction);
    $this->assertNotNull($transaction->deleted_at);
  }
}
