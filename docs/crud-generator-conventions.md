# CRUD Generator – Conventions derived from the Blog module

Phase 0 report. Every rule below is taken from the real code (file references given); the generator must reproduce these exactly.

## 1. Stack facts

| Item | Value | Source |
|---|---|---|
| PHP / Laravel | PHP 8.3.33, `laravel/framework ^13.0` | `composer.json` |
| Permissions package | `spatie/laravel-permission ^8.3`, guard **`admin`** | `composer.json`, `app/Models/Admin.php` |
| Activity log | `spatie/laravel-activitylog ^4.12` via `App\Traits\SpatieLogsActivity` | `app/Traits/SpatieLogsActivity.php` |
| Index tables | `yajra/laravel-datatables-oracle` (server side, AJAX) | `BlogController::dataTable()` |
| Tests | PHPUnit 12 (no Pest), sqlite `:memory:`, `RefreshDatabase` | `phpunit.xml`, `tests/Feature/SecuritySettingsTest.php` |
| Formatting | **No `pint.json`**; existing code does **not** pass Pint's default `laravel` preset (`concat_space` with spaces, `new X()`, `!$x` without space, unordered traits) | `vendor/bin/pint --test` on Blog files |
| Git | Project is **not** a git repository | environment |
| Front-end | Metronic theme (`ki-outline` icons, `card card-flush`, `data-kt-*`), jQuery, Select2, Flatpickr, SweetAlert2, Quill 2.0.3 – all from `public/backend/assets/plugins/global/plugins.bundle.*` (no npm build involved) | `includes/scripts.blade.php`, `includes/styles.blade.php` |

## 2. Database / migrations

`database/migrations/2026_09_19_080326_create_blogs_table.php`

```php
$table->bigIncrements('id');
$table->string('slug')->unique();
$table->date('publish_date')->nullable();
$table->unsignedInteger('order')->default(1);
$table->boolean('status')->default(true);
$table->timestamps();
```

`database/migrations/2026_09_19_080328_create_blog_translations_table.php`

```php
$table->bigIncrements('id');
$table->unsignedBigInteger('blog_id');
$table->string('locale', 10);
$table->string('title');
$table->string('subtitle')->nullable();
$table->longText('content')->nullable();

$table->unique(['blog_id', 'locale']);
$table->foreign('blog_id')->references('id')->on('blogs')->onUpdate('restrict')->onDelete('cascade');
```

Conventions:
- Anonymous class migrations, `up()`/`down()` with `: void`, `down()` = `Schema::dropIfExists()`. No docblocks.
- `bigIncrements('id')` (not `id()`), FK declared as `unsignedBigInteger` + explicit `foreign()` with `onUpdate('restrict')->onDelete('cascade')`.
- Translation table: **no timestamps**, `locale` = `string(10)`, unique `[parent_id, locale]`.
- Parent: `timestamps()`, **no soft deletes**.
- Translatable `string` → `string()` (`nullable()` when optional); rich text → `longText()->nullable()`.
- Translation table migration is a separate file with a later timestamp.
- Images are **not** columns – they live in the polymorphic `documents` table.

## 3. Models

`app/Models/Blog.php`
- `use TranslationTrait, DocumentTrait, SpatieLogsActivity;`
- `protected $guarded = [];` (Blog uses `$guarded`, not `$fillable` – generator follows Blog).
- `protected $casts = [...]` for dates.
- Scopes: `scopeActive` (`status = 1`), `scopeOrdered` (`order asc`).
- `translations(): HasMany`, `translation(): HasOne` filtered by `App::getLocale()`.
- Image accessor `getGalleryImagesAttribute()` → `getDocuments('blog_images')->where('status', '!=', 'main')`.

`app/Models/BlogTranslation.php`: `$guarded = []`, `public $timestamps = false;`, nothing else.

Translation mechanics (no package – home-grown):
- `TranslationTrait::translate($code)` → `$this->translations->where('locale', $code)->first()`.
- Helpers `translation($model)` (current locale) and `translation_first($model)` (current locale, else first) in `app/Helpers/helpers.php`. **Locale fallback** = `translation_first()` only; the `translation` relation itself has no fallback (index shows `'No Title'`).
- Locales come from the DB: `active_langs()` (cached forever, `languages` table, seeded az/en/ru, az default). Session locale set by `App\Http\Middleware\Localization` (`lang` alias).

`app/Traits/DocumentTrait.php`: `files()` morphMany `Document`, `getDocuments($collection)`, `getDocument($collection)`, `first_image`, `cover_image` (document with `status = 'main'`).

