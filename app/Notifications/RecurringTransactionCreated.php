<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecurringTransactionCreated extends Notification implements ShouldQueue
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
        $parentTransaction = $this->transaction->parentTransaction;
        $nextOccurrence = $parentTransaction?->next_occurrence;

        return (new MailMessage)
            ->subject(__('notifications.recurring_transaction_subject'))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('notifications.recurring_transaction_message', [
                'type' => $typeLabel,
                'amount' => $this->transaction->formatted_amount,
                'description' => $this->transaction->description,
            ]))
            ->when($nextOccurrence, function ($mail) use ($nextOccurrence) {
                return $mail->line(__('notifications.next_occurrence', [
                    'date' => $nextOccurrence->format('M j, Y')
                ]));
            })
            ->action(__('notifications.view_transaction'), url('/finances/transactions/' . $this->transaction->id . '/edit'))
            ->line(__('notifications.thanks'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $typeLabel = __('transactions.' . $this->transaction->type);
        $parentTransaction = $this->transaction->parentTransaction;

        return [
            'title' => __('notifications.recurring_transaction_created'),
            'message' => __('notifications.recurring_transaction_message', [
                'type' => $typeLabel,
                'amount' => $this->transaction->formatted_amount,
                'description' => $this->transaction->description,
            ]),
            'transaction_id' => $this->transaction->id,
            'parent_transaction_id' => $this->transaction->parent_transaction_id,
            'transaction_type' => $this->transaction->type,
            'amount' => $this->transaction->amount,
            'next_occurrence' => $parentTransaction?->next_occurrence,
            'icon' => 'heroicon-o-arrow-path',
            'color' => 'info',
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
