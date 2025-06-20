<?php

return [
  // General
  'hello' => '¡Hola :name!',
  'thanks' => '¡Gracias por usar nuestra aplicación!',

  // Transaction Created
  'transaction_created' => 'Transacción Creada',
  'transaction_created_subject' => 'Nueva Transacción Creada',
  'transaction_created_message' => 'Se ha creado una nueva transacción de :type por :amount para ":description".',
  'view_transaction' => 'Ver Transacción',

  // Low Balance Alert
  'low_balance_alert' => 'Alerta de Saldo Bajo',
  'low_balance_subject' => 'Advertencia de Saldo Bajo',
  'low_balance_message' => 'Tu billetera ":wallet" tiene un saldo bajo de :balance (por debajo del límite de :threshold).',
  'view_wallet' => 'Ver Billetera',
  'consider_adding_funds' => 'Considera agregar fondos para evitar fallas en las transacciones.',

  // Budget Exceeded
  'budget_exceeded' => 'Presupuesto Excedido',
  'budget_exceeded_subject' => 'Límite de Presupuesto Excedido',
  'budget_exceeded_message' => 'Has gastado :spent en la categoría ":category", lo que excede tu presupuesto de :budget (:percentage% sobre el límite).',
  'view_category' => 'Ver Categoría',
  'consider_reviewing_spending' => 'Considera revisar tus hábitos de gasto para esta categoría.',

  // Recurring Transaction
  'recurring_transaction_created' => 'Transacción Recurrente Creada',
  'recurring_transaction_subject' => 'Transacción Recurrente Procesada',
  'recurring_transaction_message' => 'Se ha creado automáticamente una transacción recurrente de :type por :amount para ":description".',
  'next_occurrence' => 'Próxima ocurrencia: :date',

  // Notification Actions
  'mark_as_read' => 'Marcar como Leída',
  'view_all' => 'Ver Todas las Notificaciones',
  'clear_all' => 'Limpiar Todo',

  // Notification Settings
  'notification_preferences' => 'Preferencias de Notificación',
  'email_notifications' => 'Notificaciones por Email',
  'database_notifications' => 'Notificaciones en la App',
  'transaction_notifications' => 'Notificaciones de Transacciones',
  'balance_notifications' => 'Alertas de Saldo',
  'budget_notifications' => 'Alertas de Presupuesto',
  'recurring_notifications' => 'Notificaciones de Transacciones Recurrentes',

  // Status Messages
  'no_notifications' => 'Aún no hay notificaciones.',
  'all_caught_up' => '¡Estás al día!',
  'notifications_cleared' => 'Todas las notificaciones han sido eliminadas.',
  'notification_marked_read' => 'Notificación marcada como leída.',

  // Time Formatting
  'just_now' => 'Ahora mismo',
  'minutes_ago' => 'hace :count minuto|hace :count minutos',
  'hours_ago' => 'hace :count hora|hace :count horas',
  'days_ago' => 'hace :count día|hace :count días',
  'weeks_ago' => 'hace :count semana|hace :count semanas',
];