## 4. Images

- Service: `App\Services\UploadDocumentService::upload($morphAlias, $inputName, $id, $collectionName, $multiple, $request)`.
- **Whitelist**: the service only accepts collections listed in its `$allowedTypes` (`'blog_images' => $standardImage`). A new collection **must** be registered there, otherwise upload throws `upload_no_rule`.
- **Morph map**: `AppServiceProvider::boot()` → `Relation::morphMap(['admin' => ..., 'blog' => Blog::class])`. Morph alias = singular snake (`'blog'`). A new model **must** be added there.
- Disk `documents` → `storage/app/public/documents/{collection}/...`, URL via `Document::document` accessor.
- Collection name convention: `{singular}_{input}` → `blog_images`. Input name `images[]`, `multiple`.
- Validation: `'images' => ['nullable','array']`, `'images.*' => ['nullable','image','mimes:jpeg,jpg,png,webp','max:2048', new ValidImageMimeTypeRule()]`, attribute name `trans('backend.labels.images')`.
- Update: new uploads are **appended**; existing images removed one by one via AJAX `POST /gload/documents/{id}/delete` and cover set via `.../set-status` (`DocumentsController`), UI in `backend.includes.media` (`isDeleted`, `isCovered`). Note: `setStatus` resets `status` for **all** documents of the model, regardless of collection.
- Destroy: controller deletes each file from disk and the `Document` row, then the model.
- No thumbnails.

## 5. Controller (`app/Http/Controllers/Backend/BlogController.php`)

- Namespace `App\Http\Controllers\Backend`, extends `App\Http\Controllers\Controller`.
- Methods: `index, create, store, show, edit, update, destroy` + `protected dataTable($query)` + `protected permissions($id)`.
- Authorization: first line of every action `abort_if(Gate::denies('blogs index'), Response::HTTP_FORBIDDEN);` (show → `index`, store → `create`, update → `edit`, destroy → `delete`).
- `index`: AJAX → `Blog::with(['translation'])->orderBy('id','desc')`, search on `translations.title` via `whereHas`, return `$this->dataTable($query)`; non-AJAX → `view('backend.blogs.index')`. Pagination is DataTables server side (pageLength 10), not `paginate()`.
- `create`/`edit`: one shared view `backend.blogs.form` with `compact('edit','langs')` (+ `blog`).
- `store`/`update`: `$data = $request->validated()`, `DB::beginTransaction()`, explicit column array on create/update, loop `active_langs()` → `translations()->create([...])` / `updateOrCreate(['locale' => ...], [...])`, input key format **`field:locale`**, then `if ($request->hasFile('images')) $uploadDocumentService->upload(...)`, commit, `redirect(route('backend.blogs.index'))->withSuccess(trans('backend.messages.success.create|update'))`. On exception: rollback, `Log::error`, `redirect()->back()->withError(trans('backend.messages.error.create|update'))`.
- `order`: `$data['order'] ?? 1` on store, `?? $blog->order` on update.
- `show`: loads `translations, files`, builds `$translations = $langs->mapWithKeys(...)` for the view.
- `destroy`: delete documents + files, `$blog->delete()`, returns JSON `['status' => 1|0]` (AJAX from DataTables).
- `dataTable`: columns `checkbox`, `blog` (cover thumb + title linking to edit), `status` (`badge()` helper), `publish_date`, `actions` (dropdown built by `permissions()` with `admin()->can()` checks for view/edit/delete); `rawColumns` list.

## 6. FormRequests (`app/Http/Requests/Backend/StoreBlogRequest.php`, `UpdateBlogRequest.php`)

- `authorize()` returns `true` (authorization is in the controller).
- Rules built as `$rules[] = [...]` per `active_langs()` then `Arr::collapse($rules)`.
- Per-locale keys `'title:' . $lang->code`. **All locales are required equally** for required fields (no default-locale-only rule).
- Required string: `['required','string','max:255']`; optional: `['nullable','string','max:255']`; rich text: `['required','string']`.
- Base: `slug` `Rule::unique('blogs','slug')` (update: `->ignore($this->blog->id)`), `order` `['nullable','integer']`, `status` `['required','boolean']`.
- `attributes()` only for `images.*`. Messages come from `lang/*/validation.php` (no custom messages).

## 7. Blade views (`resources/views/backend/blogs/`)

