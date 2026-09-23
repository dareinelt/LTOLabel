<?php

declare(strict_types=1);

namespace App\Barcode;

/**
 * Code 39 ("3 of 9", USS-39) barcode generator.
 *
 * This is the symbology mandated by the LTO Ultrium cartridge label
 * specification and therefore by the Dell EMC ML3 tape library.
 *
 * Each character is encoded as 9 elements (5 bars + 4 spaces) with exactly
 * three wide elements. Elements alternate bar/space starting with a bar. A
 * single narrow space (inter-character gap) separates adjacent characters.
 * The start and stop character is '*'.
 *
 * The patterns below are the standard USS-39 encodations where each of the
 * nine positions is '1' (wide) or '0' (narrow); position 0 is a bar, position
 * 1 a space, and so on.
 */
final class Code39BarcodeGenerator implements BarcodeGeneratorInterface
{
    public const SYMBOLOGY = 'CODE39';

    /** @var array<string, string> character => 9-bit pattern */
    private const PATTERNS = [
        '0' => '000110100', '1' => '100100001', '2' => '001100001',
        '3' => '101100000', '4' => '000110001', '5' => '100110000',
        '6' => '001110000', '7' => '000100101', '8' => '100100100',
        '9' => '001100100',
        'A' => '100001001', 'B' => '001001001', 'C' => '101001000',
        'D' => '000011001', 'E' => '100011000', 'F' => '001011000',
        'G' => '000001101', 'H' => '100001100', 'I' => '001001100',
        'J' => '000011100', 'K' => '100000011', 'L' => '001000011',
        'M' => '101000010', 'N' => '000010011', 'O' => '100010010',
        'P' => '001010010', 'Q' => '000000111', 'R' => '100000110',
        'S' => '001000110', 'T' => '000010110', 'U' => '110000001',
        'V' => '011000001', 'W' => '111000000', 'X' => '010010001',
        'Y' => '110010000', 'Z' => '011010000',
        '-' => '010000101', '.' => '110000100', ' ' => '011000100',
        '$' => '010101000', '/' => '010100010', '+' => '010001010',
        '%' => '000101010', '*' => '010010100',
    ];

    public function supports(string $symbology): bool
    {
        return strtoupper($symbology) === self::SYMBOLOGY;
    }

    public function generate(string $data, BarcodeSpec $spec): BarcodePattern
    {
        $symbols = str_split('*' . $data . '*');
        $runs = [];

        foreach ($symbols as $symbol) {
            $pattern = self::PATTERNS[$symbol] ?? null;
            if ($pattern === null) {
                throw new \InvalidArgumentException(
                    sprintf('Zeichen "%s" kann nicht als Code 39 kodiert werden.', $symbol)
                );
            }

            for ($pos = 0; $pos < 9; $pos++) {
                $isWide = $pattern[$pos] === '1';
                $isBar = ($pos % 2) === 0;
                $runs[] = [
                    'bar' => $isBar,
                    'width' => $isWide ? $spec->wideNarrowRatio : 1.0,
                ];
            }

            // Inter-character gap (narrow space). A trailing gap after the stop
            // character is harmless because it merges with the quiet zone.
            $runs[] = ['bar' => false, 'width' => 1.0];
        }

        return new BarcodePattern($data, self::SYMBOLOGY, $runs);
    }
}
