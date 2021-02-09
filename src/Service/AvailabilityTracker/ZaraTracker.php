<?php

namespace App\Service\AvailabilityTracker;

use App\Service\Helpers\CrawlerHelper;

class ZaraTracker implements AvailabilityTrackerInterface
{
    const DOMAIN = 'zara.com';

    /** @var CrawlerHelper */
    private $crawlerHelper;

    public function __construct(CrawlerHelper $crawlerHelper)
    {
        $this->crawlerHelper = $crawlerHelper;
    }

    public function getDomain(): string
    {
        return self::DOMAIN;
    }

    public function getColors(string $link): array
    {
        $productData = $this->getProductData($link);
        $colors = [];

        foreach ($productData['colors'] as $colorData) {
            $colors[] = $colorData['name'];
        }

        if (empty($colors)) {
            throw new \RuntimeException('Invalid link');
        }

        return $colors;
    }

    public function getSizes(string $link, string $color): array
    {
        $html = file_get_contents($link);
        $productJson = $this->crawlerHelper->getSubstringBetweenTwoSubstrings($html, 'product: ', 'originalProductId:');
        $productJson = rtrim(rtrim($productJson), ',');
        $productData = json_decode($productJson, true);

        $sizes = [];

        foreach ($productData['colors'] as $colorData) {

            if ($colorData['name'] !== $color) {
                continue;
            }

            foreach ($colorData['sizes'] as $sizeData) {
                $sizes[] = $sizeData['name'];
            }
        }

        if (empty($sizes)) {
            throw new \RuntimeException('Invalid link');
        }

        return $sizes;
    }

    private function getProductData(string $link): array
    {
        $html = file_get_contents($link);
        $productJson = $this->crawlerHelper->getSubstringBetweenTwoSubstrings($html, 'product: ', 'originalProductId:');
        $productJson = rtrim(rtrim($productJson), ',');
        $productData = json_decode($productJson, true);

        if (!$productData) {
            throw new \RuntimeException('Invalid link');
        }

        return $productData;
    }
}