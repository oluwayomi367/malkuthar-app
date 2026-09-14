<?php

namespace App\Exceptions;

use RuntimeException;

class ServiceBookingException extends RuntimeException
{
    public static function slotUnavailable(string $serviceName, string $datetime): self
    {
        return new self("Le créneau demandé pour « {$serviceName} » ({$datetime}) n'est plus disponible.");
    }

    public static function outsideSchedule(string $serviceName, string $datetime): self
    {
        return new self("« {$serviceName} » n'est pas disponible à la date/heure demandée ({$datetime}).");
    }
}
