# UnusualDope Uduny Module for Magento 2

Uduny/Typesense Search Integration module for Magento 2. This module provides a product catalog export API that can be consumed by external search services like Typesense.

## Features

### Product Export
- **Export Lists Management**: Create multiple export configurations with different product filters
- **REST API Endpoint**: JSON API for product catalog export
- **Flexible Filtering**: Filter products by status, stock, categories, and brands
- **Category Exclusion**: Exclude products from specific categories
- **Authentication**: Token-based authentication with IP whitelist support
- **Multi-store Support**: Configure per store view
- **Multi-Currency Pricing**: Optional `prices` array per location/currency, with exchange-rate conversion and website-scoped indexed prices
- **Configurable Products**: Variants exported with their own price, stock, image, URL and option combinations

### Storefront Search Widget
- **UdSearch Embed**: Injects the Uduny search script plus `window.UdunySearch` and `window.UdunyStoreContext` (country/currency)
- **Trigger Selectors**: Configure which elements open the search on click or typing

### Event Tracking
- **Session Tracking**: Automatic session/customer mapping with unique Uduny tokens
- **Add to Cart Events**: Track when products are added to cart
- **Purchase Events**: Track completed orders with full order details
- **Debug Logging**: Built-in debug logging with admin viewer

## Requirements

- Magento 2.3.7 – 2.4.x (Mage-OS 1.x supported)
- PHP 7.4 or higher

## Installation

### Manual Installation

1. Create the directory structure:
```bash
mkdir -p app/code/UnusualDope/Uduny
```

2. Copy the module files to `app/code/UnusualDope/Uduny/`

3. Enable the module:
```bash
bin/magento module:enable UnusualDope_Uduny
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Composer Installation (from GitHub)

```bash
composer config repositories.uduny vcs https://github.com/Uduny-Search/magento_plugin.git
composer require unusualdope/module-uduny:dev-main
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuration

Navigate to **Stores → Configuration → UnusualDope → Uduny Settings**

### General Settings

| Setting | Description |
|---------|-------------|
| Enable Module | Enable/disable the module |
| Master Token | Authentication token for API requests |
| Server Whitelist | IP addresses allowed to access the API (one per line, empty = allow all) |
| Production Mode | When enabled, frontend script loads on all pages |
| Debug Mode | Enable logging to `var/log/uduny/` |

### Frontend Settings

| Setting | Description |
|---------|-------------|
| Script URL | URL of the Uduny search JavaScript to embed |
| UdSearch Base URL | Search app base URL (optional; derived from Script URL if empty) |
| Click Trigger Selectors | Comma-separated CSS selectors that open search on click |
| Typing Trigger Selectors | Comma-separated CSS selectors that open search on typing (default `#search`) |
| Export Image Width | Width of product images in export (pixels, default 300) |
| Export Image Height | Height of product images in export (pixels, default 300) |

### Export & Multi-Currency Settings

| Setting | Description |
|---------|-------------|
| Multi-Price / Multi-Currency Export | `Auto-detect` (default): add `prices` only for multi-currency/multi-store setups. `Always`: always add `prices`. `Disabled`: never add `prices` |
| Currency to Location Overrides | Optional `CURRENCY=COUNTRY` lines (e.g. `EUR=IT`, `USD=US`) for extra currencies on one store view |

## Usage

### Creating Export Lists

1. Go to **Admin → Uduny → Export Lists**
2. Click **Add New Export List**
3. Configure:
   - **Name**: Internal reference name
   - **Slug**: URL identifier (used in API endpoint)
   - **Status Filter**: All / Enabled Only / Disabled Only
   - **Stock Filter**: All / In Stock / In Stock + Backorder
   - **Include Categories**: Pick categories from the tree (empty = all)
   - **Exclude Categories**: Products in these categories are left out
   - **Brands / Manufacturers**: Searchable multiselect (empty = all)
   - **Store View**: Which store to export from
   - **Active**: Enable/disable this export list

