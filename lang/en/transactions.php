<?php

return [
    'title' => 'Transactions',
    'create_transaction' => 'Create Transaction',
    'amount' => 'Amount',
    'type' => 'Type',
    'description' => 'Description',
    'date' => 'Date',
    'category' => 'Category',
    'wallet' => 'Wallet',
    'from_wallet' => 'From Wallet',
    'to_wallet' => 'To Wallet',
    'reference' => 'Reference',
    'tags' => 'Tags',
    'receipt' => 'Receipt',
    'notes' => 'Notes',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'income' => 'Income',
    'expense' => 'Expense',
    'transfer' => 'Transfer',
    'recurring' => 'Recurring',
    'is_recurring' => 'Is Recurring',
    'recurring_frequency' => 'Recurring Frequency',
    'next_occurrence' => 'Next Occurrence',
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'quarterly' => 'Quarterly',
    'semi_annually' => 'Semi-Annually',
    'yearly' => 'Yearly',

    // Validation messages
    'no_wallets_title' => 'No Active Wallets',
    'no_wallets_message' => 'You need to create at least one active wallet before creating transactions.',
    'insufficient_funds_title' => 'Insufficient Funds',
    'insufficient_funds_message' => 'Cannot create :amount transaction. Wallet ":wallet" only has :balance available.',
    'available_balance_hint' => 'Available balance: :balance',
    'validation_error' => 'Validation Error',

    // Transaction statuses
    'status_pending' => 'Pending',
    'status_completed' => 'Completed',
    'status_failed' => 'Failed',

    // Form sections
    'transaction_details' => 'Transaction Details',
    'category_and_wallet' => 'Category & Wallet',
    'additional_information' => 'Additional Information',
    'recurring_transaction' => 'Recurring Transaction',

    // Helper texts
    'reference_helper' => 'Auto-generated if left empty',
    'tags_placeholder' => 'Add tags...',
    'tags_helper' => 'Press Enter to add tags',
    'recurring_helper' => 'Make this transaction repeat automatically',
    'next_occurrence_helper' => 'When should the next occurrence be created?',
    'category_filtered_by_type' => 'Only categories matching the transaction type are shown',
    'select_type_first' => 'Select transaction type first',
    'no_category_for_transfer' => 'Transfers do not use categories',
    'select_category_for_type' => 'Select a :type category',

    // Stats widget
    'total_transactions' => 'Total Transactions',
    'all_transactions' => 'All transactions',
    'total_income' => 'Total Income',
    'money_received' => 'Money received',
    'total_expenses' => 'Total Expenses',
    'money_spent' => 'Money spent',
    'net_balance' => 'Net Balance',
    'transaction_flow' => 'Transaction Flow',
    'income_minus_expenses' => 'Income minus expenses',
    'income_minus_expenses_note' => 'Income minus expenses (excludes initial balances)',
    'positive_balance' => 'Positive balance',
    'negative_balance' => 'Negative balance',

    // Transaction types for translation tests
    'types' => [
        'income' => 'Income',
        'expense' => 'Expense',
        'transfer' => 'Transfer',
    ],

    // Count for pluralization tests
    'count' => '{0} no transactions|{1} 1 transaction|[2,*] :count transactions',
];
