# Frontend API Reference

## Base URL

```
http://your-domain.com/api
```

## Response Format

All API responses follow a consistent format:

```json
{
  "status": true,
  "message": "Operation message",
  "data": { ... }           // Object or array
}
```

On error:
```json
{
  "status": false,
  "message": "Error description",
  "data": null
}
```

---

## Authentication

### Login
```
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Response:
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

### Register
```
POST /register
Content-Type: application/json

{
  "name": "John",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### Authenticated Requests
Include token in header:
```
Authorization: Bearer 1|abc123...
```

### Get User
```
GET /user
Authorization: Bearer {token}
```

### Logout
```
GET /logout
Authorization: Bearer {token}
```

---

## Products

All product endpoints return the same data structure.

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/products` | List all products |
| GET | `/products/search?q=term` | Search/filter products |
| GET | `/products/{id or slug}` | Single product detail |

### Product Search Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `q` or `search` | string | Search by name, description, SKU, attribute value |
| `category_id` | int | Filter by category |
| `min_price` | number | Minimum price filter |
| `max_price` | number | Maximum price filter |
| `in_stock` | bool | `true` to filter in-stock products only |
| `sort_by` | string | `name`, `price`, `created_at`, `stock` (default: `created_at`) |
| `sort_order` | string | `asc` or `desc` (default: `desc`) |
| `per_page` | int | Items per page (default: 12, max: 100) |

### Product Response Structure

```json
{
  "id": 1,
  "name": "V-Neck T-Shirt",
  "slug": "v-neck-t-shirt",
  "description": "Full product description...",
  "short_description": "Brief description",
  "price": "29.99",
  "offer_price": "19.99",
  "stock": 50,
  "status": "active",              // "active" | "draft" | "discontinued"
  "category_id": 2,
  "created_at": "2026-06-18T12:00:00.000000Z",
  "updated_at": "2026-06-18T12:00:00.000000Z",

  "category": {
    "id": 2,
    "name": "T-Shirts",
    "slug": "t-shirts",
    "description": "...",
    "image": "images/category.jpg",
    "status": "active"
  },

  "images": [
    {
      "id": 1,
      "product_id": 1,
      "url": "images/product-front.jpg",
      "is_featured": true,
      "created_at": "...",
      "updated_at": "..."
    }
  ],

  "variants": [
    {
      "id": 5,
      "product_id": 1,
      "sku": "TS-RED-M",
      "price": "29.99",
      "offer_price": "19.99",
      "stock": 15,
      "created_at": "...",
      "updated_at": "...",

      "images": [
        {
          "id": 10,
          "variant_id": 5,
          "url": "images/tshirt-red-front.jpg",
          "is_featured": true,
          "created_at": "...",
          "updated_at": "..."
        }
      ],

      "attributes": [
        {
          "id": 1,
          "variant_id": 5,
          "attribute": "Color",
          "value": "Red",
          "created_at": "...",
          "updated_at": "..."
        }
      ]
    }
  ]
}
```

### Key Product Fields

| Field | Type | Notes |
|-------|------|-------|
| `id` | int | |
| `name` | string | |
| `slug` | string | URL-friendly unique identifier |
| `price` | string (decimal) | Full/regular price |
| `offer_price` | string (decimal) | Discounted price; `null` if no discount |
| `stock` | int | Main stock (used when no variants) |
| `status` | string | `"active"`, `"draft"`, or `"discontinued"` |
| `images[]` | array | Product-level images (not variant-specific) |
| `images[].url` | string | Relative path (prefix with base URL) |
| `images[].is_featured` | bool | Only one should be `true` |
| `variants[]` | array | Empty if single-variant product |
| `variants[].sku` | string | Stock keeping unit |
| `variants[].stock` | int | Variant-specific stock |
| `variants[].attributes[]` | array | e.g. Color: Red, Size: M |
| `variants[].attributes[].attribute` | string | Attribute name |
| `variants[].attributes[].value` | string | Attribute value |

### Display Rules

- If `variants` is empty → product has no variants; use `price`/`offer_price`/`stock` from the product level.
- If `variants` has items → product has variants; user must select one. Use the variant's `price`/`offer_price`/`stock`. Hide product-level pricing.
- Show the **featured image** (`is_featured: true`). If none is marked, show the first image.
- For variants with their own images, prefer variant images over product images when a variant is selected.
- Status `"draft"` = hidden from customers (admin only). `"discontinued"` = no longer sold.

### Image URL Handling

Images are stored as relative paths (e.g., `images/abc.jpg`). Prepend the site base URL:
```
https://your-domain.com/images/abc.jpg
```

---

## Cart (auth required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cart` | List cart items |
| POST | `/cart/add` | Add item |
| POST | `/cart/update` | Update quantity |
| DELETE | `/cart/delete/{id}` | Remove item |
| POST | `/cart/toggle-selection` | Select/deselect item |
| POST | `/cart/select-all` | Select all |
| POST | `/cart/deselect-all` | Deselect all |

### POST `/cart/add`

```json
{
  "product_id": 1,
  "variant_id": 5,
  "quantity": 2
}
```

- `variant_id` is required when the product has variants, optional otherwise.
- `quantity` defaults to 1 if omitted.

### POST `/cart/update`

```json
{
  "cart_id": 10,
  "quantity": 3
}
```

### POST `/cart/toggle-selection`

```json
{
  "cart_id": 10,
  "is_selected": true
}
```

---

## Orders (auth required)

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/orders` | List user's orders |
| POST | `/orders/add` | Create order from **selected** cart items |
| POST | `/orders/direct` | Buy now (skips cart, items specified inline) |

### POST Request Body

Fields can be **nested** (recommended) or **flat** (backward compatible).

```json
{
  "payment_method": "cod",

  "customer": {
    "name": "John",
    "email": "john@example.com",
    "phone": "123456789",
    "phone_alt": ""
  },

  "shipping_address": {
    "line1": "123 Main St",
    "line2": "Apt 4B",
    "district": "Central",
    "city": "Dhaka",
    "country": "Bangladesh"
  },

  "pricing": {
    "currency": "BDT",
    "delivery_charge": 5.00,
    "coupon_code": "DISCOUNT10",
    "discount": 10,
    "discount_type": "fixed"
  },

  "shipping": {
    "method": "standard",
    "estimated_delivery_days": 3
  },

  "notes": "Deliver in the evening"
}
```

**Flat fallback** (also accepted):
```json
{
  "payment_method": "cod",
  "name": "John",
  "email": "john@example.com",
  "phone": "123456789",
  "line1": "123 Main St",
  "city": "Dhaka",
  "country": "Bangladesh",
  "delivery_charge": 5.00,
  "coupon": "DISCOUNT10",
  "notes": ""
}
```

### POST `/orders/direct` — Extra `items` Field

```json
{
  "payment_method": "cod",
  "items": [
    { "product_id": 1, "variant_id": 5, "quantity": 2 }
  ],
  ... other order fields ...
}
```

`items` can also be a single object (not array):
```json
{
  "items": { "product_id": 1, "variant_id": 5, "quantity": 2 }
}
```

### Order Response Structure

```json
{
  "status": true,
  "message": "Order placed successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20260620-0001",
    "customer_id": 3,
    "status": "pending",
    "payment_method": "cod",
    "payment_status": "pending",

    "customer": {
      "name": "John",
      "email": "john@example.com",
      "phone": "123456789",
      "phone_alt": ""
    },

    "shipping_address": {
      "line1": "123 Main St",
      "line2": "Apt 4B",
      "district": "Central",
      "city": "Dhaka",
      "country": "Bangladesh"
    },

    "pricing": {
      "currency": "BDT",
      "subtotal": 59.98,
      "delivery_charge": 5.00,
      "discount": 10.00,
      "discount_type": "fixed",
      "coupon_code": "DISCOUNT10",
      "total": 54.98
    },

    "shipping": {
      "method": "standard",
      "estimated_delivery_days": 3
    },

    "summary": {
      "total_items": 2,
      "total_quantity": 3
    },

    "items": [
      {
        "id": 1,
        "product_id": 1,
        "variant_id": 5,
        "name": "V-Neck T-Shirt",
        "slug": "v-neck-t-shirt",
        "sku": "TS-RED-M",
        "image": "images/tshirt-red-front.jpg",
        "attributes": [
          { "attribute": "Color", "value": "Red" },
          { "attribute": "Size", "value": "Medium" }
        ],
        "original_price": 29.99,
        "unit_price": 19.99,
        "discount": 10.00,
        "quantity": 2,
        "line_total": 39.98,
        "stock_snapshot": {
          "variant_id": 5,
          "stock": 15
        }
      }
    ],

    "status_history": [
      {
        "status": "pending",
        "label": "Order Placed",
        "note": null,
        "created_by": "customer",
        "created_at": "2026-06-20T12:00:00+00:00"
      }
    ],

    "notes": null,
    "created_at": "2026-06-20T12:00:00+00:00",
    "updated_at": "2026-06-20T12:00:00+00:00"
  }
}
```

### Order Status Values

| Status | Label | Description |
|--------|-------|-------------|
| `pending` | Order Placed | Initial state |
| `confirmed` | Order Confirmed | Admin confirmed payment |
| `processing` | Processing | Being prepared |
| `shipped` | Shipped | Out for delivery |
| `delivered` | Delivered | Received by customer |
| `cancelled` | Cancelled | Order cancelled |
| `returned` | Returned | Customer returned |
| `refunded` | Refunded | Money refunded |

### Key Order Fields Explained

| Field | Notes |
|-------|-------|
| `order_number` | Auto-generated as `ORD-YYYYMMDD-NNNN` |
| `customer_id` | User ID who placed the order |
| `status` | Current order status string |
| `items[].name` / `slug` / `sku` / `image` | **Denormalized** — copied from product/variant at time of order |
| `items[].attributes` | **Snapshot** of variant attributes at time of order |
| `items[].stock_snapshot` | **Snapshot** of variant stock at time of order |
| `items[].line_total` | `unit_price × quantity` |
| `items[].original_price` | Product/variant full price |
| `items[].unit_price` | Price actually charged per unit |
| `pricing.subtotal` | Sum of all `line_total` |
| `pricing.delivery_charge` | From the order request |
| `pricing.discount` | Coupon/flat/percentage discount |
| `pricing.total` | `subtotal + delivery_charge - discount` |
| `status_history` | Chronological log of status changes |

---

## Delivery Charges (public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/delivery-charges` | List available delivery charges |

