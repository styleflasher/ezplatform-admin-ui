<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUi\Form\DataTransformer;

use eZ\Publish\API\Repository\SectionService;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;

/**
 * Transforms between a Sections ID and a domain specific object.
 */
class SectionsTransformer implements DataTransformerInterface
{
    /** @var SectionService */
    protected $sectionService;

    /**
     * @param SectionService $sectionService
     */
    public function __construct(SectionService $sectionService)
    {
        $this->sectionService = $sectionService;
    }

    /**
     * Transforms a domain specific Section objects into a string with comma separated Sections identifiers.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public function transform($value): ?string
    {
        /** TODO add sanity check is array of Location object? */
        if (!is_array($value) || empty($value)) {
            return null;
        }

        return implode(',', array_column($value, 'id'));
    }

    /**
     * Transforms a string with comma separated Sections identifiers into a domain specific Section objects.
     *
     * @param mixed $value
     *
     * @return array|null
     *
     * @throws TransformationFailedException
     */
    public function reverseTransform($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $value = explode(',', $value);

        try {
            return array_map([$this->sectionService, 'loadSection'], $value);
        } catch (NotFoundException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
