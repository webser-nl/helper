<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Twig;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\RendererInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension for Webser helper functions and filters.
 */
class WebserTwigExtension extends AbstractExtension {

  /**
   * Constructs WebserTwigExtension.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   File URL generator.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   Image factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected ImageFactory $imageFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'webser_helper';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new TwigFilter('image_style', [$this, 'imageStyleFilter']),
      new TwigFilter('truncate_html', [$this, 'truncateHtmlFilter'], ['is_safe' => ['html']]),
      new TwigFilter('render_entity', [$this, 'renderEntityFilter']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('webser_image', [$this, 'getImageUrl']),
      new TwigFunction('webser_view', [$this, 'embedView']),
      new TwigFunction('webser_load_entity', [$this, 'loadEntity']),
    ];
  }

  /**
   * Applies image style to file URI or entity.
   *
   * Usage in Twig:
   * {{ file_uri|image_style('thumbnail') }}
   * {{ node.field_image.entity.uri.value|image_style('large') }}
   *
   * @param mixed $input
   *   File URI string or entity.
   * @param string $styleName
   *   Image style machine name.
   *
   * @return string|null
   *   Styled image URL or NULL.
   */
  public function imageStyleFilter(mixed $input, string $styleName): ?string {
    try {
      $imageStyle = $this->entityTypeManager
        ->getStorage('image_style')
        ->load($styleName);

      if (!$imageStyle) {
        return NULL;
      }

      // Handle string URI.
      if (is_string($input)) {
        return $imageStyle->buildUrl($input);
      }

      // Handle file entity.
      if ($input instanceof \Drupal\file\FileInterface) {
        return $imageStyle->buildUrl($input->getFileUri());
      }

      return NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Truncates HTML string preserving tags.
   *
   * Usage: {{ content.body|truncate_html(200, '...') }}
   *
   * @param string|null $html
   *   HTML string.
   * @param int $length
   *   Maximum length in characters.
   * @param string $suffix
   *   Suffix to append (e.g., '...').
   *
   * @return string
   *   Truncated HTML.
   */
  public function truncateHtmlFilter(?string $html, int $length = 200, string $suffix = '...'): string {
    if (empty($html)) {
      return '';
    }

    // Strip tags for length calculation.
    $text = strip_tags($html);

    if (mb_strlen($text) <= $length) {
      return $html;
    }

    // Simple truncation preserving words.
    $truncated = mb_substr($text, 0, $length);
    $lastSpace = mb_strrpos($truncated, ' ');

    if ($lastSpace !== FALSE) {
      $truncated = mb_substr($truncated, 0, $lastSpace);
    }

    return $truncated . $suffix;
  }

  /**
   * Renders an entity in a specific view mode.
   *
   * Usage: {{ node|render_entity('teaser') }}
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to render.
   * @param string $viewMode
   *   View mode machine name.
   *
   * @return array
   *   Render array.
   */
  public function renderEntityFilter(ContentEntityInterface $entity, string $viewMode = 'default'): array {
    try {
      $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      return $viewBuilder->view($entity, $viewMode);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets image URL from entity field.
   *
   * Usage: {{ webser_image(node, 'field_image', 'thumbnail') }}
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $fieldName
   *   Field name.
   * @param string|null $imageStyle
   *   Optional image style.
   *
   * @return string|null
   *   Image URL or NULL.
   */
  public function getImageUrl(
    ContentEntityInterface $entity,
    string $fieldName,
    ?string $imageStyle = NULL,
  ): ?string {
    $imageHelper = \Drupal::service('webser_helper.image_helper');
    return $imageHelper->getImageUrl($entity, $fieldName, $imageStyle);
  }

  /**
   * Embeds a view.
   *
   * Usage: {{ webser_view('content', 'block_1', arg1, arg2) }}
   *
   * @param string $viewId
   *   View machine name.
   * @param string $displayId
   *   Display ID.
   * @param mixed ...$args
   *   Optional view arguments.
   *
   * @return array
   *   Render array.
   */
  public function embedView(string $viewId, string $displayId = 'default', mixed ...$args): array {
    try {
      $viewStorage = $this->entityTypeManager->getStorage('view');
      $view = $viewStorage->load($viewId);

      if (!$view) {
        return [];
      }

      return [
        '#type' => 'view',
        '#name' => $viewId,
        '#display_id' => $displayId,
        '#arguments' => $args,
      ];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Loads an entity by type and ID.
   *
   * Usage: {% set node = webser_load_entity('node', 123) %}
   *
   * @param string $entityType
   *   Entity type ID.
   * @param int|string $entityId
   *   Entity ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   Loaded entity or NULL.
   */
  public function loadEntity(string $entityType, int|string $entityId): ?ContentEntityInterface {
    try {
      $entity = $this->entityTypeManager
        ->getStorage($entityType)
        ->load($entityId);

      return $entity instanceof ContentEntityInterface ? $entity : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
