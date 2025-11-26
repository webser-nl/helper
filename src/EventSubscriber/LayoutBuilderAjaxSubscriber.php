<?php

declare(strict_types=1);

namespace Drupal\webser_helper\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for Layout Builder AJAX enhancements.
 */
class LayoutBuilderAjaxSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onResponse', -100],
    ];
  }

  /**
   * Modifies AJAX responses for Layout Builder.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();

    if (!$response instanceof AjaxResponse) {
      return;
    }

    $request = $event->getRequest();
    $route = $request->attributes->get('_route');

    // Only modify Layout Builder routes.
    if (!$route || !str_starts_with($route, 'layout_builder.')) {
      return;
    }

    // Additional AJAX enhancements can be added here.
    // For example: auto-close dialogs, refresh specific regions, etc.
  }

}
