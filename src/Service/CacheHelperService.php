<?php

declare(strict_types=1);

namespace Drupal\webser_helper\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Service for cache-related helper functions.
 */
class CacheHelperService {

  /**
   * The logger channel.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs CacheHelperService.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   Cache tags invalidator.
   * @param \Drupal\Core\Cache\CacheBackendInterface $renderCache
   *   Render cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory.
   */
  public function __construct(
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected CacheBackendInterface $renderCache,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('webser_helper');
  }

  /**
   * Invalidates cache tags.
   *
   * @param array<string> $tags
   *   Array of cache tags to invalidate.
   */
  public function invalidateTags(array $tags): void {
    if (empty($tags)) {
      return;
    }

    $this->cacheTagsInvalidator->invalidateTags($tags);
    $this->logger->info('Invalidated cache tags: @tags', ['@tags' => implode(', ', $tags)]);
  }

  /**
   * Merges cache metadata arrays.
   *
   * @param array<array{tags?: array, contexts?: array, max-age?: int}> $cacheMetadata
   *   Array of cache metadata arrays to merge.
   *
   * @return array{tags: array, contexts: array, max-age: int}
   *   Merged cache metadata.
   */
  public function mergeCacheMetadata(array $cacheMetadata): array {
    $merged = [
      'tags' => [],
      'contexts' => [],
      'max-age' => Cache::PERMANENT,
    ];

    foreach ($cacheMetadata as $metadata) {
      if (!empty($metadata['tags'])) {
        $merged['tags'] = Cache::mergeTags($merged['tags'], $metadata['tags']);
      }

      if (!empty($metadata['contexts'])) {
        $merged['contexts'] = Cache::mergeContexts($merged['contexts'], $metadata['contexts']);
      }

      if (isset($metadata['max-age'])) {
        $merged['max-age'] = Cache::mergeMaxAges($merged['max-age'], $metadata['max-age']);
      }
    }

    return $merged;
  }

  /**
   * Builds cache metadata for an entity.
   *
   * @param object $entity
   *   The entity object.
   * @param array<string> $additionalTags
   *   Additional cache tags to include.
   *
   * @return array{tags: array, contexts: array, max-age: int}
   *   Cache metadata array.
   */
  public function buildEntityCacheMetadata(object $entity, array $additionalTags = []): array {
    $tags = [];

    if (method_exists($entity, 'getCacheTags')) {
      $tags = $entity->getCacheTags();
    }

    if (!empty($additionalTags)) {
      $tags = Cache::mergeTags($tags, $additionalTags);
    }

    $contexts = method_exists($entity, 'getCacheContexts')
      ? $entity->getCacheContexts()
      : [];

    $maxAge = method_exists($entity, 'getCacheMaxAge')
      ? $entity->getCacheMaxAge()
      : Cache::PERMANENT;

    return [
      'tags' => $tags,
      'contexts' => $contexts,
      'max-age' => $maxAge,
    ];
  }

}
