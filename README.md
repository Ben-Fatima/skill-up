# skill-up

collection of small, practical web dev exercises (PHP + JS) to master fundamentals step by step

## FileImporter project

Simple CSV importer that streams uploads in chunks, stores the file, and processes it into SQLite.

### Setup

- `cd FileImporter`
- Create the database: `php init_db.php`
- Start the dev server: `php -S localhost:8000 -t public`
- Open the uploader at http://localhost:8000/upload

### CSV format

Headers (four columns, comma-separated):

```
sku,name,price,stock
```

Example row:

```
SKU123,Example product,9.99,10
```

### Routes

- `GET /` → redirects to `/upload`
- `GET /upload` → upload form
- `POST /upload/init` → start a chunked upload
- `POST /upload/chunk` → append a chunk (query: `upload_id`, `offset`)
- `POST /upload/complete` → finalize upload (query: `upload_id`, `original_name`), returns `import_id`
- `POST /import?id=IMPORT_ID` → process the next chunk of the file
- `GET /import/status?id=IMPORT_ID` → current import progress
- `GET /import/errors-report?import_id=IMPORT_ID` → CSV of failed rows
- `GET /products?page=N` → paginated products list

## Inventory API (Multi-Location)

Lumen REST API for multi-location inventory.

### Rules (core behavior)

- Movements are append-only.
- `qty` must be a non-zero integer, `IN` is stored as positive, `OUT` as negative, `ADJUST` keeps the provided sign.
- `OUT` is rejected if it would make stock negative (response includes `current_stock`).
- Stock listings omit zero balances, low-stock report uses a fixed threshold of 100.
- Pagination is capped at `per_page=100`.

### Routes

#### Products

- `GET /products` → list products (query: `q`, `sort`, `dir`, `per_page`, `page`)
- `POST /products` → create product (`sku`, `name`, `unit_cost_cents`, `min_stock`)
- `GET /products/{id}` → fetch product by id
- `PATCH /products/{id}` → update product (any of: `sku`, `name`, `unit_cost_cents`, `min_stock`)
- `DELETE /products/{id}` → delete product

#### Locations

- `GET /locations` → list locations (query: `q`, `sort`, `dir`, `per_page`, `page`)
- `POST /locations` → create location (`code`, `address`)
- `GET /locations/{id}` → fetch location by id
- `PATCH /locations/{id}` → update location (any of: `code`, `address`)
- `DELETE /locations/{id}` → delete location

#### Movements

- `GET /movements` → movement history (query: `product_id`, `location_id`, `type`, `per_page`, `page`)
- `POST /movements` → add movement (`product_id`, `location_id`, `type`, `qty`, optional `note`)

#### Stock

- `GET /stock` → stock on hand (query: `sku`, `location`, `per_page`, `page`)
- `GET /products/{id}/stock` → stock on hand for a product (query: `per_page`, `page`)
- `GET /locations/{id}/stock` → stock on hand for a location (query: `per_page`, `page`)

#### Reports

- `GET /reports/low-stock` → low stock report (query: `per_page`, `page`)