### Response

```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "Kushtia",
      "charge": "70.00",
      "minimum_order": "30.00",
      "status": true,
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

| Field | Type | Notes |
|-------|------|-------|
| `name` | string | Location/area name |
| `charge` | string (decimal) | Delivery fee |
| `minimum_order` | string (decimal) | Minimum order subtotal to use this option; `null` = no minimum |
| `status` | bool | `true` = available |

---

## Coupon Verification (auth required)

```
POST /verify-coupon
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "DISCOUNT10"
}
```

Response:
```json
{
  "status": true,
  "data": {
    "id": 1,
    "code": "DISCOUNT10",
    "discount": "10",
    "discount_type": "fixed",
    "is_active": true
  }
}
```

---

## Wishlist (auth required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wishlist` | List wishlist items |
| POST | `/wishlist/add` | Add item (body: `{ "product_id": 1, "variant_id": 5 }`) |
| DELETE | `/wishlist/delete/{id}` | Remove item |

---

## Categories (public)

| Method | Endpoint |
|--------|----------|
| GET | `/categories` |

---

## Banners (public)

| Method | Endpoint |
|--------|----------|
| GET | `/banners` |

---

## Blogs (public)

| Method | Endpoint |
|--------|----------|
| GET | `/blogs` |
| GET | `/blogs/{id}` |
| GET | `/blog-categories` |
| GET | `/blog-tags` |

