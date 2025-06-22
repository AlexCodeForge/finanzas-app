<?php

return [
  'title' => 'Wallets',
  'name' => 'Name',
  'type' => 'Type',
  'currency' => 'Currency',
  'color' => 'Color',
  'balance' => 'Balance',
  'initial_balance' => 'Initial Balance',
  'current_balance' => 'Current Balance',
  'description' => 'Description',
  'is_active' => 'Active',
  'active' => 'Active',
  'created_at' => 'Created At',
  'updated_at' => 'Updated At',
  'checking' => 'Checking',
  'bank_account' => 'Bank Account',
  'cash' => 'Cash',
  'credit_card' => 'Credit Card',
  'savings' => 'Savings',
  'investment' => 'Investment',
  'other' => 'Other',
  'total_balance' => 'Total Balance',
  'available_balance' => 'Available Balance',
  'create_wallet' => 'Create Wallet',
  'transactions' => 'Transactions',

  // Form sections
  'wallet_information' => 'Wallet Information',
  'balance_information' => 'Balance Information',

  // Helper texts
  'currency_helper' => 'Select the currency for this wallet (affects transfer compatibility)',
  'active_helper' => 'Inactive wallets will be hidden from transaction forms',
  'initial_balance_helper' => 'The starting balance for this wallet',
  'initial_balance_edit_helper' => 'Changing this will adjust the current balance by the difference',
  'minimum_initial_balance_hint' => 'Minimum allowed: :minimum',

  // Validation messages
  'invalid_initial_balance_title' => 'Invalid Initial Balance',
  'invalid_initial_balance_message' => 'Cannot set initial balance that would result in a negative current balance. Current balance: :current_balance. Minimum allowed initial balance: :minimum_initial_balance',

  // Status messages
  'balance_placeholder' => 'Will be set to initial balance',
  'description_placeholder' => 'Optional description for this wallet...',

  // Filters and Actions
  'minimum_balance' => 'Minimum Balance',
  'maximum_balance' => 'Maximum Balance',
  'view_transactions' => 'View Transactions',
  'activate_selected' => 'Activate Selected',
  'deactivate_selected' => 'Deactivate Selected',
  'actions' => 'Actions',

  // Stats widget
  'total_wallets' => 'Total Wallets',
  'all_wallets' => 'All wallets in system',
  'active_wallets' => 'Active Wallets',
  'currently_active' => 'Currently active',
  'combined_balance' => 'Combined balance',
  'average_balance' => 'Average Balance',
  'previous_month_balance' => 'Previous Month Balance',

  // Wallet types for translation tests
  'types' => [
    'checking' => 'Checking',
    'savings' => 'Savings',
    'credit_card' => 'Credit Card',
    'cash' => 'Cash',
    'investment' => 'Investment',
    'other' => 'Other',
  ],
];
