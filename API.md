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
Fetch all products with category, images, variants, variant images, and variant attributes.
```
GET /api/products
```

### GET `/products/search?q={term}&category_id={id}&min_price={}&max_price={}&in_stock={true|false}&sort_by={name|price|created_at|stock}&sort_order={asc|desc}&per_page={12}`
Search/filter products with pagination. Searches name, description, variant SKU, and variant attribute values.
```
GET /api/products/search?q=red&category_id=1&min_price=10&max_price=100&in_stock=true&sort_by=price&sort_order=asc&per_page=20
```

### GET `/products/{id or slug}`
Fetch a single product by numeric ID or slug string. Loads category, images, variants, variant images, variant attributes.
```
GET /api/products/1
GET /api/products/nike-air-max
```

---

## Admin Endpoints (auth + admin required)

All admin routes are under `/api/admin` and require `auth:sanctum` + `admin` middleware. Include the Bearer token.

### Products

#### GET `/admin/products/{slug}`
Fetch a single product by slug for admin editing.
```
GET /api/admin/products/my-product-slug
```

#### POST `/admin/products/add`
Create a new product. Accepts **multipart/form-data** or **application/json**.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | yes | Product name |
| `slug` | string | no | Auto-generated from name if omitted |
| `description` | string | no | |
| `short_description` | string | no | |
| `price` | number | no | Defaults to 0 |
| `offer_price` | number | no | |
| `stock` | integer | no | Defaults to 0 |
| `category_id` | integer | no | Nullable |
| `status` | string | no | `active`, `draft`, or `discontinued` (default: `active`) |
| `images` | array | no | Array of image objects (see below) |
| `images.*.url` | file/string | no | Upload file or URL string |
| `images.*.is_featured` | boolean | no | Whether this is the primary image |
| `variants` | array | no | Array of variant objects (see below) |

**Variant object fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `sku` | string | no | Auto-generated if omitted |
| `price` | number | no | Defaults to 0 |
| `offer_price` | number | no | |
| `stock` | integer | no | Defaults to 0 |
| `attributes` | array | no | Array of `{attribute, value}` objects |
| `attributes.*.attribute` | string | yes | e.g. "Color", "Size" |
| `attributes.*.value` | string | yes | e.g. "Red", "Medium" |
| `images` | array | no | Array of image objects for this variant |
| `images.*.url` | file/string | no | Upload file or URL string |
| `images.*.is_featured` | boolean | no | |

**Example JSON body:**
```json
{
  "name": "V-Neck T-Shirt",
  "price": "19.99",
  "offer_price": "14.99",
  "stock": 15,
  "category_id": 1,
  "status": "active",
  "images": [
    { "url": "/images/tshirt-size-chart.jpg", "is_featured": false }
  ],
  "variants": [
    {
      "sku": "TS-RED-M",
      "price": "19.99",
      "offer_price": "14.99",
      "stock": 15,
      "attributes": [
        { "attribute": "Color", "value": "Red" },
        { "attribute": "Size", "value": "Medium" }
      ],
      "images": [
        { "url": "/images/tshirt-red-front.jpg", "is_featured": true }
      ]
    }
  ]
}
```

#### POST `/admin/products/update`
Update an existing product. Accepts **multipart/form-data** or **application/json**.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | integer | yes | ID of product to update |
| `name` | string | yes | |
| `slug` | string | no | |
| `description` | string | no | |
| `short_description` | string | no | |
| `price` | number | no | |
| `offer_price` | number | no | |
| `stock` | integer | no | |
| `category_id` | integer | no | Nullable |
| `status` | string | no | `active`, `draft`, or `discontinued` |
| `images` | array | no | New images to append |
| `variants` | array | no | Full list of variants (see sync logic) |

**Variant sync logic during update:**
- If a variant object has an `id` field → that existing variant is **updated**.
- If a variant object has **no** `id` → a **new** variant is created.
- Any existing variant IDs **not** present in the `variants` array are **deleted** (along with their images and attributes).
- Send the **complete** list of desired variants every time.

**Per-variant fields during update:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Existing variant ID to update (omit to create new) |
| `sku` | string | |
| `price` | number | |
| `offer_price` | number | |
| `stock` | integer | |
| `attributes` | array | Replaces all existing attributes |
| `images` | array | New images to append |
| `images_to_keep` | integer[] | IDs of existing images to keep (others deleted) |

#### DELETE `/admin/products/delete/{id}`
Deletes product + all its images + all its variants + variant images + variant attributes.

#### DELETE `/admin/products/{productId}/images/{imageId}`
Delete a single product-level image.

---

### Variants

