<div class="p-6">
    <div class="space-y-4">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <x-heroicon-o-information-circle class="w-8 h-8 text-blue-500" />
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    About Recurring Transaction Instances
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Understanding how recurring transactions work
                </p>
            </div>
        </div>

        <div class="prose prose-sm dark:prose-invert max-w-none">
            <p>
                <strong>Recurring Transaction Instances</strong> are automatically generated transactions based on the
                recurring settings of the parent transaction.
            </p>

            <ul>
                <li><strong>Parent Transaction:</strong> The original transaction with recurring settings enabled</li>
                <li><strong>Child Instances:</strong> Automatically created transactions that inherit the parent's
                    details</li>
                <li><strong>Frequency:</strong> How often new instances are created (daily, weekly, monthly, etc.)</li>
            </ul>

            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                    <x-heroicon-o-light-bulb class="w-4 h-4 inline mr-1" />
                    How it works:
                </h4>
                <ol class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <li>1. Create a transaction and enable recurring</li>
                    <li>2. Set the frequency (daily, weekly, monthly, etc.)</li>
                    <li>3. The system automatically creates new instances</li>
                    <li>4. Each instance can be edited or deleted independently</li>
                </ol>
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                <h4 class="text-sm font-medium text-amber-900 dark:text-amber-100 mb-2">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline mr-1" />
                    Important Notes:
                </h4>
                <ul class="text-sm text-amber-800 dark:text-amber-200 space-y-1">
                    <li>• Deleting the parent transaction will not delete existing instances</li>
                    <li>• Editing the parent transaction will not affect existing instances</li>
                    <li>• Each instance maintains its own wallet balance impact</li>
                </ul>
            </div>
        </div>
    </div>
</div>