- `index.blade.php`: `@extends('layouts.backend.master')`, `@section('title', trans('backend.titles.blogs'))`, toolbar + breadcrumb, card with `backend.includes.card.card_title` (search) and `backend.includes.card.card_toolbar` (`['page' => 'blogs']` → create button, bulk delete under `@can("blogs delete")`), `@include('backend.blogs.tables.index')`, scripts `@include('backend.includes.plugins.datatable', ['columns' => [...], 'route' => ..., 'pageLength' => 10])`. Delete confirmation (SweetAlert `confirm()` helper) and bulk delete are inside the datatable include.
- `tables/index.blade.php`: `<table id="datatable">` with header `<th>` per column. Empty state = DataTables' own.
- `form.blade.php` (create + edit, no separate partial): left column (images card with Metronic `image-input`, status select2 + indicator, general card with slug/date/order), right column with language `nav-tabs` (`#kt_blog_lang_{code}`), per-locale fields, media card on edit (`backend.includes.media` with `isDeleted/isCovered = true`), cancel/save buttons with `indicator-progress`.
- Field value pattern: `isset($blog) ? $blog->translate($lang->code)?->field : old('field:' . $lang->code)`.
- **Quill**: `<div class="quill-wrapper"><div id="kt_blog_content_{code}" class="min-h-300px mb-2">{!! ... !!}</div></div>` + hidden `<textarea name="content:{code}" required class="d-none">`; JS creates `new Quill('#kt_blog_content_{code}', {modules: {toolbar: [...]}, theme: 'snow'})` per locale; on submit copies `root.innerHTML` (empty `<p><br></p>` → `''`) into the textarea, then `ktValidateRequired(this)` (global helper in `includes/scripts.blade.php`) and sets submit indicator. `.ql-container { min-height: 250px; }` in `@section('styles')`.
- `show.blade.php` + `tables/show.blade.php`: cover image, details card, language tabs, media card (read-only).
- Quill HTML is rendered **unescaped** (`{!! !!}`) in both form and show – **no sanitization exists** in Blog.
- No `@php` blocks anywhere (also a CLAUDE.md rule).

## 8. Routes

- File `routes/backend.php`, loaded in `bootstrap/app.php` with `middleware(['web','lang'])->prefix('gload')->name('backend.')`.
- Inside `Route::group(['middleware' => ['auth:admin']], ...)`: `Route::resource('/blogs', BlogController::class);` – implicit model binding `{blog}`, names `backend.blogs.*`.
- **No permission middleware on routes**; checks are in controllers. `use` imports at the top of the file, alphabetic.

## 9. Permissions & roles

- `database/seeders/PermissionSeeder.php`: deletes all `admin` guard permissions, recreates `"{module} {action}"` for `$modules` (`'blogs'` = plural table name) × `$actions` (`index, create, show, edit, delete`), then `Role::findOrCreate('Admin','admin')->syncPermissions(all)`. Idempotent in end state. Adding a module = adding its plural to `$modules`.
- Role **`Developer` already exists**: `Admin::DEVELOPER_ROLE = 'Developer'`, created by `DeveloperSeeder`, `Admin::isDeveloper()` checks `role_id` relation.
- `AppServiceProvider`: `Gate::before` returns `true` for developers → developers pass **every** ability. Spatie permissions are therefore never needed for developers, and every permission in the seeder is auto-granted to `Admin`.
- `RoleController` hides the Developer role from the roles list.

## 10. Sidebar

- Blade: `resources/views/backend/includes/sidebar.blade.php` (no config array).
- Pattern per link: `@can('blogs index')` → `menu-item` / `menu-link {{ request()->routeIs('backend.blogs.*') ? 'active' : '' }}` / `<i class="ki-outline ki-book-square fs-2">` / `@lang('backend.titles.blogs')`. Blogs sits under a "Pages" section header (`backend.titles.pages`) rendered inside the same `@can`.

## 11. Translations (UI labels)

- `lang/{az,en,ru}/backend.php` (`array(` export style), groups `labels`, `titles`, `buttons`, `messages.success|error`, `mixins`…; DB overrides via `DatabaseTranslationLoader` + `translations` table, import with `php artisan translations:import`.
- Module title key: `backend.titles.blogs`. Field labels reuse generic `backend.labels.*` keys.

## 12. Style notes for generated code

- 4 spaces, LF, final newline (`.editorconfig`).
- `'title:' . $lang->code` (spaces around `.`), `new ValidImageMimeTypeRule()`, `!is_null(...)`, short closures, minimal comments, no docblocks.
- Generated code must therefore be formatted with a Pint config matching this style, not the default preset.
