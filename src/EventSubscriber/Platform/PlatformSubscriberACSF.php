<?php

namespace Acquia\Console\Acsf\EventSubscriber\Platform;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use EclipseGc\CommonConsole\CommonConsoleEvents;
use EclipseGc\CommonConsole\Event\GetPlatformTypeEvent;
use EclipseGc\CommonConsole\Event\GetPlatformTypesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PlatformSubscriberACSF.
 *
 * @package Acquia\Console\Acsf\EventSubscriber\Platform
 */
class PlatformSubscriberACSF implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CommonConsoleEvents::GET_PLATFORM_TYPES] = 'onGetPlatformTypes';
    $events[CommonConsoleEvents::GET_PLATFORM_TYPE] = 'onGetPlatformType';
    return $events;
  }

  /**
   * Expose available platform(s) to CommonConsole.
   *
   * @param \EclipseGc\CommonConsole\Event\GetPlatformTypesEvent $event
   *   The get platform TYPES event.
   */
  public function onGetPlatformTypes(GetPlatformTypesEvent $event) {
    $event->addPlatformType(ACSFPlatform::getPlatformId());
  }

  /**
   * Add class and/or factory data for an available platform.
   *
   * @param \EclipseGc\CommonConsole\Event\GetPlatformTypeEvent $event
   *   The get platform TYPE event.
   *
   * @throws \Exception
   */
  public function onGetPlatformType(GetPlatformTypeEvent $event) {
    if ($event->getPlatformType() === ACSFPlatform::getPlatformId()) {
      $event->addClass(ACSFPlatform::class);
      $event->stopPropagation();
    }
  }

}
