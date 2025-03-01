<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelOrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $travelOrder;
    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct($travelOrder, $status)
    {
        $this->travelOrder = $travelOrder;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->status === 'aprovado'
            ? 'Seu pedido de viagem foi aprovado!'
            : 'Seu pedido de viagem foi cancelado.';

        $message = $this->status === 'aprovado'
            ? 'Seu pedido de viagem para ' . $this->travelOrder->destination . ' foi aprovado.'
            : 'Seu pedido de viagem para ' . $this->travelOrder->destination . ' foi cancelado.';
        
        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->action('Notification Action', url('/travel-orders/' . $this->travelOrder->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
