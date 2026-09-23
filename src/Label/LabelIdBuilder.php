<?php

declare(strict_types=1);

namespace App\Label;

use App\Profile\MediaTypeProfile;

/**
 * Builds a label code from a sequence number according to the media-type rules.
 *
 * DATA:     VOLSER (prefix + zero-padded number, volserLength chars) + media id
 *           e.g. prefix "ABC", number 1, volserLength 6, mediaId "L8" => "ABC001L8"
 * CLEANING: prefix + zero-padded number (sequenceLength) + suffix
 *           e.g. "CLN" + "001" + "L1" => "CLN001L1"
 */
final class LabelIdBuilder
{
    public function build(MediaTypeProfile $profile, int $number, string $userPrefix = '', ?string $mediaId = null): string
    {
        return $profile->type === MediaType::DATA
            ? $this->buildData($profile, $number, $userPrefix, $mediaId ?? $profile->defaultMediaId ?? 'L8')
            : $this->buildCleaning($profile, $number);
    }

    private function buildData(MediaTypeProfile $profile, int $number, string $prefix, string $mediaId): string
    {
        $pad = max(0, $profile->volserLength - strlen($prefix));
        $volser = $prefix . str_pad((string) $number, $pad, '0', STR_PAD_LEFT);

        return $volser . $mediaId;
    }

    private function buildCleaning(MediaTypeProfile $profile, int $number): string
    {
        return $profile->prefix
            . str_pad((string) $number, $profile->sequenceLength, '0', STR_PAD_LEFT)
            . ($profile->suffix ?? '');
    }
}
