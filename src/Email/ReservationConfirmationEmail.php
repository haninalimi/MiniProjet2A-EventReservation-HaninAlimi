<?php

namespace App\Email;

use App\Entity\Reservation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class ReservationConfirmationEmail extends TemplatedEmail
{
    public function __construct(Reservation $reservation)
    {
        parent::__construct();

        $this
            ->from(new Address(
                $_ENV['MAILER_FROM'] ?? 'noreply@eventapp.dev',
                $_ENV['MAILER_FROM_NAME'] ?? 'EventApp'
            ))
            ->to(new Address($reservation->getEmail(), $reservation->getName()))
            ->subject(' Réservation confirmée — ' . $reservation->getEvent()->getTitle())
            ->htmlTemplate('emails/reservation_confirmation.html.twig')
            ->context([
                'reservation' => $reservation,
                'event'       => $reservation->getEvent(),
            ]);
    }
}