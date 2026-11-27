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

### Event Tracking
- **Session Tracking**: Automatic session/customer mapping with unique Uduny tokens
- **Add to Cart Events**: Track when products are added to cart
- **Purchase Events**: Track completed orders with full order details
- **Debug Logging**: Built-in debug logging with admin viewer

## Requirements

- Magento 2.4.x
- PHP 8.1 or higher

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

### Composer Installation (if packaged)

```bash
composer require unusualdope/module-uduny
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
| Export Image Width | Width of product images in export (pixels) |
| Export Image Height | Height of product images in export (pixels) |

## Usage

### Creating Export Lists

1. Go to **Admin → Uduny → Export Lists**
2. Click **Add New Export List**
3. Configure:
   - **Name**: Internal reference name
   - **Slug**: URL identifier (used in API endpoint)
   - **Status Filter**: All / Enabled Only / Disabled Only
   - **Stock Filter**: All / In Stock / In Stock + Backorder
   - **Category IDs**: Comma-separated category IDs (empty = all)
   - **Brand IDs**: Comma-separated brand attribute IDs (empty = all)
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
  "success": true,
  "export_list": {
    "id": 1,
    "name": "All Products",
    "slug": "all-products"
  },
  "count": 150,
  "products": [
    {
      "id": 1,
      "sku": "PRODUCT-001",
      "name": "Sample Product",
      "description": "Product description",
      "short_description": "Short desc",
      "url": "https://store.com/product.html",
      "image": "https://store.com/media/catalog/product/image.jpg",
      "price": 99.99,
      "special_price": 79.99,
      "final_price": 79.99,
      "currency": "USD",
      "brand": "Brand Name",
      "categories": [2, 3, 5],
      "in_stock": true,
      "qty": 100,
      "meta_title": "Product Meta Title",
      "meta_description": "Product meta description",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-12-09 14:22:00"
    }
  ]
}
```

#### Error Responses

| Code | Error | Description |
|------|-------|-------------|
| 400 | Missing slug parameter | No slug in URL |
| 401 | Invalid token | Wrong or missing authentication token |
| 401 | IP not allowed | Client IP not in whitelist |
| 403 | Export list is disabled | Export list is_active = false |
| 404 | Export list not found | Invalid slug |
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

