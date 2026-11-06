<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackInStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Alert $alert,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
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
        $recipientName = property_exists($notifiable, 'name') ? (string) $notifiable->name : 'Cliente';
        $product = $this->alert->product;
        $name = $product->nickname ?: ($product->title ?: $product->normalized_url);
        $currencyTriggered = number_format((float) $this->alert->triggered_price, 2, ',', '.');

        return (new MailMessage)
            ->subject("Produto de Volta ao Estoque: {$name}")
            ->greeting("Olá, {$recipientName}!")
            ->line("O produto \"{$name}\" que estava esgotado acaba de voltar a ficar disponível para compra.")
            ->line("Preço atual à vista: R$ {$currencyTriggered}")
            ->action('Acessar Loja e Comprar', $product->url)
            ->line('Recomendamos finalizar a compra com rapidez, pois as unidades podem se esgotar novamente.');
    }
}
