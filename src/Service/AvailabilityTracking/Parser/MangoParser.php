<?php

namespace App\Service\AvailabilityTracking\Parser;

class MangoParser implements ParserInterface
{
    /** @var ParserHelper */
    private $parserHelper;

    public function __construct(ParserHelper $parserHelper)
    {
        $this->parserHelper = $parserHelper;
    }

    public function supports(string $link): bool
    {
        $supportedDomains = ['shop.mango.com', 'mangooutlet.com'];

        foreach ($supportedDomains as $domain) {
            if (strpos($link, $domain) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getColors(string $link): array
    {
        return ['-'];
    }

    public function getSizes(string $link, string $color): array
    {
        $productData = $this->getProductData($link);
        $sizesAvailability = explode(',', $productData['sizeAvailability']);
        $sizesNoAvailability = explode(',', $productData['sizeNoAvailability']);

        //merge all sizes
        $sizesMerged = array_merge($sizesAvailability, $sizesNoAvailability);

        //'ninguno' mean nothing - remove it
        $sizes = array_diff($sizesMerged, ['ninguno']);

        if (empty($sizes)) {
            throw new \RuntimeException('Invalid link');
        }

        return $sizes;
    }

    public function checkAvailability(string $link, string $color, string $size): bool
    {
        $productData = $this->getProductData($link);
        $sizesAvailability = explode(',', $productData['sizeAvailability']);

        return in_array($size, $sizesAvailability);
    }

    private function getProductData(string $link): array
    {
        try {

            $html = file_get_contents($link);
            $productJson = $this->parserHelper->getSubstringBetweenTwoSubstrings($html, 'var dataLayerV2Json = ', '</script>');
            $productJson = rtrim(rtrim($productJson), ';');
            $productData = json_decode($productJson, true);

            if (!$productData) {
                throw new \RuntimeException('Could not parse');
            }

            return $productData['ecommerce']['detail']['products']['0'];

        } catch (\Throwable $e) {
            throw new \RuntimeException("Invalid link: {$e->getMessage()}");
        }
    }
}