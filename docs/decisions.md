# CRUD Generator – Decisions

Decisions taken while building the generator. Conventions copied from Blog are documented in `crud-generator-conventions.md`.

## Approved by the project owner

| # | Decision | Reason |
|---|---|---|
| 1 | Rich-text (Quill) fields of generated modules are cleaned with `symfony/html-sanitizer` through a `sanitize_html()` helper used in model mutators. Blog is left untouched. | Blog renders Quill HTML raw (`{!! !!}`), which allows stored XSS. |
| 2 | Generator access is a role check: `Admin::canUseCrudGenerator()` = feature enabled **and** `isDeveloper()`. No Spatie permission. | `Gate::before` grants Developers everything and `PermissionSeeder` syncs every permission to Admin, so a permission would leak to Admins. The `Developer` role already exists. |
| 3 | Git repository initialised with a baseline commit; one commit per milestone. | The project was not under version control. |
| 4 | Generated UI labels are injected into `lang/{az,en,ru}/backend.php` inside managed regions (`titles.{key}`, `labels.{key}_{field}`). | Same keys style as Blog (`backend.titles.blogs`). |

## Defaults chosen from existing conventions

| # | Decision | Reason |
|---|---|---|
| 5 | Module key = snake plural (`blog_posts`) for table, permissions (`blog_posts index`), route names (`backend.blog_posts.*`) and view folder; URL is kebab (`/blog-posts`) via `->names('blog_posts')`. | `card_toolbar.blade.php` uses one `$page` value for both `@can("$page delete")` and `route("backend.$page.create")`. |
| 6 | Base options `slug` (unique), `publish_date`, `order`, `status` are toggles, all on by default. | Blog has all four. |
| 7 | At least one translatable `string` field is required; the first one is the index title column and the search column. | Blog's `title` plays this role in `dataTable()` and the search query. |
| 8 | Image fields are never translatable; each one is a `documents` collection `{singular}_{field}`, input `{field}[]`, multiple with cover. Required images are `required` on store and `nullable` on update. | Blog stores images in `documents` (`blog_images`). |
| 9 | Known limitation kept as-is: `DocumentsController::setStatus` resets the cover across all collections of a model. | Existing behaviour; Blog must not be changed. |
| 10 | Injected class references use FQCN (`\App\Models\X::class`) so no `use` import regions are needed. | Keeps injections single-region and removable. |
| 11 | Generated PHP is formatted with `stubs/crud-generator/pint.json` (project style). Pint never runs on existing files; injected text is pre-formatted. | Existing code fails Pint's default `laravel` preset. |
| 12 | Manifest stored at `database/crud-generator/manifest.json`. | Versioned with the code it describes and survives DB resets. |
| 13 | "Remove generated module" is not part of v1; the manifest keeps the data needed to add it. | Marked optional in the brief. |
| 14 | Preview diffs are rendered as line insertions with context; no diff package. | Injections only insert lines. |
| 15 | `slug`, `status`, `order`, `publish_date` are always reserved as field names, even when the base option is off. | Keeps one simple rule; the base option re-enables them. |
| 16 | Generated index eager-loads `files` when the module has image fields (`with(['translation', 'files'])`). | Blog's `dataTable()` reads `cover_image` per row without eager loading (N+1). |
| 17 | The datatable title is escaped with `e()`; translatable/main inputs use `old('field', stored)`; the show page's edit button is wrapped in `@can`. | Small safety/UX fixes over Blog that keep the same structure. |
| 18 | All images of a module are shown in a single media card (`$model->files`). | `backend.includes.media` pushes its JS on every include, so including it once per image field would bind delete/cover handlers several times. |
| 19 | Quill fields share one `quillOptions` object and one submit loop over `quills` keyed by input name. | Same toolbar and behaviour as Blog without repeating the config per field. |
| 20 | Rich-text values echoed back into the editor are passed through `sanitize_html()`; stored values are sanitized by model mutators. | Blog echoes `old()` input raw. |
| 21 | The preview stores the validated spec in the session under a one-time random token; the confirm step only posts the token and re-validates before writing. | The spec cannot be altered between preview and generation. |
| 22 | Migrations/seeders run in a separate `php artisan` process after writing; failures are reported on the result page instead of rolling back the written files. | New classes and migrations must be loaded fresh; the code itself is already valid at that point. |
| 23 | Generation holds a `crud-generator` cache lock. | Two concurrent runs could interleave injections into the same files. |
| 24 | Generated FormRequests keep Blog's `authorize(): true` + controller Gate check. | Same authorization flow as Blog. |

## Smart Seeder

| # | Decision | Reason |
|---|---|---|
| 25 | AI provider is Google Gemini (free tier), called with Laravel's `Http` client (`generateContent` + `responseSchema`); no new Composer package. | Chosen by the project owner; the free tier needs no billing. `GeminiClient` is the only provider-specific class. |
| 26 | Photos come from Pexels search on an English query the AI writes per record; files are stored with the seeder. | Real, topic-matching photos; the seeder stays reproducible offline. |
| 27 | Gemini/Pexels keys live in `ai_settings` with `encrypted` casts, edited on the Smart Seeder page. | Same pattern as the reCAPTCHA keys in `security_settings`. |
| 28 | Smart Seeder uses the CRUD generator's middleware/flag (developer-only, local by default). | Both tools write files into the codebase. |
| 29 | Generated seeders are thin classes that call `SmartSeederRunner` with their JSON file; records are skipped when the slug (or default-language title) exists. | Logic stays testable in one place; re-running a seeder never duplicates data. |
| 30 | Flash messages built from API errors or Artisan output are stripped of quotes, backslashes, angle brackets and line breaks. | The existing `notify()` helper prints them unescaped into a JavaScript string. |
| 31 | Photos come from Pixabay instead of Pexels (decision 26): Pexels paused new API key issuance. A new migration renames `pexels_api_key` to `pixabay_api_key`. | Pixabay has open key issuance, real photos and topic search; images are downloaded, as its terms require. |
| 32 | Gemini 5xx/overload responses are retried twice (2 s, 5 s); if the chosen model is still overloaded the request is repeated once on `gemini-3.5-flash-lite`, and the seeder records which model produced the data. | The free tier often returns 503 "high demand" for short periods. |
