<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Wallet;
use App\Models\Transaction;
use Carbon\Carbon;

class DashboardTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get test user
        $user = User::firstOrCreate([
            'email' => 'test@example.com'
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'currency' => 'USD',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'theme' => 'light'
        ]);

        // Create categories
        $categories = [
            ['name' => 'Food & Dining', 'color' => '#EF4444', 'icon' => 'heroicon-o-cake'],
            ['name' => 'Transportation', 'color' => '#F97316', 'icon' => 'heroicon-o-truck'],
            ['name' => 'Entertainment', 'color' => '#F59E0B', 'icon' => 'heroicon-o-film'],
            ['name' => 'Salary', 'color' => '#10B981', 'icon' => 'heroicon-o-banknotes'],
            ['name' => 'Shopping', 'color' => '#8B5CF6', 'icon' => 'heroicon-o-shopping-bag'],
            ['name' => 'Utilities', 'color' => '#06B6D4', 'icon' => 'heroicon-o-bolt'],
            ['name' => 'Healthcare', 'color' => '#EC4899', 'icon' => 'heroicon-o-heart'],
            ['name' => 'Freelance', 'color' => '#22C55E', 'icon' => 'heroicon-o-computer-desktop'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate([
                'name' => $categoryData['name'],
                'user_id' => $user->id
            ], array_merge($categoryData, ['user_id' => $user->id]));
        }

        // Create wallets
        $wallets = [
            ['name' => 'Main Checking', 'type' => 'bank_account', 'currency' => 'USD', 'balance' => 2500.00, 'initial_balance' => 2000.00],
            ['name' => 'Savings Account', 'type' => 'savings', 'currency' => 'USD', 'balance' => 5000.00, 'initial_balance' => 4500.00],
            ['name' => 'Cash Wallet', 'type' => 'cash', 'currency' => 'USD', 'balance' => 200.00, 'initial_balance' => 300.00],
            ['name' => 'Credit Card', 'type' => 'credit_card', 'currency' => 'USD', 'balance' => -850.00, 'initial_balance' => 0.00],
        ];

        foreach ($wallets as $walletData) {
            Wallet::firstOrCreate([
                'name' => $walletData['name'],
                'user_id' => $user->id
            ], array_merge($walletData, ['user_id' => $user->id]));
        }

        // Get created categories and wallets
        $userCategories = Category::where('user_id', $user->id)->get();
        $userWallets = Wallet::where('user_id', $user->id)->get();

        // Create transactions for the last 6 months
        $transactions = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);

            // Monthly salary (income)
            $salaryCategory = $userCategories->where('name', 'Salary')->first();
            $mainWallet = $userWallets->where('name', 'Main Checking')->first();

            if ($salaryCategory && $mainWallet) {
                $transactions[] = [
                    'user_id' => $user->id,
                    'category_id' => $salaryCategory->id,
                    'wallet_id' => $mainWallet->id,
                    'type' => 'income',
                    'amount' => 4500.00,
                    'description' => 'Monthly Salary',
                    'date' => $month->copy()->day(1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Freelance income (random)
            $freelanceCategory = $userCategories->where('name', 'Freelance')->first();
            if ($freelanceCategory && $mainWallet && rand(0, 1)) {
                $transactions[] = [
                    'user_id' => $user->id,
                    'category_id' => $freelanceCategory->id,
                    'wallet_id' => $mainWallet->id,
                    'type' => 'income',
                    'amount' => rand(500, 1500),
                    'description' => 'Freelance Project',
                    'date' => $month->copy()->day(rand(5, 25)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Monthly expenses
            $expenseCategories = $userCategories->whereNotIn('name', ['Salary', 'Freelance']);
            foreach ($expenseCategories as $category) {
                $expenseWallet = $userWallets->random();

                // Random number of transactions per category per month
                $transactionCount = rand(1, 4);
                for ($j = 0; $j < $transactionCount; $j++) {
                    $amount = match ($category->name) {
                        'Food & Dining' => rand(20, 80),
                        'Transportation' => rand(30, 120),
                        'Entertainment' => rand(15, 60),
                        'Shopping' => rand(25, 200),
                        'Utilities' => rand(80, 150),
                        'Healthcare' => rand(50, 300),
                        default => rand(20, 100),
                    };

                    $transactions[] = [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'wallet_id' => $expenseWallet->id,
                        'type' => 'expense',
                        'amount' => $amount,
                        'description' => $category->name . ' expense',
                        'date' => $month->copy()->day(rand(1, 28)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Insert all transactions
        Transaction::insert($transactions);

        $this->command->info('Dashboard test data created successfully!');
        $this->command->info('User: test@example.com / password');
        $this->command->info('Categories: ' . $userCategories->count());
        $this->command->info('Wallets: ' . $userWallets->count());
        $this->command->info('Transactions: ' . count($transactions));
    }
}
