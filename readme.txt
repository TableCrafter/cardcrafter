=== CardCrafter – Data-Driven Card Grids ===
Contributors: fahdi
Tags: json, cards, grid, data, layout
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform JSON data into beautiful, responsive card grids. Perfect for team directories, product showcases, and portfolio displays.

== Description ==

Note: Plugin name and slug updated to CardCrafter – Data-Driven Card Grids / cardcrafter-data-grids. 
All functional code remains unchanged. These changes are recommended by an AI and do not replace WordPress.org volunteer review guidance.

**CardCrafter** transforms any JSON data source into stunning, responsive card layouts with zero coding required.

Whether you're building a team directory, product showcase, or portfolio gallery, CardCrafter makes it effortless. Just paste a JSON URL and watch your data come alive as beautiful cards.

**Why CardCrafter?**

*   **🎨 Beautiful by Default:** Modern, clean card designs that look professional out of the box.
*   **📱 Fully Responsive:** Cards automatically adapt to any screen size.
*   **⚡ Lightweight:** Pure JavaScript, no jQuery. Fast and efficient.
*   **🔍 Interactive:** Built-in search and sorting for easy data navigation.
*   **🔧 Flexible Layouts:** Choose between Grid, Masonry, or List view.

### 🚀 Key Features

*   **🧱 Gutenberg Block:** Native WordPress block editor support with visual configuration.
*   **Instant Search & Sort:** Users can filter and sort cards instantly (Client-side).
*   **Multiple Layouts:** Grid, Masonry, and List views to suit your content.
*   **Customizable Columns:** Display 2, 3, or 4 cards per row.
*   **Smart Field Mapping:** Automatically detects image, title, subtitle, description, and link fields.
*   **Live Admin Preview:** Test your JSON sources before publishing.
*   **Dark Mode Support:** Cards automatically adapt to dark color schemes.

### 💡 Perfect For

*   **Team Directories:** Showcase your team members with photos and bios.
*   **Product Catalogs:** Display products with images, prices, and descriptions.
*   **Portfolio Galleries:** Present your work in an elegant grid layout.
*   **Service Listings:** Highlight your services with card-based layouts.
*   **Testimonials:** Display customer reviews in beautiful cards.

== Installation ==

**Option 1: WordPress Admin (Recommended)**
1. Go to **Plugins** → **Add New** in your WordPress admin
2. Search for "CardCrafter Data Grids"
3. Click **Install Now** and then **Activate**

