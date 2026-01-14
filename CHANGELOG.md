# Changelog

All notable changes to CardCrafter will be documented in this file.

## [1.1.4] - 2026-01-14
### Security
- **Rate Limiting:** Implemented transient-based rate limiting (30 req/min) on the AJAX proxy endpoint.
- **Abuse Prevention:** Added client identification via user ID or IP address (Cloudflare/Proxy aware).
- **Error Handling:** Returns HTTP 429 status code when rate limit is exceeded.

## [1.1.3] - 2026-01-06
### Fixed
- **Security:** Added `wp_unslash` to all user input processing and fixed nonce verification order.
- **Compliance:** Renamed slug and text domain to `cardcrafter-wp-grid-layouts-view` to avoid restricted terms.
- **Compatibility:** Upgraded `parse_url` to `wp_parse_url` for better PHP version consistency.
- **Maintenance:** Updated "Tested up to" to WordPress 6.9 and optimized tag count for directory submission.

## [1.1.0] - 2026-01-06
### Added
- **Security:** Implemented strict output escaping and SSRF protection to meet WordPress.org directory standards.
- **Proxy:** Added a "Secure Data Proxy" to handle remote requests, bypassing CORS issues and protecting user data.
- **Performance:** Implemented SWR (Stale-While-Revalidate) caching via WordPress transients.
- **Resilience:** Added intelligent "Error Diagnostics" and a frontend "Retry" mechanism for failed data fetches.
- **i18n:** Improved translation support with a dedicated text domain.

## [1.0.0] - 2025-12-30
### Added
- Initial release.
- Grid, Masonry, and List layouts.
- Live admin preview.
- Customizable field mapping.
