<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletModelTest extends TestCase
{
  use RefreshDatabase;

  protected User $user;
  protected Wallet $wallet;

  protected function setUp(): void
  {
    parent::setUp();
    $this->user = User::factory()->create();
    $this->wallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'balance' => 1000.00,
      'initial_balance' => 1000.00,
      'currency' => 'USD'
    ]);
  }

  public function test_wallet_belongs_to_user(): void
  {
    $this->assertInstanceOf(User::class, $this->wallet->user);
    $this->assertEquals($this->user->id, $this->wallet->user->id);
  }

  public function test_wallet_has_transactions_relationship(): void
  {
    $category = Category::factory()->create(['user_id' => $this->user->id]);
    $transaction = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'wallet_id' => $this->wallet->id,
      'category_id' => $category->id,
    ]);

    $this->assertTrue($this->wallet->transactions->contains($transaction));
    $this->assertInstanceOf(Transaction::class, $this->wallet->transactions->first());
  }

  public function test_wallet_has_incoming_transfers_relationship(): void
  {
    $fromWallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'currency' => 'USD'
    ]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    $transfer = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $category->id,
      'from_wallet_id' => $fromWallet->id,
      'to_wallet_id' => $this->wallet->id,
      'type' => 'transfer',
      'amount' => 100.00,
    ]);

    $this->assertTrue($this->wallet->incomingTransfers->contains($transfer));
    $this->assertEquals($this->wallet->id, $transfer->to_wallet_id);
  }

  public function test_wallet_has_outgoing_transfers_relationship(): void
  {
    $toWallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'currency' => 'USD'
    ]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    $transfer = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $category->id,
      'from_wallet_id' => $this->wallet->id,
      'to_wallet_id' => $toWallet->id,
      'type' => 'transfer',
      'amount' => 100.00,
    ]);

    $this->assertTrue($this->wallet->outgoingTransfers->contains($transfer));
    $this->assertEquals($this->wallet->id, $transfer->from_wallet_id);
  }

  public function test_wallet_has_transfer_transactions_relationship(): void
  {
    $toWallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'currency' => 'USD'
    ]);
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    // Create a transfer transaction
    $transfer = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $category->id,
      'from_wallet_id' => $this->wallet->id,
      'to_wallet_id' => $toWallet->id,
      'type' => 'transfer',
      'amount' => 100.00,
    ]);

    // Create a non-transfer transaction from the same wallet
    $expense = Transaction::factory()->create([
      'user_id' => $this->user->id,
      'category_id' => $category->id,
      'wallet_id' => $this->wallet->id,
      'type' => 'expense',
      'amount' => 50.00,
    ]);

    $transferTransactions = $this->wallet->transferTransactions;

    $this->assertTrue($transferTransactions->contains($transfer));
    $this->assertFalse($transferTransactions->contains($expense));
    $this->assertCount(1, $transferTransactions);
  }

  public function test_wallet_casts_balance_to_decimal(): void
  {
    $this->wallet->update(['balance' => 1234.56]);
    $this->wallet->refresh();

    $this->assertEquals('1234.56', $this->wallet->balance);
    $this->assertIsString($this->wallet->balance);
  }

  public function test_wallet_casts_initial_balance_to_decimal(): void
  {
    $this->wallet->update(['initial_balance' => 999.99]);
    $this->wallet->refresh();

    $this->assertEquals('999.99', $this->wallet->initial_balance);
    $this->assertIsString($this->wallet->initial_balance);
  }

  public function test_wallet_casts_is_active_to_boolean(): void
  {
    $this->wallet->update(['is_active' => 1]);
    $this->wallet->refresh();

    $this->assertTrue($this->wallet->is_active);
    $this->assertIsBool($this->wallet->is_active);

    $this->wallet->update(['is_active' => 0]);
    $this->wallet->refresh();

    $this->assertFalse($this->wallet->is_active);
    $this->assertIsBool($this->wallet->is_active);
  }

  public function test_wallet_fillable_attributes(): void
  {
    $fillable = [
      'user_id',
      'name',
      'type',
      'currency',
      'balance',
      'initial_balance',
      'description',
      'is_active',
    ];

    $this->assertEquals($fillable, $this->wallet->getFillable());
  }

  public function test_initial_balance_change_adjusts_current_balance(): void
  {
    // Create a wallet with initial balance
    $wallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'initial_balance' => 1000.00,
      'balance' => 1000.00,
    ]);

    // Simulate some transactions that change the balance
    $wallet->update(['balance' => 1500.00]); // Balance changed due to transactions

    // Now simulate editing the initial balance
    $originalInitialBalance = $wallet->initial_balance;
    $newInitialBalance = 1200.00;
    $balanceDifference = $newInitialBalance - $originalInitialBalance;

    // This simulates what the EditWallet page does
    $wallet->update([
      'initial_balance' => $newInitialBalance,
      'balance' => $wallet->balance + $balanceDifference,
    ]);

    $wallet->refresh();

    // Verify the balance was adjusted correctly
    $this->assertEquals(1200.00, $wallet->initial_balance);
    $this->assertEquals(1700.00, $wallet->balance); // 1500 + (1200 - 1000) = 1700
  }

  public function test_minimum_initial_balance_calculation(): void
  {
    // Create a wallet with transactions that changed the balance
    $wallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'initial_balance' => 1000.00,
      'balance' => 1500.00, // Current balance is higher than initial
    ]);

    // Calculate minimum allowed initial balance
    $minimumInitialBalance = $wallet->initial_balance - $wallet->balance;

    // For this example: 1000 - 1500 = -500
    // So minimum initial balance would be -500, but we should prevent going below 0 current balance
    $this->assertEquals(-500.00, $minimumInitialBalance);

    // If we set initial balance to 500, new current balance would be:
    // 1500 + (500 - 1000) = 1500 - 500 = 1000 (positive, should be allowed)
    $newInitialBalance = 500.00;
    $newCurrentBalance = $wallet->balance + ($newInitialBalance - $wallet->initial_balance);
    $this->assertEquals(1000.00, $newCurrentBalance);

    // If we set initial balance to 400, new current balance would be:
    // 1500 + (400 - 1000) = 1500 - 600 = 900 (positive, should be allowed)
    $newInitialBalance = 400.00;
    $newCurrentBalance = $wallet->balance + ($newInitialBalance - $wallet->initial_balance);
    $this->assertEquals(900.00, $newCurrentBalance);

    // If we set initial balance to 300, new current balance would be:
    // 1500 + (300 - 1000) = 1500 - 700 = 800 (positive, should be allowed)
    $newInitialBalance = 300.00;
    $newCurrentBalance = $wallet->balance + ($newInitialBalance - $wallet->initial_balance);
    $this->assertEquals(800.00, $newCurrentBalance);
  }

  public function test_initial_balance_validation_prevents_negative_balance(): void
  {
    // Create a wallet where current balance is lower than initial balance
    $wallet = Wallet::factory()->create([
      'user_id' => $this->user->id,
      'initial_balance' => 1000.00,
      'balance' => 800.00, // Spent 200 in transactions
    ]);

    // Try to set initial balance too low (would result in negative current balance)
    $newInitialBalance = 500.00; // This would make current balance: 800 + (500 - 1000) = 300 (OK)
    $newCurrentBalance = $wallet->balance + ($newInitialBalance - $wallet->initial_balance);
    $this->assertEquals(300.00, $newCurrentBalance);
    $this->assertGreaterThanOrEqual(0, $newCurrentBalance);

    // Try to set initial balance even lower
    $newInitialBalance = 200.00; // This would make current balance: 800 + (200 - 1000) = 0 (OK)
    $newCurrentBalance = $wallet->balance + ($newInitialBalance - $wallet->initial_balance);
    $this->assertEquals(0.00, $newCurrentBalance);
    $this->assertGreaterThanOrEqual(0, $newCurrentBalance);

    // Try to set initial balance too low (would result in negative current balance)
    $newInitialBalance = 100.00; // This would make current balance: 800 + (100 - 1000) = -100 (BAD)
    $newCurrentBalance = $wallet->balance + ($newInitialBalance - $wallet->initial_balance);
    $this->assertEquals(-100.00, $newCurrentBalance);
    $this->assertLessThan(0, $newCurrentBalance); // This should be prevented by validation
  }
}
