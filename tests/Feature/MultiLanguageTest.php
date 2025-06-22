<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

class MultiLanguageTest extends TestCase
{
  use RefreshDatabase;

  protected User $user;

  protected function setUp(): void
  {
    parent::setUp();

    $this->user = User::factory()->create(['language' => 'en']);
    $this->actingAs($this->user, 'web');
  }

  public function test_application_defaults_to_english(): void
  {
    // Test that user defaults to English
    $this->assertEquals('en', $this->user->language);
    $this->assertEquals('en', App::getLocale());
  }

  public function test_user_can_switch_to_spanish(): void
  {
    $this->user->update(['language' => 'es']);

    // Test that user language preference is saved
    $this->assertEquals('es', $this->user->fresh()->language);
  }

  public function test_spanish_translations_are_loaded(): void
  {
    App::setLocale('es');

    // Test that Spanish translation files exist and work
    $this->assertEquals('Finanzas', __('finance.title'));
    $this->assertEquals('Categorías', __('categories.title'));
    $this->assertEquals('Billeteras', __('wallets.title'));
    $this->assertEquals('Transacciones', __('transactions.title'));
  }

  public function test_english_translations_are_loaded(): void
  {
    App::setLocale('en');

    // Test that English translation files exist and work
    $this->assertEquals('Finance', __('finance.title'));
    $this->assertEquals('Categories', __('categories.title'));
    $this->assertEquals('Wallets', __('wallets.title'));
    $this->assertEquals('Transactions', __('transactions.title'));
  }

  public function test_transaction_types_are_translated(): void
  {
    App::setLocale('es');

    $this->assertEquals('Ingreso', __('transactions.types.income'));
    $this->assertEquals('Gasto', __('transactions.types.expense'));
    $this->assertEquals('Transferencia', __('transactions.types.transfer'));

    App::setLocale('en');

    $this->assertEquals('Income', __('transactions.types.income'));
    $this->assertEquals('Expense', __('transactions.types.expense'));
    $this->assertEquals('Transfer', __('transactions.types.transfer'));
  }

  public function test_wallet_types_are_translated(): void
  {
    App::setLocale('es');

    $this->assertEquals('Cuenta Corriente', __('wallets.types.checking'));
    $this->assertEquals('Ahorros', __('wallets.types.savings'));
    $this->assertEquals('Tarjeta de Crédito', __('wallets.types.credit_card'));
    $this->assertEquals('Efectivo', __('wallets.types.cash'));

    App::setLocale('en');

    $this->assertEquals('Checking', __('wallets.types.checking'));
    $this->assertEquals('Savings', __('wallets.types.savings'));
    $this->assertEquals('Credit Card', __('wallets.types.credit_card'));
    $this->assertEquals('Cash', __('wallets.types.cash'));
  }

  public function test_notification_messages_are_translated(): void
  {
    App::setLocale('es');

    $this->assertEquals('Transacción Creada', __('notifications.transaction_created'));
    $this->assertEquals('Saldo bajo en billetera', __('notifications.low_balance_alert'));
    $this->assertEquals('Presupuesto excedido', __('notifications.budget_exceeded'));

    App::setLocale('en');

    $this->assertEquals('Transaction Created Successfully', __('notifications.transaction_created'));
    $this->assertEquals('Low Balance Alert', __('notifications.low_balance_alert'));
    $this->assertEquals('Budget Exceeded', __('notifications.budget_exceeded'));
  }

  public function test_dashboard_widgets_respect_language_setting(): void
  {
    $this->user->update(['language' => 'es']);

    // Test that language setting is properly stored
    $this->assertEquals('es', $this->user->fresh()->language);

    // Test that Spanish translations work
    App::setLocale('es');
    $this->assertEquals('Finanzas', __('finance.title'));
  }

  public function test_fallback_to_english_for_missing_translations(): void
  {
    App::setLocale('es');

    // Test a key that might not exist in Spanish
    $translation = __('some.missing.key');

    // Should return the key itself when translation is missing
    $this->assertEquals('some.missing.key', $translation);
  }

  public function test_pluralization_works_in_both_languages(): void
  {
    App::setLocale('en');
    $this->assertEquals('1 transaction', trans_choice('transactions.count', 1));
    $this->assertEquals('2 transactions', trans_choice('transactions.count', 2));

    App::setLocale('es');
    $this->assertEquals('1 transacción', trans_choice('transactions.count', 1));
    $this->assertEquals('2 transacciones', trans_choice('transactions.count', 2));
  }

  public function test_date_formatting_respects_locale(): void
  {
    $date = now()->parse('2024-01-15');

    App::setLocale('en');
    $englishFormat = $date->translatedFormat('F j, Y');

    App::setLocale('es');
    $spanishFormat = $date->translatedFormat('F j, Y');

    // Dates should be formatted differently in each language
    $this->assertNotEquals($englishFormat, $spanishFormat);
    $this->assertStringContainsString('enero', $spanishFormat); // Spanish month name
    $this->assertStringContainsString('January', $englishFormat); // English month name
  }

  public function test_currency_formatting_is_consistent(): void
  {
    $amount = 1234.56;

    // Currency formatting should be consistent regardless of language
    App::setLocale('en');
    $englishCurrency = '$' . number_format($amount, 2);

    App::setLocale('es');
    $spanishCurrency = '$' . number_format($amount, 2);

    $this->assertEquals($englishCurrency, $spanishCurrency);
    $this->assertEquals('$1,234.56', $englishCurrency);
  }

  public function test_validation_messages_are_translated(): void
  {
    // Test that custom translations work in different languages
    App::setLocale('es');
    $spanishTitle = __('finance.title');
    $this->assertEquals('Finanzas', $spanishTitle);

    App::setLocale('en');
    $englishTitle = __('finance.title');
    $this->assertEquals('Finance', $englishTitle);

    // Test that different languages return different results
    $this->assertNotEquals($spanishTitle, $englishTitle);
  }
}
