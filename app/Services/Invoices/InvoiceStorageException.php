<?php

namespace App\Services\Invoices;

use RuntimeException;

class InvoiceStorageException extends RuntimeException
{
    public function __construct(public readonly string $path)
    {
        parent::__construct('Cannot save the order invoice to private storage.');
    }
}
