<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction
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
        $typeLabel = __('transactions.' . $this->transaction->type);

        return (new MailMessage)
            ->subject(__('notifications.transaction_created_subject'))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('notifications.transaction_created_message', [
                'type' => $typeLabel,
                'amount' => $this->transaction->formatted_amount,
                'description' => $this->transaction->description,
            ]))
            ->action(__('notifications.view_transaction'), url('/finances/transactions/' . $this->transaction->id . '/edit'))
            ->line(__('notifications.thanks'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $typeLabel = __('transactions.' . $this->transaction->type);

        return [
            'title' => __('notifications.transaction_created'),
            'message' => __('notifications.transaction_created_message', [
                'type' => $typeLabel,
                'amount' => $this->transaction->formatted_amount,
                'description' => $this->transaction->description,
            ]),
            'transaction_id' => $this->transaction->id,
            'transaction_type' => $this->transaction->type,
            'amount' => $this->transaction->amount,
            'icon' => match ($this->transaction->type) {
                'income' => 'heroicon-o-arrow-trending-up',
                'expense' => 'heroicon-o-arrow-trending-down',
                'transfer' => 'heroicon-o-arrow-right-left',
                default => 'heroicon-o-banknotes',
            },
            'color' => match ($this->transaction->type) {
                'income' => 'success',
                'expense' => 'danger',
                'transfer' => 'info',
                default => 'gray',
            },
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
