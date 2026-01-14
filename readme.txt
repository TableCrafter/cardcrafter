=== CardCrafter – Data-Driven Card Grids ===
Contributors: fahdi
Tags: json, cards, grid, portfolio, team
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.4
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
*   **🔧 Flexible Layouts:** Choose between Grid, Masonry, or List view.

### 🚀 Key Features

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

1.  Upload the `cardcrafter` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to the **CardCrafter** admin menu.
4.  Paste your JSON URL or try the demo data.
5.  Copy the shortcode and add it to any page.

== Usage ==

**Basic Shortcode:**
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

1. **Admin Dashboard & Grid Preview (Team Directory)** - Configure your card layout and preview instantly in the WordPress admin.
2. **Masonry Layout (Team Directory)** - Pinterest-style masonry layout for varied content heights.
3. **List Layout (Team Directory)** - Clean horizontal list view for detailed content.
4. **Product Showcase** - Grid layout perfect for displaying products with prices.
5. **Portfolio Gallery** - Elegant display for creative work.

== Changelog ==

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