---

## Admin Endpoints

All admin endpoints require `auth:sanctum` + `admin` middleware.

### Admin Auth

Use the same login endpoint. The user must have `is_admin = 1` in the database. The admin check is done via the `admin` middleware.

### Admin — Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/products/{slug}` | Get product by slug (admin view) |
| POST | `/admin/products/add` | Add product (multipart or JSON) |
| POST | `/admin/products/update` | Update product |
| DELETE | `/admin/products/delete/{id}` | Delete product |
| DELETE | `/admin/products/{productId}/images/{imageId}` | Delete product image |
| POST | `/admin/products/{id}/variants` | Add variant to product |
| GET | `/admin/variants/{id}` | Get variant details |
| PUT or POST | `/admin/variants/{id}` | Update variant |
| POST | `/admin/variants/delete/{id}` | Delete variant |
| POST | `/admin/variants/{id}/images` | Add image to variant |
| DELETE | `/admin/variants/{variantId}/images/{imageId}` | Delete variant image |

### Admin — Add/Update Product

**Content-Type: multipart/form-data** (for file uploads) or **application/json** (for URLs).

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | yes | |
| `slug` | string | no | Auto-generated from name if omitted |
| `description` | string | no | |
| `short_description` | string | no | |
| `price` | number | no | Default: 0 |
| `offer_price` | number | no | |
| `stock` | integer | no | Default: 0 |
| `category_id` | integer | no | |
| `status` | string | no | `active`, `draft`, `discontinued`. Default: `active` |
| `images` | array | no | Array of image objects |
| `images.*.url` | file or string | no | Upload file or URL string |
| `images.*.is_featured` | boolean | no | Mark as primary image |
| `variants` | array | no | Array of variant objects |

