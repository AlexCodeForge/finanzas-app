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
    'no_wallets_title' => 'No Hay Billeteras Disponibles',
    'no_wallets_message' => 'Necesitas crear al menos una billetera antes de poder crear transacciones. Haz clic en el botón de abajo para crear tu primera billetera.',

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
    'total_income' => 'Total de Ingresos',
    'money_received' => 'Dinero recibido',
    'total_expenses' => 'Total de Gastos',
    'money_spent' => 'Dinero gastado',
    'net_balance' => 'Balance Neto',
    'income_minus_expenses' => 'Ingresos menos gastos',
    'positive_balance' => 'Balance positivo',
    'negative_balance' => 'Balance negativo',
];
