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
        $conversation = new Conversation();
        $conversation->setChatId($chatId);
        $conversation->setType($type);
        $conversation->setStep(1);
        $this->em->persist($conversation);
        $this->em->flush();

        return $conversation;
    }

    public function finish($id): void
    {
        $conversation = $this->conversationRepository->find($id);

        if ($conversation !== null) {
            $this->em->remove($conversation);
        }
    }

    public function find($id): ?Conversation
    {
        return $this->conversationRepository->find($id);
    }
}