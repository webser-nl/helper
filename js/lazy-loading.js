/**
 * @file
 * Lazy loading for images and iframes.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Lazy loading behavior using Intersection Observer.
   */
  Drupal.behaviors.webserLazyLoading = {
    attach: function (context) {
      // Check for Intersection Observer support.
      if (!('IntersectionObserver' in window)) {
        return;
      }

      const lazyElements = once('lazy-load', '[data-lazy-src], [loading="lazy"]', context);

      if (lazyElements.length === 0) {
        return;
      }

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const element = entry.target;

            // Handle data-lazy-src attribute.
            if (element.hasAttribute('data-lazy-src')) {
              const src = element.getAttribute('data-lazy-src');
              element.setAttribute('src', src);
              element.removeAttribute('data-lazy-src');
            }

            // Handle data-lazy-srcset attribute.
            if (element.hasAttribute('data-lazy-srcset')) {
              const srcset = element.getAttribute('data-lazy-srcset');
              element.setAttribute('srcset', srcset);
              element.removeAttribute('data-lazy-srcset');
            }

            element.classList.add('lazy-loaded');
            observer.unobserve(element);
          }
        });
      }, {
        rootMargin: '50px 0px',
        threshold: 0.01
      });

      lazyElements.forEach(element => {
        observer.observe(element);
      });
    }
  };

})(Drupal, once);