#### GET `/admin/variants/{id}`
Fetch a single variant. Loads `product`, `images`, and `attributes` relations.
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
    "sku": "TS-RED-M",
    "price": 19.99,
    "offer_price": 14.99,
    "stock": 15,
    "created_at": "...",
    "updated_at": "...",
    "product": { ... },
    "images": [
      { "id": 1, "variant_id": 5, "url": "images/tshirt-red-front.jpg", "is_featured": true }
    ],
    "attributes": [
      { "id": 1, "variant_id": 5, "attribute": "Color", "value": "Red" },
      { "id": 2, "variant_id": 5, "attribute": "Size", "value": "Medium" }
    ]
  }
}
```

#### POST `/admin/products/{id}/variants`
Add a new variant to a product. Accepts **multipart/form-data** or **application/json**.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `sku` | string | no | Auto-generated if omitted |
| `price` | number | no | Defaults to 0 |
| `offer_price` | number | no | |
| `stock` | integer | no | Defaults to 0 |
| `attributes` | array | no | Array of `{attribute, value}` objects |
| `attributes.*.attribute` | string | yes | |
| `attributes.*.value` | string | yes | |
| `images` | array | no | Array of image objects |
| `images.*.url` | file/string | no | |
| `images.*.is_featured` | boolean | no | |

#### PUT or POST `/admin/variants/{id}`
Update an existing variant. Accepts **multipart/form-data** or **application/json**.

| Field | Type | Description |
|-------|------|-------------|
| `sku` | string | |
| `price` | number | |
| `offer_price` | number | |
| `stock` | integer | |
| `attributes` | array | Replaces all existing attributes |
| `images` | array | New images to append |
| `images_to_keep` | integer[] | IDs of existing images to keep |

#### POST `/admin/variants/delete/{id}`
Delete a variant + its images + its attributes.

#### POST `/admin/variants/{id}/images`
Upload a single image to a variant. Accepts **multipart/form-data**.

| Field | Type | Required |
|-------|------|----------|
| `url` | file/string | yes |
| `is_featured` | boolean | no |

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
Creates order from **selected** cart items, deducts stock, clears selected cart items. Stores a snapshot of variant attributes on each order product.

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

## Delivery Charges

### Public (no auth)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/delivery-charges` | List all active delivery charges |

### Admin (auth + admin required)

All under `/api/admin` with `auth:sanctum` + `admin` middleware.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/delivery-charges` | List all delivery charges |
| POST | `/admin/delivery-charges/add` | Add a delivery charge |
| POST | `/admin/delivery-charges/update` | Update a delivery charge |
| DELETE | `/admin/delivery-charges/delete/{id}` | Delete a delivery charge |

**POST `/admin/delivery-charges/add` body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | yes | e.g. "Inside City", "Outside City" |
| `charge` | number | yes | Delivery fee amount |
| `minimum_order` | number | no | Minimum order for this rate (null = no minimum) |
| `status` | boolean | no | Defaults to `true` |

**POST `/admin/delivery-charges/update` body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | yes | Delivery charge ID |
| `name` | string | yes | |
| `charge` | number | yes | |
| `minimum_order` | number | no | |
| `status` | boolean | no | |

### Data Model

### DeliveryCharge
| Column | Type | Notes |
|--------|------|-------|
| id | integer | |
| name | string | e.g. "Inside City" |
| charge | decimal | Delivery fee |
| minimum_order | decimal | Nullable, minimum order amount for this rate |
| status | boolean | Active/inactive |

---

## Data Model

### Product
| Column | Type | Notes |
|--------|------|-------|
| id | integer | |
| name | string | |
| slug | string | Unique, nullable |
| description | text | Nullable |
| short_description | text | Nullable |
| price | decimal | |
| offer_price | decimal | Nullable |
| stock | integer | |
| status | string | `active`, `draft`, or `discontinued` |
| category_id | integer | Nullable, FK to categories |

### ProductVariant
| Column | Type | Notes |
|--------|------|-------|
| id | integer | |
| product_id | integer | FK to products |
| sku | string | Unique |
| price | decimal | |
| offer_price | decimal | Nullable |
| stock | integer | |

### ProductImage
| Column | Type | Notes |
|--------|------|-------|
| id | integer | |
| product_id | integer | FK to products |
| url | string | Relative path in public/images/ or URL |
| is_featured | boolean | |

### VariantImage
| Column | Type | Notes |
|--------|------|-------|
| id | integer | |
| variant_id | integer | FK to product_variants |
| url | string | Relative path in public/images/ or URL |
| is_featured | boolean | |

### VariantAttribute
| Column | Type | Notes |
|--------|------|-------|
| id | integer | |
| variant_id | integer | FK to product_variants |
| attribute | string | e.g. "Color", "Size" |
| value | string | e.g. "Red", "Medium" |

### OrderProduct
| Column | Type | Notes |
|--------|------|-------|
| id | integer | |
| order_id | integer | FK to orders |
| product_id | integer | FK to products |
| variant_id | integer | Nullable, FK to product_variants |
| quantity | integer | |
| price | decimal | |
| variant_attributes | json | Snapshot of variant attributes at time of order |

---

## Image Storage Convention

Images can be provided as either uploaded files or URL strings. Uploaded files are stored in `public/images/` as `{timestamp}-{uniqid}.{ext}`. The `url` field stores the relative path (e.g., `images/1712345678-abc123.jpg`).

To display: prepend your backend domain, e.g., `https://yourdomain.com/{url}`.
