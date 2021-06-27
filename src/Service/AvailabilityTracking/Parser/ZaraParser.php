<?php

namespace App\Service\AvailabilityTracking\Parser;

class ZaraParser implements ParserInterface
{
    /** @var ParserHelper */
    private $parserHelper;

    public function __construct(ParserHelper $parserHelper)
    {
        $this->parserHelper = $parserHelper;
    }

    public function supports(string $link): bool
    {
        return strpos($link, 'zara.com') !== false;
    }

    public function getColors(string $link): array
    {
        $productData = $this->getProductData($link);
        $colors = [];

        foreach ($productData['product']['detail']['colors'] as $colorData) {
            $colors[$colorData['id']] = $colorData['name'];
        }

        if (empty($colors)) {
            throw new \RuntimeException('Invalid link');
        }

        return $colors;
    }

    public function getSizes(string $link, string $color): array
    {
        $productData = $this->getProductData($link);
        $sizes = [];

        foreach ($productData['product']['detail']['colors'] as $colorData) {

            if ($colorData['name'] !== $color) {
                continue;
            }

            foreach ($colorData['sizes'] as $sizeData) {
                $sizes[$sizeData['id']] = $sizeData['name'];
            }
        }

        if (empty($sizes)) {
            throw new \RuntimeException('Invalid link');
        }

        return $sizes;
    }

    public function checkAvailability(string $link, string $color, string $size): bool
    {
        $productData = $this->getProductData($link);
        $result = false;

        foreach ($productData['product']['detail']['colors'] as $colorData) {

            if ($colorData['name'] !== $color) {
                continue;
            }

            foreach ($colorData['sizes'] as $sizeData) {

                if ($sizeData['name'] !== $size) {
                    continue;
                }

                if ($sizeData['availability'] === 'in_stock') {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    private function getProductData(string $link): array
    {
        try {

            $html = file_get_contents($link);
            $productJson = $this->parserHelper->getSubstringBetweenTwoSubstrings($html, 'window.zara.viewPayload = ', ';</script>');
            $productJson = rtrim(rtrim($productJson), ',');
            $productData = json_decode($productJson, true);

            if (!$productData) {
                throw new \RuntimeException('Could not parse');
            }

            return $productData;

        } catch (\Throwable $e) {
            throw new \RuntimeException("Invalid link: {$e->getMessage()}");
        }
    }
}