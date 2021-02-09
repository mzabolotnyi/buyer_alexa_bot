<?php

namespace App\Command;

use App\Entity\AvailabilityTracking\Tracking;
use App\Repository\AvailabilityTracking\TrackingRepository;
use App\Service\AvailabilityTracking\TrackingManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telegram\Bot\Api;

class AvailabilityTrackingCommand extends Command
{
    const NAME = 'availability-tracking:run';

    /** @var Api */
    private $bot;

    /** @var EntityManagerInterface */
    private $em;

    /** @var TrackingManager */
    private $trackingManager;

    public function __construct(EntityManagerInterface $em, TrackingManager $trackingManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->trackingManager = $trackingManager;
        $this->bot = new Api(getenv('TELEGRAM_TOKEN'));
    }

    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Run availability tracking');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var TrackingRepository $trackingRepository */
        $trackingRepository = $this->em->getRepository(Tracking::class);

        foreach ($trackingRepository->findForTracking() as $tracking) {
            try {
                $this->runTracking($tracking);
                $this->em->flush();
            } catch (\Throwable $e) {
                //TODO add logging
            }
        }

        return 0;
    }

    private function runTracking(Tracking $tracking): void
    {
        $available = $this->trackingManager->checkAvailability($tracking);
        $tracking->setLastTrackedAt(new \DateTime());

        if ($available) {

            $params = [
                'chat_id' => $tracking->getChatId(),
                'text' => sprintf(
                    "AVAILABLE FOR PURCHASE!!!\n\nLink: %s\nColor:  %s\nSize:  %s",
                    $tracking->getLink(),
                    $tracking->getColor(),
                    $tracking->getSize()
                )
            ];

            $this->bot->sendMessage($params);

            if ($tracking->getSuccessfulCount() >= 10) {

                $this->em->remove($tracking);

                $params = [
                    'chat_id' => $tracking->getChatId(),
                    'text' => sprintf(
                        "Removed from tracking\n\nLink: %s\nColor:  %s\nSize:  %s",
                        $tracking->getLink(),
                        $tracking->getColor(),
                        $tracking->getSize()
                    )
                ];

                $this->bot->sendMessage($params);

            } else {
                $tracking->successfulCountIncrement();
            }
        }
    }
}
