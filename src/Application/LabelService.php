<?php

declare(strict_types=1);

namespace App\Application;

use App\Barcode\BarcodeRegistry;
use App\Barcode\BarcodeValidator;
use App\Database\LabelRepository;
use App\Label\Label;
use App\Label\LabelGenerator;
use App\Label\MediaType;
use App\Label\ValidatorFactory;
use App\Profile\MediaTypeProfile;
use App\Profile\Profile;
use App\Support\ValidationResult;

/**
 * Application service for label planning (preview), validation and creation.
 *
 * This is the single place where batch generation + validation + duplicate
 * handling live, so the web UI, the API and the CLI all share one
 * implementation.
 */
final class LabelService
{
    /**
     * @param array<string, Profile> $profiles
     */
    public function __construct(
        private readonly array $profiles,
        private readonly ValidatorFactory $validatorFactory,
        private readonly LabelGenerator $generator,
        private readonly LabelRepository $repository,
        private readonly BarcodeRegistry $barcodes,
        private readonly BarcodeValidator $barcodeValidator,
    ) {
    }

    /**
     * @return array<string, Profile>
     */
    public function profiles(): array
    {
        return $this->profiles;
    }

    /**
     * @return string[]
     */
    public function profileNames(): array
    {
        return array_keys($this->profiles);
    }

    public function profile(string $name): Profile
    {
        return $this->profiles[$name]
            ?? throw new \InvalidArgumentException(sprintf('Unbekanntes Profil "%s".', $name));
    }

    public function mediaTypeProfile(Profile $profile, MediaType $type): MediaTypeProfile
    {
        return $profile->mediaType($type)
            ?? throw new \InvalidArgumentException(sprintf('Medientyp "%s" ist im Profil nicht konfiguriert.', $type->value));
    }

    /**
     * @return array{profile: Profile, mediaTypeProfile: MediaTypeProfile, labels: Label[], results: ValidationResult[], duplicates: string[]}
     */
    public function planBatch(BatchRequest $request): array
    {
        $profile = $this->profile($request->profileName);
        $mediaTypeProfile = $this->mediaTypeProfile($profile, $request->mediaType);

        $labels = $this->generator->generate(
            $mediaTypeProfile,
            $request->start,
            $request->count,
            $request->prefix,
            $request->mediaId,
            $request->notes,
            $request->location,
        );

        $validator = $this->validatorFactory->forMediaType($mediaTypeProfile);

        $results = [];
        foreach ($labels as $label) {
            $result = $validator->validate($label);
            $pattern = $this->barcodes->generator($profile->barcode->type)
                ->generate($label->labelCode, $profile->barcode);
            $result = $result->merge($this->barcodeValidator->validate(
                $label->labelCode,
                $pattern,
                $profile->barcode,
                $profile->labelWidthMm,
                $profile->labelHeightMm,
            ));
            $results[] = $result;
        }

        $codes = array_map(static fn (Label $label): string => $label->labelCode, $labels);
        $duplicates = $this->repository->findDuplicates($codes);

        return [
            'profile' => $profile,
            'mediaTypeProfile' => $mediaTypeProfile,
            'labels' => $labels,
            'results' => $results,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * @return array{created: Label[], skipped: string[], errors: string[], batchId: ?int}
     */
    public function createBatch(BatchRequest $request): array
    {
        $plan = $this->planBatch($request);
        $labels = $plan['labels'];
        $duplicates = $plan['duplicates'];

        $errors = [];
        foreach ($plan['results'] as $i => $result) {
            foreach ($result->errors() as $error) {
                $errors[] = $labels[$i]->labelCode . ': ' . $error;
            }
        }

        if ($errors !== []) {
            return ['created' => [], 'skipped' => [], 'errors' => $errors, 'batchId' => null];
        }

        $toCreate = [];
        $skipped = [];
        foreach ($labels as $label) {
            if (in_array($label->labelCode, $duplicates, true)) {
                if ($request->duplicateAction === 'replace') {
                    $this->repository->deleteByCode($label->labelCode);
                    $toCreate[] = $label;
                } else {
                    $skipped[] = $label->labelCode;
                }
            } else {
                $toCreate[] = $label;
            }
        }

        $batchId = $this->repository->nextBatchId();
        $created = $toCreate === [] ? [] : $this->repository->saveMany($toCreate, $batchId);

        return ['created' => $created, 'skipped' => $skipped, 'errors' => [], 'batchId' => $batchId];
    }

    /**
     * Validate an already-constructed label (used by CLI/API single-label flows).
     */
    public function validateLabel(Label $label, string $profileName): ValidationResult
    {
        $profile = $this->profile($profileName);
        $mediaTypeProfile = $this->mediaTypeProfile($profile, $label->mediaType);

        $result = $this->validatorFactory->forMediaType($mediaTypeProfile)->validate($label);
        $pattern = $this->barcodes->generator($profile->barcode->type)
            ->generate($label->labelCode, $profile->barcode);

        return $result->merge($this->barcodeValidator->validate(
            $label->labelCode,
            $pattern,
            $profile->barcode,
            $profile->labelWidthMm,
            $profile->labelHeightMm,
        ));
    }

    /**
     * Create a single already-constructed label with duplicate handling.
     *
     * @return array{created: Label[], skipped: string[], errors: string[]}
     */
    public function createLabel(Label $label, string $profileName, string $duplicateAction = 'skip'): array
    {
        $result = $this->validateLabel($label, $profileName);

        $errors = [];
        foreach ($result->errors() as $error) {
            $errors[] = $label->labelCode . ': ' . $error;
        }

        if ($errors !== []) {
            return ['created' => [], 'skipped' => [], 'errors' => $errors];
        }

        if ($this->repository->exists($label->labelCode)) {
            if ($duplicateAction === 'replace') {
                $this->repository->deleteByCode($label->labelCode);
            } else {
                return ['created' => [], 'skipped' => [$label->labelCode], 'errors' => []];
            }
        }

        return ['created' => [$this->repository->save($label)], 'skipped' => [], 'errors' => []];
    }
}
