<?php

return [
  // General
  'hello' => 'Hello :name!',
  'thanks' => 'Thank you for using our application!',

  // Transaction Created
  'transaction_created' => 'Transaction Created',
  'transaction_created_subject' => 'New Transaction Created',
  'transaction_created_message' => 'A new :type transaction of :amount has been created for ":description".',
  'view_transaction' => 'View Transaction',

  // Low Balance Alert
  'low_balance_alert' => 'Low Balance Alert',
  'low_balance_subject' => 'Low Balance Warning',
  'low_balance_message' => 'Your wallet ":wallet" has a low balance of :balance (below threshold of :threshold).',
  'view_wallet' => 'View Wallet',
  'consider_adding_funds' => 'Consider adding funds to avoid transaction failures.',

  // Budget Exceeded
  'budget_exceeded' => 'Budget Exceeded',
  'budget_exceeded_subject' => 'Budget Limit Exceeded',
  'budget_exceeded_message' => 'You have spent :spent in ":category" category, which exceeds your budget of :budget (:percentage% over limit).',
  'view_category' => 'View Category',
  'consider_reviewing_spending' => 'Consider reviewing your spending habits for this category.',

  // Recurring Transaction
  'recurring_transaction_created' => 'Recurring Transaction Created',
  'recurring_transaction_subject' => 'Recurring Transaction Processed',
  'recurring_transaction_message' => 'A recurring :type transaction of :amount has been automatically created for ":description".',
  'next_occurrence' => 'Next occurrence: :date',

  // Notification Actions
  'mark_as_read' => 'Mark as Read',
  'view_all' => 'View All Notifications',
  'clear_all' => 'Clear All',

  // Notification Settings
  'notification_preferences' => 'Notification Preferences',
  'email_notifications' => 'Email Notifications',
  'database_notifications' => 'In-App Notifications',
  'transaction_notifications' => 'Transaction Notifications',
  'balance_notifications' => 'Balance Alerts',
  'budget_notifications' => 'Budget Alerts',
  'recurring_notifications' => 'Recurring Transaction Notifications',

  // Status Messages
  'no_notifications' => 'No notifications yet.',
  'all_caught_up' => 'You\'re all caught up!',
  'notifications_cleared' => 'All notifications have been cleared.',
  'notification_marked_read' => 'Notification marked as read.',

  // Time Formatting
  'just_now' => 'Just now',
  'minutes_ago' => ':count minute ago|:count minutes ago',
  'hours_ago' => ':count hour ago|:count hours ago',
  'days_ago' => ':count day ago|:count days ago',
  'weeks_ago' => ':count week ago|:count weeks ago',
];
