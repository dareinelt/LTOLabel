<?php

declare(strict_types=1);

namespace App\Barcode;

/**
 * Registry of available barcode generators, selected by symbology identifier.
 */
final class BarcodeRegistry
{
    /** @var BarcodeGeneratorInterface[] */
    private array $generators = [];

    public function register(BarcodeGeneratorInterface $generator): void
    {
        $this->generators[] = $generator;
    }

    public function generator(string $symbology): BarcodeGeneratorInterface
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($symbology)) {
                return $generator;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Keine Barcode-Implementierung für Symbologie "%s" registriert.', $symbology)
        );
    }

    /**
     * @return string[]
     */
    public function supportedSymbologies(): array
    {
        return [Code39BarcodeGenerator::SYMBOLOGY];
    }
}
