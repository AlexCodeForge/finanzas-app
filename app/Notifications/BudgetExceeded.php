<?php

namespace App\Notifications;

use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetExceeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Category $category,
        public float $spent,
        public float $budget
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
        $percentage = ($this->spent / $this->budget) * 100;

        return (new MailMessage)
            ->subject(__('notifications.budget_exceeded_subject'))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('notifications.budget_exceeded_message', [
                'category' => $this->category->name,
                'spent' => '$' . number_format($this->spent, 2),
                'budget' => '$' . number_format($this->budget, 2),
                'percentage' => number_format($percentage, 1),
            ]))
            ->action(__('notifications.view_category'), url('/finances/categories/' . $this->category->id . '/edit'))
            ->line(__('notifications.consider_reviewing_spending'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $percentage = ($this->spent / $this->budget) * 100;

        return [
            'title' => __('notifications.budget_exceeded'),
            'message' => __('notifications.budget_exceeded_message', [
                'category' => $this->category->name,
                'spent' => '$' . number_format($this->spent, 2),
                'budget' => '$' . number_format($this->budget, 2),
                'percentage' => number_format($percentage, 1),
            ]),
            'category_id' => $this->category->id,
            'category_name' => $this->category->name,
            'spent_amount' => $this->spent,
            'budget_limit' => $this->budget,
            'percentage_over' => $percentage,
            'icon' => 'heroicon-o-exclamation-circle',
            'color' => 'danger',
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
