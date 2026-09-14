<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forProduct(string $productName, int $requested, int $available): self
    {
        return new self(sprintf(
            'Stock insuffisant pour "%s" : demandé %d, disponible %d.',
            $productName,
            $requested,
            $available
        ));
    }
}
