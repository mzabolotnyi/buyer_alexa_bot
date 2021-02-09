<?php

namespace App\Service\AvailabilityTracker;

interface AvailabilityTrackerInterface
{
    public function getDomain(): string;
    public function getColors(string $link): array;
    public function getSizes(string $link, string $color): array;
}