### API Endpoint

#### URL Format
```
GET /uduny/export/{slug}
```

#### Authentication

Include token via:
- Query parameter: `?token=your-token`
- Header: `X-Uduny-Token: your-token`
- Bearer header: `Authorization: Bearer your-token`

#### Example Request
```bash
curl -X GET "https://your-store.com/uduny/export/all-products?token=your-token"
```

#### Example Response
```json
{
  "items": [
    {
      "id": "1",
      "reference": "PRODUCT-001",
      "name": "Sample Product",
      "url": "https://store.com/product.html",
      "image": "https://store.com/media/catalog/product/image.jpg",
      "description": "Product description",
      "date_created": "2024-01-15 10:30:00",
      "price": 99.99,
      "sale_price": 79.99,
      "quantity": 100,
      "categories": ["Men", "T-Shirts"],
      "brands": ["Brand Name"],
      "attributes": { "material": "Cotton" },
      "prices": [
        { "location": "US", "currency": "USD", "price": 99.99, "sale": 79.99 },
        { "location": "IT", "currency": "EUR", "price": 92.50, "sale": 73.90 }
      ],
      "variants": [
        {
          "variant_id": "12",
          "name": "Sample Product - Blue / M",
          "price": 99.99,
          "sale": 79.99,
          "quantity": 20,
          "image": "https://store.com/media/catalog/product/blue.jpg",
          "url": "https://store.com/product.html?color=Blue&size=M",
          "combinations": [
            { "group": "Color", "value": "Blue" },
            { "group": "Size", "value": "M" }
          ]
        }
      ]
    }
  ]
}
```

Optional fields:
- `attributes` is included only when the product has descriptive user-defined attributes.
- `prices` follows the **Multi-Price / Multi-Currency Export** setting. Variants get their own `prices` too.
- `variants` is included only for configurable products.

#### Error Responses

| Code | Error | Description |
|------|-------|-------------|
| 400 | Missing slug parameter | No slug in URL |
| 401 | Invalid token | Wrong or missing authentication token |
| 401 | IP not allowed | Client IP not in whitelist |
| 403 | Export list is disabled | Export list is_active = false |
| 404 | Export list not found | Invalid slug |
| 500 | Internal server error | Unexpected error (details in debug log) |
| 503 | Module is disabled | Module not enabled in config |

### Debug Logs

View logs at **Admin → Uduny → Debug Logs**

Features:
- View all log files
- Read log contents
- Delete individual logs
- Clear all logs

Log files are stored in `var/log/uduny/` with daily rotation.

---

## Event Tracking

The module automatically tracks customer behavior and sends events to Uduny.

### Session Tracking

Sessions are automatically tracked on every page load via JavaScript.

#### How it works:
1. JavaScript runs on page load
2. Calls `/uduny/track/session` endpoint
3. Creates/retrieves unique `uduny_token`
4. Stores token in `sessionStorage`
5. Links to `customer_id` when logged in

#### Database Table: `uduny_session_tracking`

| Column | Description |
|--------|-------------|
| `session_id` | Magento PHP session ID |
| `customer_id` | Customer ID (null for guests) |
| `uduny_token` | Unique Uduny tracking token |
| `store_id` | Store view ID |
| `created_at` | Session creation time |
| `updated_at` | Last activity time |

#### Session Endpoint

```
POST /uduny/track/session
```

**Response:**
```json
{
  "success": true,
  "token": "uduny_1765353571_9a879d73",
  "is_logged_in": false
}
```

### Add to Cart Event

Triggered when a product is added to cart.

**Magento Event:** `checkout_cart_add_product_complete`

**Data Captured:**
```json
{
  "event": "add_to_cart",
  "product_id": 123,
  "product_name": "Blue T-Shirt",
  "quantity": 2,
  "price": 29.99,
  "user_session": "uduny_1765353571_9a879d73"
}
```

### Purchase Event

Triggered when an order is successfully placed.

