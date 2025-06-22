<?php

return [
  'title' => 'Billeteras',
  'name' => 'Nombre',
  'type' => 'Tipo',
  'currency' => 'Moneda',
  'color' => 'Color',
  'balance' => 'Balance',
  'initial_balance' => 'Balance Inicial',
  'current_balance' => 'Balance Actual',
  'description' => 'Descripción',
  'is_active' => 'Activo',
  'active' => 'Activo',
  'created_at' => 'Creado En',
  'updated_at' => 'Actualizado En',
  'checking' => 'Cuenta Corriente',
  'bank_account' => 'Cuenta Bancaria',
  'cash' => 'Efectivo',
  'credit_card' => 'Tarjeta de Crédito',
  'savings' => 'Ahorros',
  'investment' => 'Inversión',
  'other' => 'Otro',
  'total_balance' => 'Balance Total',
  'available_balance' => 'Balance Disponible',
  'create_wallet' => 'Crear Billetera',
  'transactions' => 'Transacciones',

  // Form sections
  'wallet_information' => 'Información de Billetera',
  'balance_information' => 'Información de Balance',

  // Helper texts
  'currency_helper' => 'Selecciona la moneda para esta billetera (afecta compatibilidad de transferencias)',
  'active_helper' => 'Las billeteras inactivas se ocultarán de los formularios de transacciones',
  'initial_balance_helper' => 'El saldo inicial para esta billetera',
  'initial_balance_edit_helper' => 'Cambiar esto ajustará el saldo actual por la diferencia',
  'minimum_initial_balance_hint' => 'Mínimo permitido: :minimum',

  // Validation messages
  'invalid_initial_balance_title' => 'Saldo Inicial Inválido',
  'invalid_initial_balance_message' => 'No se puede establecer un saldo inicial que resulte en un saldo actual negativo. Saldo actual: :current_balance. Saldo inicial mínimo permitido: :minimum_initial_balance',

  // Status messages
  'balance_placeholder' => 'Se establecerá al balance inicial',
  'description_placeholder' => 'Descripción opcional para esta billetera...',

  // Filters and Actions
  'minimum_balance' => 'Balance Mínimo',
  'maximum_balance' => 'Balance Máximo',
  'view_transactions' => 'Ver Transacciones',
  'activate_selected' => 'Activar Seleccionadas',
  'deactivate_selected' => 'Desactivar Seleccionadas',
  'actions' => 'Acciones',

  // Stats widget
  'total_wallets' => 'Total de Billeteras',
  'all_wallets' => 'Todas las billeteras del sistema',
  'active_wallets' => 'Billeteras Activas',
  'currently_active' => 'Actualmente activas',
  'combined_balance' => 'Balance combinado',
  'average_balance' => 'Balance Promedio',
  'previous_month_balance' => 'Balance del Mes Anterior',

  // Wallet types for translation tests
  'types' => [
    'checking' => 'Cuenta Corriente',
    'savings' => 'Ahorros',
    'credit_card' => 'Tarjeta de Crédito',
    'cash' => 'Efectivo',
    'investment' => 'Inversión',
    'other' => 'Otro',
  ],
];
