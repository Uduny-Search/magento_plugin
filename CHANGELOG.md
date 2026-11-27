# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-11-26

### Added
- **Uduny JSON Feed Format (`{ "items": [...] }`)**
  - Standard single-currency feed (Option 1: omits `prices` array automatically when store has a single currency and location)
  - Multi-currency & multi-store/website `prices` array (`[{ location, currency, price, sale }]`) with automatic detection, exchange-rate conversion, website-scoped indexed pricing (`catalog_product_index_price`), and date-validated `special_price` support
  - Admin **Export & Multi-Currency Settings** (`pricelist_mode`: `auto` / `always` / `never`, plus optional `currency_location_map` overrides) supporting Option 1, Option 2 (1 currency / 1 location array), and full multi-currency/multi-location stores
  - Configurable product `variants` export with `variant_id`, `name`, `price`, `sale`, `quantity`, `image`, variant `url`, `combinations`, and per-variant `prices`
  - Category name resolution, `brands` array, and descriptive user-defined `attributes` object
- **Storefront UdSearch Widget Embed**
  - Renders `window.UdunyStoreContext` (`{ country, currency }`), `window.UdunySearch` (`{ url, lang, trigger_elements }`), and deferred `search.js` script in sync with the export pricelist mode and Magento's active visitor currency
  - New Admin Frontend Settings: `search_url`, `trigger_clicked`, and `trigger_typing`
  - `Tracking::getDirectEmbedUrl()` helper for Method 2 direct iframe URLs
- **Magento 2.3.7 – 2.4.x Compatibility**
  - Lowered PHP requirement to `>=7.4` and Magento module bounds to support Magento `2.3.7` through `2.4.9` / Mage-OS `1.x`
  - Added Magento 2.3 fallback for `$escaper` and `$secureRenderer` in `tracking.phtml`

### Fixed
- Moved custom frontend router registration (`UnusualDope\Uduny\Controller\Router`) from global `etc/di.xml` to `etc/frontend/di.xml` with re-entry guards so clean `/uduny/export/{slug}` URLs resolve instead of returning HTTP 404
- Removed direct `<head><script src="UnusualDope_Uduny::js/session-tracking.js"/>` tag from `view/frontend/layout/default.xml` (fixing RequireJS `Mismatched anonymous define()` errors) and updated `session-tracking.js` to return a callable initializer for `text/x-magento-init`
- Fixed overlapping `Include Category IDs` and `Category Filters` labels on the Export List Admin form (`uduny_exportlist_form.xml`) and upgraded `Include Categories` / `Exclude Categories` to Magento's expandable Category Tree `ui-select` component and `Brands / Manufacturers` to a searchable multiselect `ui-select` component

---

## [1.1.0] - 2024-12-11

### Added
- **Event Tracking System**
  - Session tracking with unique Uduny tokens
  - Frontend JavaScript for automatic session registration
  - Session/customer mapping in `uduny_session_tracking` table
  - Add to cart event observer (`AddToCartObserver`)
  - Purchase/order event observer (`OrderPlaceObserver`)
  - Session tracking endpoint: `POST /uduny/track/session`

- **Category Exclusion Filter**
  - New "Exclude Category IDs" field in Export Lists
  - Products belonging to excluded categories are filtered from export
  - Works alongside existing category inclusion filter

### Changed
- Updated admin form to display category filters side-by-side
- Enhanced `ProductExporter` to support category exclusion

### Database
- New table: `uduny_session_tracking`
  - `session_id`: Magento PHP session ID
  - `customer_id`: Customer ID (null for guests)
  - `uduny_token`: Unique tracking token
  - `store_id`: Store view ID
  - `created_at` / `updated_at`: Timestamps
- New column in `uduny_export_list`: `exclude_category_ids`

---

## [1.0.0] - 2024-12-09

### Added
- Initial release of Uduny module for Magento 2
- Export Lists management with admin CRUD interface
- Product export API endpoint (`/uduny/export/{slug}`)
- Configurable product filters:
  - Product status (all/enabled/disabled)
  - Stock availability (all/in stock/in stock + backorder)
  - Category filtering
  - Brand/manufacturer filtering
- Authentication system:
  - Master token validation
  - IP whitelist support
- Admin configuration panel:
  - Module enable/disable
  - Master token setting
  - Server whitelist
  - Production/Test mode toggle
  - Debug mode toggle
  - Image size configuration
- Debug logging system:
  - Daily log files in `var/log/uduny/`
  - Admin log viewer with CRUD operations
- Multi-store support
- Custom URL router for clean API URLs

### Security
- Token-based API authentication
- IP whitelist for access control
- CSRF protection on admin actions

## [Unreleased]

### Planned
- Frontend script injection for search widget
- Webhook support for real-time product updates
- GraphQL API support
- Product attribute mapping configuration
- Export scheduling via cron

