=== CardCrafter – JSON to Card Layouts ===
Contributors: fahdi
Tags: cards, json, api, grid, team, portfolio
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform JSON data into beautiful, responsive card grids. Perfect for team pages, product showcases, and portfolios.

== Description ==

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
`[cardcrafter source="https://example.com/team.json"]`

**With Options:**
`[cardcrafter source="https://example.com/products.json" layout="masonry" columns="4"]`

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

1. **Admin Dashboard** - Configure your card layout and preview instantly in the WordPress admin.
2. **Grid Layout** - Beautiful responsive card grid displaying team members.
3. **Masonry Layout** - Pinterest-style masonry layout for varied content heights.

== Changelog ==

= 1.0.0 =
*   Initial release.
*   Grid, Masonry, and List layouts.
*   Live admin preview.
*   Customizable field mapping.
