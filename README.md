# CardCrafter – Data-Driven Card Grids

**Contributors:** fahdi  
**Tags:** cards, json, api, grid, team, portfolio  
**Requires at least:** 5.0  
**Requires at least:** 5.0  
**Tested up to:** 6.9  
**Stable tag:** 1.2.0  
**Requires PHP:** 7.4  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Transform JSON data into beautiful, responsive card grids. Perfect for team directories, product showcases, and portfolio displays.

> **Note:** Plugin name and slug updated to **CardCrafter – Data-Driven Card Grids** / `cardcrafter-data-grids`. All functional code remains unchanged. These changes are recommended by an AI and do not replace WordPress.org volunteer review guidance.

## Description

**CardCrafter** transforms any JSON data source into stunning, responsive card layouts with zero coding required.

Whether you're building a team directory, product showcase, or portfolio gallery, CardCrafter makes it effortless. Just paste a JSON URL and watch your data come alive as beautiful cards.

**Why CardCrafter?**

*   **🎨 Beautiful by Default:** Modern, clean card designs that look professional out of the box.
*   **📱 Fully Responsive:** Cards automatically adapt to any screen size.
*   **⚡ Lightweight:** Pure JavaScript, no jQuery. Fast and efficient.
*   **🔍 Interactive:** Built-in search and sorting for easy data navigation.
*   **🔧 Flexible Layouts:** Choose between Grid, Masonry, or List view.

### 🚀 Key Features

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

## Installation

1.  Upload the `cardcrafter` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to the **CardCrafter** admin menu.
4.  Paste your JSON URL or try the demo data.
5.  Copy the shortcode and add it to any page.

## Usage

**Basic Shortcode:**
```
[cardcrafter-data-grids source="https://example.com/team.json"]
```

**With Options:**
```
[cardcrafter-data-grids source="https://example.com/products.json" layout="masonry" columns="4"]
```

**Shortcode Attributes:**

| Attribute | Description | Default |
|-----------|-------------|---------|
| `source` | URL of your JSON data (required) | - |
| `layout` | "grid", "masonry", or "list" | grid |
| `columns` | 2, 3, or 4 | 3 |
| `image_field` | JSON field for image | image |
| `title_field` | JSON field for title | title |
| `subtitle_field` | JSON field for subtitle | subtitle |
| `description_field` | JSON field for description | description |
| `link_field` | JSON field for link | link |

## Screenshots

### 1. Admin Dashboard
The CardCrafter settings panel with live preview functionality. Select layout options and see your cards render instantly.

![Admin Dashboard](screenshot-1.png)

### 2. Grid Layout
Beautiful responsive card grid displaying team members or products.

![Grid Layout](screenshot-2.png)

## Frequently Asked Questions

### What JSON format does CardCrafter expect?
CardCrafter works with arrays of objects. Each object should have fields like image, title, subtitle, description, and link. You can customize field names using shortcode attributes.

### Can I customize the card design?
Yes! CardCrafter uses CSS variables that you can override in your theme's custom CSS. All cards have clear class names for styling.

### Does this work with any API?
CardCrafter works with any publicly accessible JSON endpoint. The API must allow CORS requests from your domain.

## Changelog

### 1.2.0
*   Feature: Added interactive Search Toolbar (Client-side filtering).
*   Feature: Added Sorting functionality (A-Z, Z-A).
*   Design: Added modern styles for search inputs and dropdowns.

### 1.1.4
*   Security: Implemented rate limiting (30 requests/minute) on the AJAX proxy to prevent abuse.
*   Security: Added robust client identification handling for proxies (Cloudflare, X-Forwarded-For).
*   Improvement: Returns standard HTTP 429 response when rate limit is exceeded.

### 1.1.3
*   Refactor: Updated all function prefixes to `cardcrafter_` for compliance.
*   Security: Implemented `wp_unslash` and proper sanitization orders for all input processing.
*   Compliance: Renamed text-domain and slug to `cardcrafter-data-grids` to avoid restricted terms.
*   Compatibility: Upgraded to `wp_parse_url` and verified testing up to WordPress 6.9.
*   Maintenance: Optimized tags and metadata for official directory submission.

### 1.1.0
*   Feature: Added "Secure Data Proxy" - fetch data from any API regardless of CORS settings.
*   Performance: Implemented SWR (Stale-While-Revalidate) caching via transients for blazing-fast load times.
*   Resilience: Added intelligent "Error Diagnostics" and a "Retry" mechanism for failed data fetches.
*   Security: Enforced strict output escaping and SSRF protection to meet WordPress.org directory standards.
*   Maintenance: Updated Core Library to v1.1.0.

### 1.0.0
*   Initial release.
*   Grid, Masonry, and List layouts.
*   Live admin preview.
*   Customizable field mapping.
