<?php

namespace App\Service\AvailabilityTracking;

use App\Entity\AvailabilityTracking\Tracking;
use App\Entity\Conversation;
use App\Repository\AvailabilityTracking\TrackingRepository;
use App\Service\AvailabilityTracking\Parser\ParserInterface;
use Doctrine\ORM\EntityManagerInterface;

class TrackingManager
{
    /** @var ParserInterface[] */
    private $parsers;

    /** @var EntityManagerInterface */
    private $em;

    /** @var TrackingRepository */
    private $trackingRepository;

    public function __construct(iterable $parsers, EntityManagerInterface $em)
    {
        $this->parsers = $parsers;
        $this->em = $em;
        $this->trackingRepository = $em->getRepository(Tracking::class);
    }

    public function getColors(Conversation $conversation): array
    {
        $link = $conversation->getParam('link');

        return $this->getParser($link)->getColors($link);
    }

    public function getSizes(Conversation $conversation): array
    {
        $link = $conversation->getParam('link');
        $color = $conversation->getParam('color');

        return $this->getParser($link)->getSizes($link, $color);
    }

    public function startTracking(Conversation $conversation): Tracking
    {
        $chatId = $conversation->getChatId();
        $link = $conversation->getParam('link');
        $color = $conversation->getParam('color');
        $size = $conversation->getParam('size');

        $tracking = $this->trackingRepository->findOneBy([
            'chatId' => $chatId,
            'link' => $link,
            'color' => $color,
            'size' => $size,
        ]);

        if ($tracking === null) {
            $tracking = new Tracking();
            $tracking->setChatId($chatId)
                ->setLink($link)
                ->setColor($color)
                ->setSize($size);
            $this->em->persist($tracking);
            $this->em->flush();
        }

        $tracking->setLastTrackedAt(null);
        $tracking->setSuccessfulCount(0);

        return $tracking;
    }

    public function finishTracking($id): void
    {
        $tracking = $this->trackingRepository->find($id);

        if ($tracking !== null) {
            $this->em->remove($tracking);
        }
    }

    public function checkAvailability(Tracking $tracking): bool
    {
        $link = $tracking->getLink();

        return $this->getParser($link)->checkAvailability($link, $tracking->getColor(), $tracking->getSize());
    }

    public function hasParser($link): bool
    {
        return $this->getParser($link) instanceof ParserInterface;
    }

    private function getParser($link): ?ParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($link)) {
                return $parser;
            }
        }

        throw new \RuntimeException("Could not find parser: $link");
    }
}