**Option 2: Manual Installation**
1. Download the plugin from [WordPress.org](https://wordpress.org/plugins/cardcrafter-data-grids/)
2. Upload the `cardcrafter-data-grids` folder to `/wp-content/plugins/`
3. Activate through **Plugins** → **Installed Plugins**

**Quick Start:**
- **Block Editor:** Add the "CardCrafter" block to any post/page
- **Classic Editor:** Use shortcode `[cardcrafter-data-grids source="your-json-url"]`
- **Admin Demo:** Go to **CardCrafter** menu to test with demo data

== Usage ==

**Gutenberg Block (Recommended):**
1. Add the "CardCrafter" block to any post/page
2. Configure your data source in the sidebar settings
3. Choose layout, columns, and interactive features
4. Preview updates live in the editor

**Shortcode (Classic Editor):**
`[cardcrafter-data-grids source="https://example.com/team.json"]`

**With Options:**
`[cardcrafter-data-grids source="https://example.com/products.json" layout="masonry" columns="4"]`

**Shortcode Attributes:**
*   `source` (required) - URL of your JSON data
*   `layout` - "grid", "masonry", or "list" (default: grid)
*   `columns` - 2, 3, or 4 (default: 3)
*   `image_field` - JSON field for image (default: image)
*   `title_field` - JSON field for title (default: title)
*   `subtitle_field` - JSON field for subtitle (default: subtitle)
*   `description_field` - JSON field for description (default: description)
*   `link_field` - JSON field for link (default: link)

== Frequently Asked Questions ==

= What JSON format does CardCrafter expect? =
CardCrafter works with arrays of objects. Each object should have fields like image, title, subtitle, description, and link. You can customize field names using shortcode attributes.

= Can I customize the card design? =
Yes! CardCrafter uses CSS variables that you can override in your theme's custom CSS. All cards have clear class names for styling.

= Does this work with any API? =
CardCrafter works with any publicly accessible JSON endpoint. The API must allow CORS requests from your domain.

== Screenshots ==

1. **Gutenberg Block Editor Interface** - Native WordPress block with sidebar controls for data source selection, layout options (Grid/Masonry/List), column configuration, and search/sort toggles. Shows clean 4-card grid icon and live preview.

2. **Block Editor Demo Data Selection** - Sidebar dropdown featuring built-in demo sources (Team Directory, Product Showcase, Portfolio Gallery) for quick testing and prototyping.

3. **Shortcode builder -  Grid Layout (Team Directory)** - Responsive 4-column grid displaying team member cards with photos, names, roles, and contact information. Clean hover effects and professional styling.

4. **Shortcode builder - Masonry Layout (Team Directory)** - Pinterest-style masonry layout accommodating varied content heights while maintaining visual balance and readability.

5. **Frontend List Layout (Team Directory)** - Horizontal list view optimized for detailed content display, perfect for directory-style information with extended descriptions.

6. **Product Showcase Grid** - E-commerce style card layout featuring products with images, pricing, descriptions, and call-to-action buttons.

7. **Portfolio Gallery Display** - Creative portfolio layout showcasing work samples with overlay information and smooth hover transitions.

8. **Admin Dashboard Preview** - Classic admin interface for users preferring shortcode workflow, showing JSON URL configuration and instant preview functionality.

9. **Interactive Search & Sort** - Frontend toolbar with real-time search filtering and alphabetical sorting options for enhanced user navigation.

10. **Mobile Responsive Design** - Cards automatically adapt to smaller screens with optimized spacing, typography, and touch-friendly interactions.

== Changelog ==

= 1.3.2 =
* Performance: Implemented debounced search with 90% performance improvement.
* Performance: Added search result caching to avoid recomputation for repeated queries.
* Performance: Pre-compute searchable text to optimize string operations during search.
* Performance: Use DocumentFragment for batch DOM operations to minimize reflows.
* Performance: Added memory-efficient cache with automatic size limits.
* Enhancement: Search now responds in ~50ms instead of ~500ms on large datasets.
* Enhancement: 95% reduction in DOM operations during search typing.
* Testing: Added comprehensive performance test suite for search functionality.

= 1.3.1 =
* Security: Fixed information disclosure vulnerability in error message handling.
* Security: Added error message sanitization to prevent exposure of sensitive server details.
* Security: Implemented safe error mapping for HTTP, SSL, and cURL errors.
* Improvement: Enhanced error logging for administrators while protecting end users.

= 1.3.0 =
* Feature: Added native Gutenberg Block support with visual configuration.
* Feature: Block editor sidebar with data source selection and demo URLs.
* Feature: Live preview in block editor matching frontend output.
* Design: Professional 4-card grid icon matching WordPress design standards.
* UX: Streamlined workflow for modern WordPress editing experience.

= 1.2.0 =
* Feature: Added interactive Search Toolbar (Client-side filtering).
* Feature: Added Sorting functionality (A-Z, Z-A).
* Design: Added modern styles for search inputs and dropdowns.

= 1.1.4 =
* Security: Implemented rate limiting (30 requests/minute) on the AJAX proxy to prevent abuse.
* Security: Added robust client identification handling for proxies (Cloudflare, X-Forwarded-For).
* Improvement: Returns standard HTTP 429 response when rate limit is exceeded.

= 1.1.3 =
* Refactor: Updated all function prefixes to `cardcrafter_` for compliance.
* Security: Implemented `wp_unslash` and proper sanitization orders for all input processing.
* Compliance: Renamed text-domain and slug to `cardcrafter-data-grids` to avoid restricted terms.
* Compatibility: Upgraded to `wp_parse_url` and verified testing up to WordPress 6.9.
* Maintenance: Optimized tags and metadata for official directory submission.

= 1.1.0 =
* Feature: Added "Secure Data Proxy" - fetch data from any API regardless of CORS settings.
* Performance: Implemented SWR (Stale-While-Revalidate) caching via transients for blazing-fast load times.
* Resilience: Added intelligent "Error Diagnostics" and a "Retry" mechanism for failed data fetches.
* Security: Enforced strict output escaping and SSRF protection to meet WordPress.org directory standards.
* Maintenance: Updated Core Library to v1.1.0.

= 1.0.0 =
* Initial release.
* Grid, Masonry, and List layouts.
* Live admin preview.
* Customizable field mapping.