**Variant object:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | no | Include to **update** existing variant; omit to create new |
| `sku` | string | no | Auto-generated if omitted |
| `price` | number | no | |
| `offer_price` | number | no | |
| `stock` | integer | no | |
| `attributes` | array | no | Array of `{ attribute, value }` objects |
| `attributes.*.attribute` | string | yes | |
| `attributes.*.value` | string | yes | |
| `images` | array | no | Array of image objects |
| `images.*.url` | file or string | no | |
| `images.*.is_featured` | boolean | no | |
| `images_to_keep` | integer[] | no | IDs of existing images to keep (omit = delete all old images) |

### Admin — Update Variant (PUT or POST `/admin/variants/{id}`)

| Field | Type | Description |
|-------|------|-------------|
| `sku` | string | |
| `price` | number | |
| `offer_price` | number | Set to empty string to clear |
| `stock` | integer | |
| `attributes` | array | Replaces all existing attributes entirely |
| `images` | array | New images to append |
| `images_to_keep` | integer[] | IDs of existing images to keep |

### Admin — Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/orders` | List all orders (with user info) |
| GET | `/admin/orders/{id}` | Get single order detail |
| POST | `/admin/orders/update` | Update order status/notes |

**POST `/admin/orders/update`:**

```json
{
  "id": 1,
  "status": "shipped",
  "status_note": "Shipped via DHL",
  "payment_status": "paid",
  "delivery_charge": 7.00,
  "shipping_method": "dhl",
  "notes": "Updated by admin"
}
```

When `status` changes, a new entry is automatically added to `status_history` with the admin's name as `created_by`.

### Admin — Delivery Charges

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/delivery-charges` | List all charges |
| POST | `/admin/delivery-charges/add` | Add charge |
| POST | `/admin/delivery-charges/update` | Update charge |
| DELETE | `/admin/delivery-charges/delete/{id}` | Delete charge |

**POST `/admin/delivery-charges/add` / `update`:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | update only | |
| `name` | string | yes | Location/area name |
| `charge` | number | yes | Delivery fee |
| `minimum_order` | number | no | Min subtotal to qualify |
| `status` | boolean | no | Default: `true` |

### Admin — Coupons, Blogs, Banners

| Method | Endpoint |
|--------|----------|
| GET | `/admin/coupon` |
| POST | `/admin/coupon/add` |
| POST | `/admin/coupon/update` |
| DELETE | `/admin/coupon/delete/{id}` |
| GET | `/admin/blogs` |
| POST | `/admin/blogs/add` |
| POST | `/admin/blogs/update` |
| DELETE | `/admin/blogs/delete/{id}` |
| GET | `/admin/banners` |
| POST | `/admin/banners/add` |
| POST | `/admin/banners/update` |
| DELETE | `/admin/banners/delete/{id}` |

---

## Image Upload Guide

### Multipart Upload — Recommended

When uploading images via form data, use the nested object structure:

```
Content-Type: multipart/form-data

name: "T-Shirt"
price: 29.99
images[0][url]: (file upload)
images[0][is_featured]: true
images[1][url]: (file upload)
images[1][is_featured]: false
```

For variants:
```
name: "T-Shirt"
variants[0][sku]: "TS-RED-M"
variants[0][price]: 29.99
variants[0][attributes][0][attribute]: "Color"
variants[0][attributes][0][value]: "Red"
variants[0][images][0][url]: (file upload)
variants[0][images][0][is_featured]: true
```

### JSON Upload — URLs Only

When passing existing URL strings (e.g., from a previous state or edit), use JSON:

```json
{
  "name": "T-Shirt",
  "images": [
    { "url": "https://example.com/image.jpg", "is_featured": true }
  ]
}
```

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Validation error / Bad request |
| 401 | Unauthenticated |
| 403 | Unauthorized (non-admin accessing admin routes) |
| 404 | Resource not found |
| 422 | Validation errors |

---

## Error Response Examples

**Validation error (422):**
```json
{
  "status": false,
  "message": "The name field is required. (and 2 more errors)",
  "data": null
}
```

**Auth error (401):**
```json
{
  "status": false,
  "message": "Unauthenticated.",
  "data": null
}
```
