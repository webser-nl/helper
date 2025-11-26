# Webser Helper Module

Modern Drupal 10/11 helper module for Webser projects with a focus on Layout Builder optimization, performance, and developer experience.

## 🚀 Features

### Layout Builder
- **Custom Layout Plugins**: Flexible 1, 2, and 3 column layouts with extensive configuration
- **Column Width Control**: Bootstrap grid-based column sizing (1-12)
- **Container Management**: Toggle Bootstrap containers per section
- **Background Colors**: Pre-configured color options with custom support
- **Padding Options**: Top, bottom, both, or none
- **Extra Regions**: Optional top and bottom full-width regions
- **Tab System**: Automatic tab navigation from blocks
- **CSS Animations**: Scroll-triggered animations (fade, slide, scale)
- **Border Styling**: Top or bottom borders with custom colors

### Blocks
- **Social Share Block**: Share content on Facebook, Twitter, LinkedIn, WhatsApp, Email with copy-to-clipboard
  - Configurable services
  - Collapsed mode option
  - Automatic entity detection (nodes, taxonomy terms, media)
  - Popup windows for social platforms

### Services
- **Image Helper Service**: Modern dependency-injected service for image operations
  - Get image URLs with image styles
  - Multi-value image fields support
  - Responsive image data (srcset)
  - Media entity support
  - Proper error handling and logging

- **Cache Helper Service**: Cache management utilities
  - Tag invalidation
  - Cache metadata merging
  - Entity cache metadata building

### Twig Extensions
Powerful Twig filters and functions for templates:

**Filters:**
- `|image_style` - Apply image style to URI or entity
- `|truncate_html` - Truncate HTML preserving tags
- `|render_entity` - Render entity in view mode

**Functions:**
- `webser_image()` - Get image URL from entity field
- `webser_view()` - Embed views
- `webser_load_entity()` - Load entities

### Performance
- Lazy loading support
- Optimized Layout Builder dialogs (wider modals)
- Efficient cache strategies
- Minimal JavaScript footprint

## 📦 Installation

1. **Place module in custom modules directory:**
   ```bash
   cp -r webser_helper_new /path/to/drupal/web/modules/custom/webser_helper
   ```

2. **Enable the module:**
   ```bash
   drush en webser_helper -y
   ```

3. **Clear caches:**
   ```bash
   drush cr
   ```

## 🎯 Usage

### Using Custom Layouts

1. Go to any Layout Builder page
2. Add a new section
3. Choose "One column", "Two column", or "Three column"
4. Configure layout options:
   - Select column widths (e.g., "6/6" for equal columns)
   - Toggle container
   - Choose background color
   - Set padding
   - Advanced: animations, borders, tabs, etc.

### Using Twig Extensions

In your templates:

```twig
{# Apply image style #}
<img src="{{ node.field_image.entity.uri.value|image_style('large') }}" alt="{{ node.field_image.alt }}">

{# Get image URL #}
{% set image_url = webser_image(node, 'field_image', 'thumbnail') %}

{# Truncate HTML #}
{{ node.body.value|truncate_html(200, '...') }}

{# Render entity #}
{{ referenced_node|render_entity('teaser') }}

{# Embed view #}
{{ webser_view('content', 'block_1', node.id) }}

{# Load entity #}
{% set node = webser_load_entity('node', 123) %}
```

### Using Image Helper Service

In custom code:

```php
// Inject service
$imageHelper = \Drupal::service('webser_helper.image_helper');

// Get single image URL
$url = $imageHelper->getImageUrl($node, 'field_image', 'large');

// Get multiple images
$images = $imageHelper->getImageUrls($node, 'field_gallery', 'thumbnail');
foreach ($images as $image) {
  // $image['url'], $image['alt'], $image['title']
}

// Get responsive image data
$responsiveData = $imageHelper->getResponsiveImageData($node, 'field_image', [
  'thumbnail' => 480,
  'medium' => 768,
  'large' => 1024,
]);
// Returns: ['src' => '...', 'srcset' => '...', 'sizes' => '...']
```

### Adding Social Share Block

1. Go to Block layout or Layout Builder
2. Add "Social Share" block
3. Configure:
   - Select services to display
   - Enable/disable collapsed mode
4. Block automatically detects current entity (node, term, media)

## 🛠️ Extending

### Creating Custom Layout

Extend `WebserLayoutBase`:

```php
namespace Drupal\your_module\Plugin\Layout;

use Drupal\webser_helper\Plugin\Layout\WebserLayoutBase;

class FourColumnLayout extends WebserLayoutBase {

  protected function getWidthOptions(): array {
    return [
      '3/3/3/3' => '3/3/3/3 (Equal)',
      '4/2/2/4' => '4/2/2/4 (Wide sides)',
    ];
  }

}
```

### Adding Custom Twig Functions

1. Create event subscriber
2. Add to `webser_helper.services.yml`
3. Tag as `twig.extension`

## 📋 Requirements

- **Drupal**: 10 or 11
- **PHP**: 8.2+
- **Required modules**:
  - layout_builder
  - layout_discovery
  - block
  - media
  - image

## ⚙️ Configuration

No configuration needed! Module works out of the box.

Optional theme settings can be managed in your theme.

## 🔄 Changelog

### Version 2.0.0
- Complete rewrite with modern PHP 8.2+ practices
- Strict typing throughout
- Dependency injection for all services
- Improved Layout Builder UX
- New Twig extensions
- Performance optimizations
- Removed deprecated code
- Better error handling and logging

### Migrating from 1.x
- Custom blocks using old `CustomContentBlock`: Update to use new service-based approach
- Extended entities (ExtendedNode/Media): Use new Image Helper service methods instead
- Static Drupal calls: Update to dependency injection
- Check system for any direct references to old classes

## 🤝 Contributing

Created and maintained by Webser (https://www.webser.nl)

## 📄 License

GPL-2.0-or-later
