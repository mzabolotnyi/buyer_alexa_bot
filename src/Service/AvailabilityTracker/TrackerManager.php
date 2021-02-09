<?php

namespace App\Service\AvailabilityTracker;

use App\Entity\Conversation;

class TrackerManager
{
    /** @var AvailabilityTrackerInterface[] */
    private $trackers;

    public function __construct(iterable $trackers)
    {
        $this->trackers = $trackers;
    }

    public function getTracker($link): AvailabilityTrackerInterface
    {
        foreach ($this->trackers as $tracker) {
            if (strpos($link, 'zara.com') !== false) {
                return $tracker;
            }
        }

        throw new \RuntimeException("Could not find tracker: $link");
    }
}