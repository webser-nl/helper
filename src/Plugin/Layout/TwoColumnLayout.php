<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Plugin\Layout;

/**
 * Configurable two column layout.
 *
 * @internal
 */
class TwoColumnLayout extends WebserLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions(): array {
    return [
      '6/6' => '6/6 (Equal)',
      '8/4' => '8/4 (Wide left)',
      '7/5' => '7/5',
      '5/7' => '5/7',
      '4/8' => '4/8 (Wide right)',
      '9/3' => '9/3 (Very wide left)',
      '3/9' => '3/9 (Very wide right)',
    ];
  }

}
