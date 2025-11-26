<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Plugin\Layout;

/**
 * Configurable three column layout.
 *
 * @internal
 */
class ThreeColumnLayout extends WebserLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions(): array {
    return [
      '4/4/4' => '4/4/4 (Equal)',
      '3/6/3' => '3/6/3 (Wide center)',
      '6/3/3' => '6/3/3 (Wide left)',
      '3/3/6' => '3/3/6 (Wide right)',
      '2/8/2' => '2/8/2 (Very wide center)',
    ];
  }

}
