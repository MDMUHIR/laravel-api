# Product & Variant API Reference

**Base URL:** `/api`

**Auth:** Admin routes require `Authorization: Bearer {token}` header + user type `admin`.

**Response Format:**
```json
{ "status": true/false, "message": "...", "data": ... }
```

---

## Public Endpoints (no auth required)

### GET `/products`
Fetch all products with category, images, and variants.
```
GET /api/products
```

### GET `/products/search?q={term}&category_id={id}&min_price={}&max_price={}&in_stock={true|false}&sort_by={name|price|created_at|stock}&sort_order={asc|desc}&per_page={12}`
Search/filter products with pagination.
```
GET /api/products/search?q=shoes&category_id=1&min_price=10&max_price=100&in_stock=true&sort_by=price&sort_order=asc&per_page=20
```

### GET `/products/{id or slug}`
Fetch a single product by numeric ID or slug string. Loads category, images, variants.images.
```
GET /api/products/1
GET /api/products/nike-air-max
```

---

## Admin Endpoints (auth + admin required)

All admin routes are under `/api/admin` and require `auth:sanctum` + `admin` middleware. Include the Bearer token.

### Products

#### GET `/admin/products/{slug}`
Fetch a single product by slug for admin editing. Loads category, images, variants.images.
```
GET /api/admin/products/my-product-slug
```

#### POST `/admin/products/add`
Create a new product. Accepts **multipart/form-data**.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | yes | Product name |
| `slug` | string | no | Auto-generated from name if omitted |
| `description` | string | no |  |
| `short_description` | string | no |  |
| `price` | number | yes* | Required only if `has_variants=false` |
| `offer_price` | number | no |  |
| `stock` | integer | yes* | Required only if `has_variants=false` |
| `category_id` | integer | yes |  |
| `has_variants` | boolean | no | Set to `true` if product has color/size variants |
| `images[]` | file[] | no | Multiple product-level images |
| `image_colors[]` | string[] | no | Color label per image (parallel to images) |
| `variants` | array | no | Array of variant objects (see below) |
| `variants.*.sku` | string | no | Auto-generated if omitted |
| `variants.*.color` | string | no | e.g. "Red" |
| `variants.*.color_code` | string | no | e.g. "#FF0000" |
| `variants.*.price` | number | no |  |
| `variants.*.offer_price` | number | no |  |
| `variants.*.stock` | integer | no |  |
| `variants.*.images[]` | file[] | no | Images for this specific variant |

**Two modes:**

1. **Simple product** (`has_variants=false` or omitted):
   Provide `price`, `stock`, and optional `images[]`, `offer_price`.

2. **Variable product** (`has_variants=true`):
   Set `price=0`, `stock=0`, and send `variants` array. Each variant gets its own price/stock/images. The first variant becomes the `default_variant_id`.

#### POST `/admin/products/update`
Update an existing product. Accepts **multipart/form-data**.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | integer | yes | ID of product to update |
| `name` | string | yes |  |
| `slug` | string | no |  |
| `description` | string | no |  |
| `short_description` | string | no |  |
| `price` | number | yes* | Required if `has_variants=false` |
| `offer_price` | number | no |  |
| `stock` | integer | yes* | Required if `has_variants=false` |
| `category_id` | integer | yes |  |
| `has_variants` | boolean | no |  |
| `images[]` | file[] | no | New images to append |
| `image_colors[]` | string[] | no |  |
| `variants` | array | no | Full list of variants (see sync logic) |
| `default_variant_id` | integer | no | Which variant is the default |

**Variant sync logic during update:**
- If a variant object has an `id` field → that existing variant is **updated**.
- If a variant object has **no** `id` → a **new** variant is created.
- Any existing variant IDs **not** present in the `variants` array are **deleted** (along with their images).
- So you must send the **complete** list of desired variants every time.

#### DELETE `/admin/products/delete/{id}`
Deletes product + all its images + all its variants + variant images + old single image.

#### DELETE `/admin/products/{productId}/images/{imageId}`
Delete a single product-level image.

---

### Variants

#### GET `/admin/variants/{id}`
Fetch a single variant. Loads `product` and `images` relations.
```
GET /api/admin/variants/5
```

**Response example:**
```json
{
  "status": true,
  "message": "Variant retrieved successfully",
  "data": {
    "id": 5,
    "product_id": 1,
    "sku": "NIKE-RED-1",
    "color": "Red",
    "color_code": "#FF0000",
    "price": 99.99,
    "offer_price": 79.99,
    "stock": 50,
    "created_at": "...",
    "updated_at": "...",
    "product": { ... },
    "images": [
      { "id": 1, "variant_id": 5, "image_path": "images/..." }
    ]
  }
}
```

