<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Plugin\Layout;

/**
 * Configurable one column layout.
 *
 * @internal
 */
class OneColumnLayout extends WebserLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions(): array {
    return [
      '12' => '12 (Full width)',
      '10/1' => '10 + offset 1 (Centered)',
      '10/2' => '10 + offset 2 (Centered wide)',
      '8/2' => '8 + offset 2 (Centered)',
      '6/3' => '6 + offset 3 (Centered narrow)',
    ];
  }

}
