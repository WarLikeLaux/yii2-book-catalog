<?php

declare(strict_types=1);

namespace app\domain\events;

/**
 * Маркерный интерфейс для событий, которые должны обрабатываться асинхронно.
 * Маппинг Event → Job выполняется в Infrastructure слое (EventToJobMapper).
 */
interface QueueableEvent extends DomainEvent
{
}
