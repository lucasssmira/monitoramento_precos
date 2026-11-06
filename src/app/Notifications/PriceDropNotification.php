<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceDropNotification extends Notification implements ShouldQueue
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
        $currencyTarget = number_format((float) $this->alert->target_price, 2, ',', '.');

        $mail = (new MailMessage)
            ->subject("Alerta de Preço: {$name} atingiu R$ {$currencyTriggered}")
            ->greeting("Olá, {$recipientName}!")
            ->line("Boas notícias! O produto \"{$name}\" atingiu ou ficou abaixo do seu preço alvo.")
            ->line("Preço promocional à vista: R$ {$currencyTriggered}")
            ->line("Seu preço alvo definido: R$ {$currencyTarget}");

        if ($this->alert->previous_price !== null) {
            $currencyPrevious = number_format((float) $this->alert->previous_price, 2, ',', '.');
            $mail->line("Preço registrado anteriormente: R$ {$currencyPrevious}");
        }

        if ($this->alert->installment_price !== null) {
            $currencyInstallment = number_format((float) $this->alert->installment_price, 2, ',', '.');
            $mail->line("Preço a prazo: R$ {$currencyInstallment}");
        }

        return $mail
            ->action('Ver Oferta na Loja', $product->url)
            ->line('Aproveite antes que a promoção encerre ou o estoque acabe!');
    }
}
