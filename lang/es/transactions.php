<?php

return [
    'title' => 'Transacciones',
    'create_transaction' => 'Crear Transacción',
    'amount' => 'Monto',
    'type' => 'Tipo',
    'description' => 'Descripción',
    'date' => 'Fecha',
    'category' => 'Categoría',
    'wallet' => 'Billetera',
    'from_wallet' => 'Desde Billetera',
    'to_wallet' => 'Hacia Billetera',
    'reference' => 'Referencia',
    'tags' => 'Etiquetas',
    'receipt' => 'Recibo',
    'notes' => 'Notas',
    'created_at' => 'Creado En',
    'updated_at' => 'Actualizado En',
    'income' => 'Ingreso',
    'expense' => 'Gasto',
    'transfer' => 'Transferencia',
    'recurring' => 'Recurrente',
    'is_recurring' => 'Es Recurrente',
    'recurring_frequency' => 'Frecuencia Recurrente',
    'next_occurrence' => 'Próxima Ocurrencia',
    'daily' => 'Diario',
    'weekly' => 'Semanal',
    'monthly' => 'Mensual',
    'quarterly' => 'Trimestral',
    'semi_annually' => 'Semestral',
    'yearly' => 'Anual',

    // Validation messages
    'no_wallets_title' => 'Sin Billeteras Activas',
    'no_wallets_message' => 'Necesitas crear al menos una billetera activa antes de crear transacciones.',
    'insufficient_funds_title' => 'Fondos Insuficientes',
    'insufficient_funds_message' => 'No se puede crear transacción de :amount. La billetera ":wallet" solo tiene :balance disponible.',
    'available_balance_hint' => 'Saldo disponible: :balance',
    'validation_error' => 'Error de Validación',

    // Transaction statuses
    'status_pending' => 'Pendiente',
    'status_completed' => 'Completada',
    'status_failed' => 'Fallida',

    // Form sections
    'transaction_details' => 'Detalles de Transacción',
    'category_and_wallet' => 'Categoría y Billetera',
    'additional_information' => 'Información Adicional',
    'recurring_transaction' => 'Transacción Recurrente',

    // Helper texts
    'reference_helper' => 'Se genera automáticamente si se deja vacío',
    'tags_placeholder' => 'Agregar etiquetas...',
    'tags_helper' => 'Presione Enter para agregar etiquetas',
    'recurring_helper' => 'Hacer que esta transacción se repita automáticamente',
    'next_occurrence_helper' => '¿Cuándo debe crearse la próxima ocurrencia?',
    'category_filtered_by_type' => 'Solo se muestran categorías que coinciden con el tipo de transacción',
    'select_type_first' => 'Selecciona el tipo de transacción primero',
    'no_category_for_transfer' => 'Las transferencias no usan categorías',
    'select_category_for_type' => 'Selecciona una categoría de :type',

    // Stats widget
    'total_transactions' => 'Total de Transacciones',
    'all_transactions' => 'Todas las transacciones',
    'total_income' => 'Ingresos Totales',
    'money_received' => 'Dinero recibido',
    'total_expenses' => 'Gastos Totales',
    'money_spent' => 'Dinero gastado',
    'net_balance' => 'Balance Neto',
    'transaction_flow' => 'Flujo de Transacciones',
    'income_minus_expenses' => 'Ingresos menos gastos',
    'income_minus_expenses_note' => 'Ingresos menos gastos (excluye saldos iniciales)',
    'positive_balance' => 'Balance positivo',
    'negative_balance' => 'Balance negativo',

    // Transaction types for translation tests
    'types' => [
        'income' => 'Ingreso',
        'expense' => 'Gasto',
        'transfer' => 'Transferencia',
    ],

    // Count for pluralization tests
    'count' => '{0} no hay transacciones|{1} 1 transacción|[2,*] :count transacciones',
];
