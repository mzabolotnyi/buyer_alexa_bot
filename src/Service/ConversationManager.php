<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ConversationManager
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ConversationRepository */
    private $conversationRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->conversationRepository = $em->getRepository(Conversation::class);
    }

    public function start(string $chatId, string $type): Conversation
    {
        $conversation = $this->conversationRepository->findOneByChatId($chatId);

        if ($conversation === null) {
            $conversation = new Conversation();
            $conversation->setChatId($chatId);
            $this->em->persist($conversation);
        }

        $conversation->setType($type);
        $conversation->setStep(1);

        return $conversation;
    }

    public function finish(string $chatId): void
    {
        $conversation = $this->conversationRepository->findOneByChatId($chatId);

        if ($conversation !== null) {
            $this->em->remove($conversation);
        }
    }

    public function current(string $chatId): ?Conversation
    {
        return $this->conversationRepository->findOneByChatId($chatId);
    }
}