**Magento Event:** `checkout_onepage_controller_success_action`

**Data Captured:**
```json
{
  "event": "purchase",
  "order_id": "100000123",
  "total_amount": 159.97,
  "total_quantity": 5,
  "data": [
    {
      "product_id": 123,
      "product_name": "Blue T-Shirt",
      "quantity": 2,
      "price": 29.99
    },
    {
      "product_id": 456,
      "product_name": "Black Jeans",
      "quantity": 3,
      "price": 33.33
    }
  ],
  "user_session": "uduny_1765353571_9a879d73"
}
```

### Event Flow Diagram

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Page Load   │───▶│  Session JS  │───▶│  Get Token   │
└──────────────┘    └──────────────┘    └──────────────┘
                                               │
                                               ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Add to Cart │───▶│  Observer    │───▶│  Log Event   │
└──────────────┘    └──────────────┘    └──────────────┘
                                               │
                                               ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ Place Order  │───▶│  Observer    │───▶│  Log Event   │
└──────────────┘    └──────────────┘    └──────────────┘
```

## Module Structure

```
app/code/UnusualDope/Uduny/
├── Api/
│   ├── Data/
│   │   ├── ExportListInterface.php
│   │   └── SessionTrackingInterface.php
│   ├── ExportListRepositoryInterface.php
│   └── SessionTrackingRepositoryInterface.php
├── Block/
│   ├── Adminhtml/
│   │   ├── Debug.php
│   │   └── ExportList/Edit/
│   └── Tracking.php
├── Controller/
│   ├── Adminhtml/
│   │   ├── Debug/
│   │   └── Exportlist/
│   ├── Export/
│   │   └── Index.php
│   ├── Track/
│   │   └── Session.php
│   ├── Router.php
│   └── Test/
├── Model/
│   ├── Config.php
│   ├── ExportList.php
│   ├── ExportListRepository.php
│   ├── Logger.php
│   ├── ProductExporter.php
│   ├── SessionTracking.php
│   ├── SessionTrackingRepository.php
│   └── ResourceModel/
│       ├── ExportList/
│       ├── SessionTracking/
│       ├── ExportList.php
│       └── SessionTracking.php
├── Observer/
│   ├── AddToCartObserver.php
│   └── OrderPlaceObserver.php
├── Ui/
│   ├── Component/
│   ├── DataProvider/
│   └── Source/
├── etc/
│   ├── adminhtml/
│   ├── frontend/
│   │   └── events.xml
│   ├── acl.xml
│   ├── config.xml
│   ├── db_schema.xml
│   ├── di.xml
│   └── module.xml
├── view/
│   ├── adminhtml/
│   └── frontend/
│       ├── layout/
│       │   └── default.xml
│       ├── templates/
│       │   └── tracking.phtml
│       ├── web/js/
│       │   └── session-tracking.js
│       └── requirejs-config.js
├── composer.json
├── README.md
└── registration.php
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full details.

### 1.2.0
- Uduny JSON feed format (`{ "items": [...] }`) with multi-currency `prices` and configurable `variants`
- Storefront UdSearch widget embed with click/typing trigger settings
- Magento 2.3.7 – 2.4.x and PHP 7.4+ compatibility
- Fixed frontend router 404s, RequireJS errors and export list form layout

### 1.1.0
- **Event Tracking**
  - Session tracking with unique Uduny tokens
  - Add to cart event observer
  - Purchase/order event observer
  - Frontend JavaScript for session registration
- **Category Exclusion Filter**
  - New field to exclude products by category
  - Products in excluded categories are filtered from export
- **Database**
  - New table: `uduny_session_tracking`
  - New column: `exclude_category_ids` in `uduny_export_list`

### 1.0.0
- Initial release
- Export Lists CRUD management
- Product export API endpoint
- Token & IP authentication
- Debug logging system
- Admin configuration

## License

Proprietary - UnusualDope

## Support

For support, contact dev@unusualdope.com

