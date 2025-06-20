<?php

namespace App\Notifications;

use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowBalanceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Wallet $wallet,
        public float $threshold = 100.00
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.low_balance_subject'))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('notifications.low_balance_message', [
                'wallet' => $this->wallet->name,
                'balance' => '$' . number_format($this->wallet->balance, 2),
                'threshold' => '$' . number_format($this->threshold, 2),
            ]))
            ->action(__('notifications.view_wallet'), url('/finances/wallets/' . $this->wallet->id . '/edit'))
            ->line(__('notifications.consider_adding_funds'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notifications.low_balance_alert'),
            'message' => __('notifications.low_balance_message', [
                'wallet' => $this->wallet->name,
                'balance' => '$' . number_format($this->wallet->balance, 2),
                'threshold' => '$' . number_format($this->threshold, 2),
            ]),
            'wallet_id' => $this->wallet->id,
            'wallet_name' => $this->wallet->name,
            'current_balance' => $this->wallet->balance,
            'threshold' => $this->threshold,
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'warning',
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