#### POST `/admin/products/{id}/variants`
Add a new variant to a product. Accepts **multipart/form-data**.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `sku` | string | no | Auto-generated if omitted |
| `color` | string | no |  |
| `color_code` | string | no |  |
| `price` | number | no | Defaults to 0 |
| `offer_price` | number | no |  |
| `stock` | integer | no | Defaults to 0 |
| `images[]` | file[] | no | Images for this variant |

#### PUT or POST `/admin/variants/{id}`
Update an existing variant. Accepts **multipart/form-data**.

| Field | Type | Description |
|-------|------|-------------|
| `sku` | string |  |
| `color` | string |  |
| `color_code` | string |  |
| `price` | number |  |
| `offer_price` | number |  |
| `stock` | integer |  |
| `images[]` | file[] | New images to append |

#### POST `/admin/variants/delete/{id}`
Delete a variant + its images. If it was `default_variant_id`, the next variant becomes default.

#### POST `/admin/variants/{id}/images`
Upload a single image to a variant. Accepts **multipart/form-data**.

| Field | Type | Required |
|-------|------|----------|
| `image` | file | yes |

#### DELETE `/admin/variants/{variantId}/images/{imageId}`
Delete a single variant image.

---

## Cart (auth required, any logged-in user)

All under `/api` with `auth:sanctum` middleware.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cart` | Get user's cart (with product & variant) |
| POST | `/cart/add` | Add item to cart |
| POST | `/cart/update` | Update cart item |
| DELETE | `/cart/delete/{id}` | Remove cart item |
| POST | `/cart/toggle-selection` | Toggle item selected state |
| POST | `/cart/select-all` | Select all items |
| POST | `/cart/deselect-all` | Deselect all items |

**POST `/cart/add` body:**
```json
{ "product_id": 1, "variant_id": 5, "quantity": 2 }
```

---

## Orders

### User (auth required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/orders` | Get user's orders (with products + variants) |
| POST | `/orders/add` | Create order from selected cart items |
| POST | `/orders/direct` | Buy now (skips cart) |

**POST `/orders/add` body:**
```json
{
  "payment_method": "cod",
  "name": "John",
  "phone": "123456789",
  "phone_alt": "",
  "email": "john@example.com",
  "line1": "Address",
  "line2": "",
  "city": "City",
  "country": "Country",
  "coupon": "DISCOUNT10",
  "notes": ""
}
```
Creates order from **selected** cart items, deducts stock, clears selected cart items.

**POST `/orders/direct` body:**
```json
{
  "payment_method": "cod",
  "name": "John",
  "phone": "123456789",
  "email": "john@example.com",
  "line1": "Address",
  "city": "City",
  "country": "Country",
  "items": [{ "product_id": 1, "variant_id": 5, "quantity": 2 }]
}
```
`items` can be a single item object or an array. If omitted, falls back to top-level `product_id`, `variant_id`, `quantity` fields.

### Admin (auth + admin required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/orders` | List all orders |
| GET | `/admin/orders/{id}` | Get single order details |
| POST | `/admin/orders/update` | Update order status/notes |

**POST `/admin/orders/update` body:**
```json
{
  "id": 1,
  "status": "shipped",
  "notes": "Shipped via DHL"
}
```

---

## Data Model

### Product
| Column | Type | Notes |
|--------|------|-------|
| id | integer |  |
| name | string |  |
| slug | string | Unique |
| description | text |  |
| short_description | text |  |
| price | decimal | 0 if has_variants |
| offer_price | decimal | nullable |
| stock | integer | 0 if has_variants |
| image | string | Deprecated legacy field |
| status | boolean |  |
| category_id | integer | FK to categories |
| has_variants | boolean |  |
| default_variant_id | integer | FK to product_variants |

### ProductVariant
| Column | Type | Notes |
|--------|------|-------|
| id | integer |  |
| product_id | integer | FK to products |
| sku | string | Unique |
| color | string | e.g. "Red" |
| color_code | string | e.g. "#FF0000" |
| price | decimal |  |
| offer_price | decimal | nullable |
| stock | integer |  |

### ProductImage
| Column | Type | Notes |
|--------|------|-------|
| id | integer |  |
| product_id | integer | FK to products |
| image_path | string | Relative path in public/images/ |
| color | string | nullable, e.g. "Red" |

### VariantImage
| Column | Type | Notes |
|--------|------|-------|
| id | integer |  |
| variant_id | integer | FK to product_variants |
| image_path | string | Relative path in public/images/ |

---

## Image Storage Convention

All images are stored in `public/images/` as `{timestamp}-{uniqid}.{ext}`. The `image_path` field stores the relative path (e.g., `images/1712345678-abc123.jpg`).

To display: prepend your backend domain, e.g., `https://yourdomain.com/{image_path}`.
