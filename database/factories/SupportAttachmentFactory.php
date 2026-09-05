<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Anexa foto ou PDF ao chamado; state prende o arquivo a uma mensagem sem separar anexo e conversa.
*/

namespace Database\Factories;

use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportAttachment>
 */
class SupportAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = (string) fake()->randomElement(['jpg', 'png', 'pdf']);

        $mime = match ($extension) {
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            default => 'image/jpeg',
        };

        $name = (string) match ($extension) {
            'pdf' => 'comprovante-pagamento',
            default => fake()->randomElement(['foto-alianca', 'print-vitrine', 'gravacao-interna']),
        };

        return [
            'ticket_id' => SupportTicket::factory(),
            'message_id' => null,
            'original_name' => $name.'.'.$extension,
            // `local` é o default da migration; o model não declara vocabulário de disco.
            'disk' => 'local',
            'path' => 'support/tickets/'.fake()->uuid().'.'.$extension,
            'size_bytes' => fake()->numberBetween(20480, 2097152),
            'mime' => $mime,
        ];
    }

    /**
     * Anexo preso a uma mensagem do chamado.
     *
     * O vínculo é fechado em `afterMaking`, depois que `ticket_id` já virou id concreto — e não
     * dentro de um state. Atributo passado no `create()` entra como último state e sobrescreveria
     * o `ticket_id` decidido aqui, deixando o anexo num chamado e a mensagem em outro.
     */
    public function forMessage(?SupportMessage $message = null): static
    {
        return $this->afterMaking(function (SupportAttachment $attachment) use ($message): void {
            if ($message instanceof SupportMessage) {
                $attachment->ticket_id = $message->ticket_id;
                $attachment->message_id = $message->getKey();

                return;
            }

            $attachment->message_id = SupportMessage::factory()
                ->create(['ticket_id' => $attachment->ticket_id])
                ->getKey();
        });
    }
}
