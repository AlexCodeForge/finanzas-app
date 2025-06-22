<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'currency' => 'USD',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'theme' => 'light',
            'notification_preferences' => ['email' => true, 'database' => true],
        ]);
    }

    public function test_user_has_wallets_relationship(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->assertTrue($this->user->wallets->contains($wallet));
        $this->assertInstanceOf(Wallet::class, $this->user->wallets->first());
    }

    public function test_user_has_transactions_relationship(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
        ]);

        $this->assertTrue($this->user->transactions->contains($transaction));
        $this->assertInstanceOf(Transaction::class, $this->user->transactions->first());
    }

    public function test_user_has_categories_relationship(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $this->assertTrue($this->user->categories->contains($category));
        $this->assertInstanceOf(Category::class, $this->user->categories->first());
    }

    public function test_user_has_preferred_wallets_relationships(): void
    {
        $wallet1 = Wallet::factory()->create(['user_id' => $this->user->id]);
        $wallet2 = Wallet::factory()->create(['user_id' => $this->user->id]);
        $wallet3 = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->user->update([
            'preferred_wallet_1_id' => $wallet1->id,
            'preferred_wallet_2_id' => $wallet2->id,
            'preferred_wallet_3_id' => $wallet3->id,
        ]);

        $this->assertEquals($wallet1->id, $this->user->preferredWallet1->id);
        $this->assertEquals($wallet2->id, $this->user->preferredWallet2->id);
        $this->assertEquals($wallet3->id, $this->user->preferredWallet3->id);
    }

    public function test_preferred_wallets_attribute_filters_null_values(): void
    {
        $wallet1 = Wallet::factory()->create(['user_id' => $this->user->id]);
        $wallet3 = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->user->update([
            'preferred_wallet_1_id' => $wallet1->id,
            'preferred_wallet_2_id' => null,
            'preferred_wallet_3_id' => $wallet3->id,
        ]);

        // Refresh the user to ensure relationships are loaded properly
        $this->user->refresh();

        $preferredWallets = $this->user->preferred_wallets;

        $this->assertCount(2, $preferredWallets);
        $this->assertTrue($preferredWallets->contains('id', $wallet1->id));
        $this->assertTrue($preferredWallets->contains('id', $wallet3->id));
    }

    public function test_user_casts_notification_preferences_to_array(): void
    {
        $preferences = ['email' => true, 'database' => false, 'sms' => true];

        $this->user->update(['notification_preferences' => $preferences]);
        $this->user->refresh();

        $this->assertEquals($preferences, $this->user->notification_preferences);
        $this->assertIsArray($this->user->notification_preferences);
    }

    public function test_user_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'email',
            'password',
            'avatar',
            'timezone',
            'currency',
            'date_format',
            'theme',
            'notification_preferences',
            'preferred_wallet_1_id',
            'preferred_wallet_2_id',
            'preferred_wallet_3_id',
            'language'
        ];

        $this->assertEquals($fillable, $this->user->getFillable());
    }

    public function test_user_hidden_attributes(): void
    {
        $hidden = ['password', 'remember_token'];

        $this->assertEquals($hidden, $this->user->getHidden());
    }
}
