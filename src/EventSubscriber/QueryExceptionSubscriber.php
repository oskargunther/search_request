<?php


namespace Search\EventSubscriber;


use Doctrine\ORM\Query\QueryException;
use Search\Exception\BadRequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class QueryExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => array(
                array('queryException', 10),
            )
        ];
    }

    public function queryException(ExceptionEvent $event)
    {
        if($event->getException() instanceof QueryException) {
            throw new BadRequestException();
        }
    }

}