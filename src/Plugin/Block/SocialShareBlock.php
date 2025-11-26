<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Social Share block.
 *
 * @Block(
 *   id = "webser_social_share",
 *   admin_label = @Translation("Social Share"),
 *   category = @Translation("Webser"),
 * )
 */
class SocialShareBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new SocialShareBlock.
   *
   * @param array $configuration
   *   Block configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'services' => ['facebook', 'twitter', 'linkedin', 'whatsapp', 'email'],
      'collapsed' => FALSE,
      'label_display' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $services = $this->getAvailableServices();

    $form['services'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Share services'),
      '#description' => $this->t('Select which social sharing options to display.'),
      '#options' => array_combine(array_keys($services), array_column($services, 'label')),
      '#default_value' => $this->configuration['services'],
    ];

    $form['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsed by default'),
      '#description' => $this->t('Hide icons behind a toggle button.'),
      '#default_value' => $this->configuration['collapsed'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['services'] = array_filter($form_state->getValue('services'));
    $this->configuration['collapsed'] = (bool) $form_state->getValue('collapsed');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $entity = $this->getEntityFromRoute();

    if (!$entity) {
      return [];
    }

    $url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    $title = $entity->label();

    return [
      '#theme' => 'webser_social_share',
      '#url' => $url,
      '#title' => $title,
      '#image' => $this->getEntityImage($entity),
      '#services' => $this->getSelectedServices(),
      '#collapsed' => $this->configuration['collapsed'],
      '#attached' => [
        'library' => ['webser_helper/social-share'],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => $entity->getCacheTags(),
      ],
    ];
  }

  /**
   * Gets entity from current route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity or NULL.
   */
  protected function getEntityFromRoute(): ?ContentEntityInterface {
    // Try common entity types.
    foreach (['node', 'taxonomy_term', 'media'] as $entityType) {
      $entity = $this->routeMatch->getParameter($entityType);
      if ($entity instanceof ContentEntityInterface) {
        return $entity;
      }
    }

    return NULL;
  }

  /**
   * Gets image URL from entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string|null
   *   Image URL or NULL.
   */
  protected function getEntityImage(ContentEntityInterface $entity): ?string {
    // Try common image field names.
    $imageFields = ['field_image', 'field_media_image', 'field_thumbnail'];

    foreach ($imageFields as $fieldName) {
      if ($entity->hasField($fieldName) && !$entity->get($fieldName)->isEmpty()) {
        $imageHelper = \Drupal::service('webser_helper.image_helper');
        return $imageHelper->getImageUrl($entity, $fieldName);
      }
    }

    return NULL;
  }

  /**
   * Gets selected services configuration.
   *
   * @return array<string, array{label: string, icon: string}>
   *   Selected services.
   */
  protected function getSelectedServices(): array {
    $allServices = $this->getAvailableServices();
    $selectedKeys = $this->configuration['services'];

    return array_intersect_key($allServices, array_flip($selectedKeys));
  }

  /**
   * Gets available share services.
   *
   * @return array<string, array{label: string, icon: string}>
   *   Available services.
   */
  protected function getAvailableServices(): array {
    return [
      'facebook' => [
        'label' => $this->t('Facebook'),
        'icon' => 'facebook',
      ],
      'twitter' => [
        'label' => $this->t('Twitter / X'),
        'icon' => 'twitter',
      ],
      'linkedin' => [
        'label' => $this->t('LinkedIn'),
        'icon' => 'linkedin',
      ],
      'whatsapp' => [
        'label' => $this->t('WhatsApp'),
        'icon' => 'whatsapp',
      ],
      'email' => [
        'label' => $this->t('Email'),
        'icon' => 'envelope',
      ],
      'copy' => [
        'label' => $this->t('Copy link'),
        'icon' => 'link',
      ],
    ];
  }

}
