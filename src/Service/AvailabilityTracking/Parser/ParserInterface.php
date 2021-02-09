<?php

namespace App\Service\AvailabilityTracking\Parser;

interface ParserInterface
{
    public function getDomain(): string;
    public function getColors(string $link): array;
    public function getSizes(string $link, string $color): array;
    public function checkAvailability(string $link, string $color, string $size): bool;
}