<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaInterface;

/**
 * Service for image-related helper functions.
 */
class ImageHelperService {

  /**
   * The logger channel.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs ImageHelperService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   File URL generator.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   Image factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected ImageFactory $imageFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('webser_helper');
  }

  /**
   * Gets image URL from entity field with optional image style.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the image field.
   * @param string $fieldName
   *   The field name containing the image.
   * @param string|null $imageStyle
   *   Optional image style machine name.
   * @param string|null $fallbackField
   *   Optional fallback field name if primary field is empty.
   *
   * @return string|null
   *   The image URL or NULL if not found.
   */
  public function getImageUrl(
    ContentEntityInterface $entity,
    string $fieldName,
    ?string $imageStyle = NULL,
    ?string $fallbackField = NULL,
  ): ?string {
    if (!$entity->hasField($fieldName)) {
      return NULL;
    }

    $file = $this->getFileFromField($entity, $fieldName);

    // Try fallback field if primary is empty.
    if (!$file && $fallbackField && $entity->hasField($fallbackField)) {
      $file = $this->getFileFromField($entity, $fallbackField);
    }

    if (!$file instanceof FileInterface) {
      return NULL;
    }

    // Apply image style if provided.
    if ($imageStyle) {
      return $this->getStyledImageUrl($file, $imageStyle);
    }

    return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
  }

  /**
   * Gets multiple image URLs from a multi-value image field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $fieldName
   *   The field name.
   * @param string|null $imageStyle
   *   Optional image style.
   *
   * @return array<int, array{url: string, alt: string, title: string}>
   *   Array of image data.
   */
  public function getImageUrls(
    ContentEntityInterface $entity,
    string $fieldName,
    ?string $imageStyle = NULL,
  ): array {
    if (!$entity->hasField($fieldName) || $entity->get($fieldName)->isEmpty()) {
      return [];
    }

    $images = [];
    foreach ($entity->get($fieldName) as $item) {
      $file = NULL;

      // Handle media reference.
      if (isset($item->entity) && $item->entity instanceof MediaInterface) {
        $media = $item->entity;
        if ($media->hasField('field_media_image')) {
          $file = $media->get('field_media_image')->entity;
          $alt = $media->get('field_media_image')->alt ?? '';
          $title = $media->get('field_media_image')->title ?? '';
        }
      }
      // Handle direct file reference.
      elseif (isset($item->entity) && $item->entity instanceof FileInterface) {
        $file = $item->entity;
        $alt = $item->alt ?? '';
        $title = $item->title ?? '';
      }

      if ($file instanceof FileInterface) {
        $url = $imageStyle
          ? $this->getStyledImageUrl($file, $imageStyle)
          : $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

        if ($url) {
          $images[] = [
            'url' => $url,
            'alt' => $alt ?? '',
            'title' => $title ?? '',
          ];
        }
      }
    }

    return $images;
  }

  /**
   * Gets responsive image srcset for an image field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $fieldName
   *   The field name.
   * @param array<string, int> $sizes
   *   Array of image style => width pairs.
   *
   * @return array{src: string, srcset: string, sizes: string}|null
   *   Responsive image data or NULL.
   */
  public function getResponsiveImageData(
    ContentEntityInterface $entity,
    string $fieldName,
    array $sizes,
  ): ?array {
    $file = $this->getFileFromField($entity, $fieldName);

    if (!$file instanceof FileInterface) {
      return NULL;
    }

    $srcset = [];
    $defaultSrc = NULL;

    foreach ($sizes as $styleName => $width) {
      $url = $this->getStyledImageUrl($file, $styleName);
      if ($url) {
        $srcset[] = $url . ' ' . $width . 'w';
        if (!$defaultSrc) {
          $defaultSrc = $url;
        }
      }
    }

    if (empty($srcset)) {
      return NULL;
    }

    return [
      'src' => $defaultSrc,
      'srcset' => implode(', ', $srcset),
      'sizes' => '100vw',
    ];
  }

  /**
   * Gets File entity from entity field.
   *
   * Handles both direct file references and media entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $fieldName
   *   The field name.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file entity or NULL.
   */
  protected function getFileFromField(ContentEntityInterface $entity, string $fieldName): ?FileInterface {
    if (!$entity->hasField($fieldName) || $entity->get($fieldName)->isEmpty()) {
      return NULL;
    }

    $item = $entity->get($fieldName)->first();
    if (!$item || !isset($item->entity)) {
      return NULL;
    }

    $referencedEntity = $item->entity;

    // Handle media entity.
    if ($referencedEntity instanceof MediaInterface) {
      if ($referencedEntity->hasField('field_media_image')) {
        $mediaItem = $referencedEntity->get('field_media_image')->first();
        return $mediaItem?->entity instanceof FileInterface ? $mediaItem->entity : NULL;
      }
      return NULL;
    }

    // Handle direct file entity.
    return $referencedEntity instanceof FileInterface ? $referencedEntity : NULL;
  }

  /**
   * Gets styled image URL.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   * @param string $styleName
   *   The image style machine name.
   *
   * @return string|null
   *   The styled image URL or NULL.
   */
  protected function getStyledImageUrl(FileInterface $file, string $styleName): ?string {
    try {
      $imageStyle = $this->entityTypeManager
        ->getStorage('image_style')
        ->load($styleName);

      if (!$imageStyle instanceof ImageStyleInterface) {
        $this->logger->warning('Image style "@style" does not exist.', ['@style' => $styleName]);
        return NULL;
      }

      return $imageStyle->buildUrl($file->getFileUri());
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading image style: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

}
