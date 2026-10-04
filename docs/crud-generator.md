# CRUD Generator

Generates a translatable admin CRUD module (model + translations table) that follows the Blog module's conventions exactly, and registers it in routes, sidebar, permissions, morph map, upload whitelist and lang files.

Conventions it reproduces: `docs/crud-generator-conventions.md`. Design decisions: `docs/decisions.md`.

## Access

| Condition | Result |
|---|---|
| Feature disabled (`crud-generator.enabled = false`) | Every generator route returns **404**, the sidebar link is hidden, `crud:make` refuses to run |
| Enabled, logged-in admin without the `Developer` role | **403** on every route, link hidden |
| Enabled, `Developer` role | Full access (Sidebar → Developer → CRUD Generator) |

`enabled` defaults to `true` only when `APP_ENV=local`. To use it on staging set `CRUD_GENERATOR_ENABLED=true`; never enable it in production. Running migrations/seeders from the generator is further limited to `crud-generator.artisan_environments` (`local`, `staging`).

The `Developer` role and the `developer@gmail.com` account come from `DeveloperSeeder`. Developers already pass every permission check through `Gate::before`, so the generator uses a role check (`Admin::canUseCrudGenerator()`, middleware `crud-generator`) instead of a permission.

## Using the UI

1. **New module** – enter the singular name (`Product`). Plural, sidebar label and icon are derived; override them only when needed. The "Derived names" block previews class, table, route and permission names (the server's `Str::plural()` is authoritative).
2. **Base options** – slug, publish date, order, status (all on by default, as in Blog).
3. **Fields** – add, remove and reorder rows. Each field has a snake_case name, label, type, required, translatable and (for textarea) Quill.
4. **Preview** – lists every file that will be created and every existing file that will be modified, with the inserted lines. Nothing is written yet.
5. **Confirm** – optionally tick *Run the new migrations* and *Run PermissionSeeder*. The result page shows created files, injected regions, Artisan output and the remaining next steps.
6. **Generated modules** list (generator index) reads `database/crud-generator/manifest.json`.

## Field types

| Type | Translatable | Column | Form control |
|---|---|---|---|
| `string` | yes/no | `string(name[, max])`, nullable when optional | text input, `max:N` rule (1–255, default 255) |
| `textarea` | yes/no | `text` (plain) / `longText` (Quill), always nullable like Blog's `content` | `<textarea>` or Quill (Blog's toolbar) |
| `image` | never | none – stored in `documents` (collection `{singular}_{field}`) | Metronic image input `field[]` (multiple), media card with delete / set cover on edit |

Rules:
- At least one **translatable string** field is required; the first one is the index title column and the search field.
- Reserved field names: `id, locale, created_at, updated_at, deleted_at, {singular}_id, slug, status, order, publish_date, images, translations, translation, files, first_image, cover_image, …` and PHP keywords.
- Labels: letters, digits, spaces and `- _ . , ( ) /`, max 60 characters (they end up in lang files and unescaped `@lang()` output).
- Module names: StudlyCase, not a PHP keyword, not a class already imported by the generated code (`Controller`, `Request`, `Document`, …), not ending in `Translation`, and no conflict with existing files, tables, routes, URL paths, permissions, sidebar/lang keys, morph aliases, upload collections or manifest entries.

## Command line

```bash
php artisan crud:make --spec=product.json --dry-run      # validate + list files and diffs
php artisan crud:make --spec=product.json                 # generate
php artisan crud:make --spec=product.json --migrate --seed
```

`product.json`:

```json
{
    "name": "Product",
    "plural": "",
    "label": "Products",
    "icon": "element-11",
    "slug": true,
    "publish_date": false,
    "order": true,
    "status": true,
    "fields": [
        {"name": "title", "label": "Title", "type": "string", "required": true, "translatable": true, "max_length": 200},
        {"name": "description", "label": "Description", "type": "textarea", "required": true, "translatable": true, "quill": true},
        {"name": "sku", "label": "SKU", "type": "string", "required": true, "translatable": false},
        {"name": "photos", "label": "Photos", "type": "image", "required": false}
    ]
}
```

