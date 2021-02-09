<?php

namespace App\Service\AvailabilityTracking;

use App\Entity\AvailabilityTracking\Tracking;
use App\Entity\Conversation;
use App\Service\AvailabilityTracking\Parser\ParserInterface;
use Doctrine\ORM\EntityManagerInterface;

class TrackingManager
{
    /** @var ParserInterface[] */
    private $parsers;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(iterable $parsers, EntityManagerInterface $em)
    {
        $this->parsers = $parsers;
        $this->em = $em;
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
        $tracking = new Tracking();
        $tracking->setChatId($conversation->getChatId())
            ->setLink($conversation->getParam('link'))
            ->setColor($conversation->getParam('color'))
            ->setSize($conversation->getParam('size'));

        $this->em->persist($tracking);

        return $tracking;
    }

    private function getParser($link): ParserInterface
    {
        foreach ($this->parsers as $tracker) {
            if (strpos($link, $tracker->getDomain()) !== false) {
                return $tracker;
            }
        }

        throw new \RuntimeException("Could not find parser: $link");
    }
}