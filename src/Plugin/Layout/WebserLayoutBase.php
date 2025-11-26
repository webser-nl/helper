<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\Plugin\Layout\MultiWidthLayoutBase;

/**
 * Base class for Webser layouts with configurable options.
 *
 * Provides common configuration options for all Webser layouts:
 * - Column widths (Bootstrap grid)
 * - Container toggle
 * - Background colors
 * - Padding options
 * - Extra regions (top/bottom)
 * - Tab functionality
 * - CSS animations
 * - Border styling
 *
 * @internal
 */
abstract class WebserLayoutBase extends MultiWidthLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'extra_class' => '',
      'column_widths' => $this->getDefaultWidth(),
      'container' => TRUE,
      'padding' => 'both',
      'background_color' => 'transparent',
      'top_region' => FALSE,
      'bottom_region' => FALSE,
      'tabs_class' => '',
      'tabs_toggle' => FALSE,
      'animation' => 'none',
      'border_toggle' => 'none',
      'border_color' => '#ECECEC',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['extra_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra CSS classes'),
      '#default_value' => $this->configuration['extra_class'],
      '#description' => $this->t('Additional CSS classes for this section (space-separated).'),
      '#weight' => 1,
    ];

    $form['column_widths'] = [
      '#type' => 'select',
      '#title' => $this->t('Column widths'),
      '#default_value' => $this->configuration['column_widths'],
      '#options' => $this->getWidthOptions(),
      '#description' => $this->t('Bootstrap grid column widths (total width is 12).'),
      '#required' => TRUE,
      '#weight' => 2,
    ];

    $form['container'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap in container'),
      '#description' => $this->t('Wrap the grid in a Bootstrap container class.'),
      '#default_value' => $this->configuration['container'],
      '#weight' => 3,
    ];

    $form['background_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background color'),
      '#default_value' => $this->configuration['background_color'],
      '#options' => $this->getBackgroundColorOptions(),
      '#required' => TRUE,
      '#weight' => 4,
    ];

    $form['padding'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical padding'),
      '#default_value' => $this->configuration['padding'],
      '#options' => [
        'none' => $this->t('None'),
        'both' => $this->t('Top and bottom'),
        'top' => $this->t('Top only'),
        'bottom' => $this->t('Bottom only'),
      ],
      '#required' => TRUE,
      '#weight' => 5,
    ];

    // Advanced options in collapsible fieldset.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced options'),
      '#open' => FALSE,
      '#weight' => 99,
    ];

    $form['advanced']['top_region'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show region on top'),
      '#description' => $this->t('Add an extra full-width region above the columns.'),
      '#default_value' => $this->configuration['top_region'],
    ];

    $form['advanced']['bottom_region'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show region at bottom'),
      '#description' => $this->t('Add an extra full-width region below the columns.'),
      '#default_value' => $this->configuration['bottom_region'],
    ];

    $form['advanced']['tabs_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tab navigation class'),
      '#description' => $this->t('Enter a unique class to enable tab navigation. Tabs are generated from blocks in the main/second region.'),
      '#default_value' => $this->configuration['tabs_class'],
    ];

    $form['advanced']['tabs_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Toggle tab content'),
      '#description' => $this->t('Hide inactive tab content (show/hide on click).'),
      '#default_value' => $this->configuration['tabs_toggle'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[advanced][tabs_class]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['advanced']['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Scroll animation'),
      '#description' => $this->t('Animate section when scrolling into view.'),
      '#default_value' => $this->configuration['animation'],
      '#options' => [
        'none' => $this->t('None'),
        'fade-in' => $this->t('Fade in'),
        'slide-in-left' => $this->t('Slide from left'),
        'slide-in-right' => $this->t('Slide from right'),
        'slide-in-up' => $this->t('Slide from bottom'),
        'scale-in' => $this->t('Scale in'),
      ],
    ];

    $form['advanced']['border_toggle'] = [
      '#type' => 'select',
      '#title' => $this->t('Border'),
      '#default_value' => $this->configuration['border_toggle'],
      '#options' => [
        'none' => $this->t('None'),
        'top' => $this->t('Top border'),
        'bottom' => $this->t('Bottom border'),
      ],
    ];

    $form['advanced']['border_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Border color'),
      '#default_value' => $this->configuration['border_color'],
      '#states' => [
        'invisible' => [
          ':input[name="layout_settings[advanced][border_toggle]"]' => ['value' => 'none'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['extra_class'] = $form_state->getValue('extra_class');
    $this->configuration['column_widths'] = $form_state->getValue('column_widths');
    $this->configuration['container'] = (bool) $form_state->getValue('container');
    $this->configuration['background_color'] = $form_state->getValue('background_color');
    $this->configuration['padding'] = $form_state->getValue('padding');

    $advanced = $form_state->getValue('advanced', []);
    $this->configuration['top_region'] = (bool) ($advanced['top_region'] ?? FALSE);
    $this->configuration['bottom_region'] = (bool) ($advanced['bottom_region'] ?? FALSE);
    $this->configuration['tabs_class'] = $advanced['tabs_class'] ?? '';
    $this->configuration['tabs_toggle'] = (bool) ($advanced['tabs_toggle'] ?? FALSE);
    $this->configuration['animation'] = $advanced['animation'] ?? 'none';
    $this->configuration['border_toggle'] = $advanced['border_toggle'] ?? 'none';
    $this->configuration['border_color'] = $advanced['border_color'] ?? '#ECECEC';
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);
    $config = $this->configuration;

    // Build class array.
    $classes = [
      'layout',
      'webser-layout',
      $this->getPluginDefinition()->getTemplate(),
    ];

    // Add width variation class.
    if (!empty($config['column_widths'])) {
      $classes[] = $this->getPluginDefinition()->getTemplate() . '--' . str_replace('/', '-', $config['column_widths']);
    }

    // Add configuration-based classes.
    if (!empty($config['extra_class'])) {
      $classes[] = $config['extra_class'];
    }

    if (!empty($config['background_color']) && $config['background_color'] !== 'transparent') {
      $classes[] = 'bg-' . $config['background_color'];
    }

    if ($config['container']) {
      $classes[] = 'has-container';
    }

    if (!empty($config['padding']) && $config['padding'] !== 'none') {
      $classes[] = $config['padding'] === 'both' ? 'py-section' : 'p' . substr($config['padding'], 0, 1) . '-section';
    }

    if (!empty($config['tabs_class'])) {
      $classes[] = 'has-tabs';
      if ($config['tabs_toggle']) {
        $classes[] = 'tabs-toggle';
      }
    }

    if (!empty($config['animation']) && $config['animation'] !== 'none') {
      $classes[] = 'animate-' . $config['animation'];
    }

    if (!empty($config['border_toggle']) && $config['border_toggle'] !== 'none') {
      $classes[] = 'has-border-' . $config['border_toggle'];
    }

    $build['#attributes']['class'] = $classes;

    // Add border color as CSS variable if needed.
    if (!empty($config['border_toggle']) && $config['border_toggle'] !== 'none') {
      $build['#attributes']['style'] = '--border-color: ' . $config['border_color'];
    }

    return $build;
  }

  /**
   * Gets available background color options.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Background color options.
   */
  protected function getBackgroundColorOptions(): array {
    return [
      'transparent' => $this->t('Transparent'),
      'white' => $this->t('White'),
      'light' => $this->t('Light gray'),
      'dark' => $this->t('Dark'),
      'primary' => $this->t('Primary brand color'),
      'secondary' => $this->t('Secondary brand color'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  abstract protected function getWidthOptions(): array;

  /**
   * Provides default width value.
   *
   * @return string
   *   Default width key.
   */
  protected function getDefaultWidth(): string {
    $widthClasses = array_keys($this->getWidthOptions());
    $default = reset($widthClasses);
    return (string) $default;
  }

}