The UI and the command call the same engine (`App\Services\CrudGenerator\CrudGenerator`).

## What is generated

For `Product` (key `products`):

| Created | |
|---|---|
| `database/migrations/*_create_products_table.php`, `*_create_product_translations_table.php` | FK `product_id` restrict/cascade, `locale`, unique `[product_id, locale]` |
| `app/Models/Product.php`, `ProductTranslation.php` | Blog traits, scopes, `translations`/`translation`, `get{Field}ImagesAttribute`, `sanitize_html` mutators for Quill fields |
| `app/Http/Requests/Backend/StoreProductRequest.php`, `UpdateProductRequest.php` | per-locale `field:code` rules |
| `app/Http/Controllers/Backend/ProductController.php` | Blog's methods, DataTables, Gate checks |
| `resources/views/backend/products/{index,form,show}.blade.php`, `tables/{index,show}.blade.php` | Blog's layout, locale tabs, Quill, image inputs |

| Modified (inside `crud-generator:start Product` … `crud-generator:end Product`) | |
|---|---|
| `routes/backend.php` | `Route::resource('/products', ...)` (multi-word: `/blog-posts` + `->names('blog_posts')`) |
| `resources/views/backend/includes/sidebar.blade.php` | link wrapped in `@can('products index')` |
| `database/seeders/PermissionSeeder.php` | `'products'` in `$modules` → `products index/create/show/edit/delete`, synced to `Admin` |
| `app/Providers/AppServiceProvider.php` | morph map `'product'` |
| `app/Services/UploadDocumentService.php` | one `$standardImage` entry per image field (only when the module has images) |
| `lang/{az,en,ru}/backend.php` | `titles.products` and `labels.products_{field}` (regions `Product titles` / `Product labels`); translate them afterwards |

Generated PHP is formatted with `stubs/crud-generator/pint.json` (the project's style) and every created or modified PHP file must pass `php -l`.

## Safety guarantees

- **Dry run first**: validation and conflict detection run before anything is written; existing files are never overwritten.
- **Atomic**: if writing, Pint or `php -l` fails, every created file/directory is deleted and every modified file restored.
- **Idempotent injections**: a region that already exists is left untouched.
- **Path guard**: every target path is resolved inside the base path (no `..`, absolute paths or symlink escapes).
- **Whitelisted stubs**: stubs only accept known placeholders; values come from the validated spec.
- **One run at a time**: generation holds a cache lock (`crud-generator`).
- **Rich text**: Quill HTML is sanitized on save (`sanitize_html()`, symfony/html-sanitizer): scripts, event handlers and `javascript:` URLs are removed, Quill formatting, base64 images and sandboxed video iframes are kept.
- Migrations/seeders run after the files are written; their failure is reported on the result page, not rolled back.

## After generation

1. Run the migrations and `PermissionSeeder` if you did not tick them (`php artisan migrate`, `php artisan db:seed --class=PermissionSeeder`).
2. Translate the new `titles`/`labels` keys for en/ru (or through the Translations panel).
3. Commit the generated files together with `database/crud-generator/manifest.json`.

## Removing a generated module (manual, v1)

There is no remove action yet. To remove module `X`, delete the files listed under `files` in its manifest entry, delete every `crud-generator:start X` … `crud-generator:end X` region (and `X titles` / `X labels` in the lang files), remove the manifest entry, then roll back or drop its tables yourself.

## Known limitations

- `DocumentsController::setStatus` resets the cover across all image collections of a record (existing behaviour shared with Blog).
- Like Blog, the FormRequests authorize everything and the controller checks the Gate, so an unauthorized user submitting invalid data sees validation errors before the 403.
- New lang labels get the same text in every locale.

## Tests

`php artisan test --filter=CrudGenerator` – all tests generate into a temporary sandbox copy (`storage/framework/testing/crud-sandbox-*`), never into the real codebase. After an intended stub change, refresh the snapshots with `UPDATE_SNAPSHOTS=1 php artisan test --filter=GeneratorOutputTest`.
