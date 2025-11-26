/**
 * @file
 * Social share functionality.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Social share behavior.
   */
  Drupal.behaviors.webserSocialShare = {
    attach: function (context) {
      // Handle collapsed toggle.
      once('social-share-toggle', '.collapsed-icons .toggle-share', context).forEach(toggle => {
        toggle.addEventListener('click', () => {
          const wrapper = toggle.closest('.social-share-wrapper');
          const icons = wrapper.querySelector('.icons-wrapper');
          icons.classList.toggle('is-open');
        });
      });

      // Handle copy to clipboard.
      once('social-share-copy', '.share-icon--copy', context).forEach(copyBtn => {
        copyBtn.addEventListener('click', (e) => {
          e.preventDefault();

          const url = copyBtn.getAttribute('href');

          if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(() => {
              showCopyFeedback(copyBtn);
            });
          } else {
            // Fallback for older browsers.
            const textArea = document.createElement('textarea');
            textArea.value = url;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();

            try {
              document.execCommand('copy');
              showCopyFeedback(copyBtn);
            } catch (error) {
              console.error('Copy failed:', error);
            }

            document.body.removeChild(textArea);
          }
        });
      });

      /**
       * Shows visual feedback after copying.
       */
      function showCopyFeedback(button) {
        button.classList.add('copied');
        const originalText = button.querySelector('span').textContent;
        button.querySelector('span').textContent = Drupal.t('Copied!');

        setTimeout(() => {
          button.classList.remove('copied');
          button.querySelector('span').textContent = originalText;
        }, 2000);
      }

      // Handle social share clicks (open in popup).
      once('social-share-popup', '.share-icon:not(.share-icon--copy):not(.share-icon--email)', context).forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();

          const url = link.getAttribute('href');
          const width = 600;
          const height = 400;
          const left = (screen.width / 2) - (width / 2);
          const top = (screen.height / 2) - (height / 2);

          window.open(
            url,
            'share',
            `width=${width},height=${height},left=${left},top=${top},toolbar=0,status=0`
          );
        });
      });
    }
  };

})(Drupal, once);
