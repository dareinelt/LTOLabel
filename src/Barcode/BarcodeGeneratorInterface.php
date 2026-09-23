<?php

declare(strict_types=1);

namespace App\Barcode;

/**
 * Exchangeable barcode generation component.
 *
 * New symbologies can be added without touching label or PDF code by
 * implementing this interface and registering the implementation.
 */
interface BarcodeGeneratorInterface
{
    public function supports(string $symbology): bool;

    /**
     * Encode the given data into a renderable pattern.
     *
     * @throws \InvalidArgumentException if the data cannot be encoded.
     */
    public function generate(string $data, BarcodeSpec $spec): BarcodePattern;
}
