# Smart Seeder

Generates realistic seed data for an admin module with Google Gemini, in every active language, with real photos from Pixabay for image fields, and writes it as a normal Laravel seeder that can be run from the admin panel or Artisan.

## Access

Same rules as the CRUD generator (it writes files into the codebase): **Developer role only**, and only while `crud-generator.enabled` is true (default: `APP_ENV=local`). Sidebar → Developer → Smart Seeder.

## Setup

1. Gemini API key – free tier at [aistudio.google.com](https://aistudio.google.com) (API keys). Free-tier prompts may be used by Google to improve its products; do not send real customer data.
2. Pixabay API key – free at [pixabay.com/api/docs](https://pixabay.com/api/docs/) (100 requests per minute; images must be downloaded, not hotlinked). Only needed for modules with image fields.
3. Smart Seeder → AI settings: paste both keys and save. Keys are stored encrypted (`ai_settings` table, `encrypted` cast) and never shown again; leave a field empty to keep the stored key. The model defaults to `gemini-3.8-flash`; `gemini-3.5-flash-lite` is the faster/cheaper alternative.

## Usage

1. Pick a module (Blog and every CRUD-generator module whose tables exist), the number of records (1–20) and describe the data, e.g. *"Programming services: web, mobile, backend, DevOps"*.
2. **Generate seeder** – Gemini returns JSON constrained by a schema built from the module's columns and the active languages; the result is validated, cleaned and written to:
   - `database/seeders/Smart/{Module}SmartSeeder{YmdHis}.php` – the seeder class
   - `database/seeders/Smart/data/{Class}.json` – records, meta and photo credits
   - `database/seeders/Smart/assets/{Class}/*.jpg|png` – downloaded Pixabay photos
3. Review the data on the seeder page, then **Run seeder** (or `php artisan db:seed --class="Database\Seeders\Smart\{Class}"`).

Running a seeder twice does not duplicate data: records whose slug (or, without slug, default-language title) already exists are skipped.

## What is validated

- Every record has every active language and every required field; otherwise nothing is written.
- Strings are cut to the column's max length; plain textareas lose HTML; rich text (Quill) is passed through `sanitize_html()`.
- Slugs are normalised with `Str::slug()` and made unique against the database and the batch.
- Dates fall back to today, order to the record position, status to active.
- Missing photos produce a warning, not an error.
- Gemini overload (503 etc.) is retried twice, then the request falls back to `gemini-3.5-flash-lite`; the seeder page shows which model was used.

All files of one generation are written atomically (CRUD generator `Writer`: path guard, no overwrite, rollback).

## Photo credits

Pixabay asks for attribution. Photographer and photo links are stored in the JSON `credits` list and shown on the seeder page.

## Code

| Part | File |
|---|---|
| Orchestration (schema, prompt, validation, files) | `app/Services/SmartSeeder/SmartSeeder.php` |
| Gemini `generateContent` (JSON schema output) | `app/Services/SmartSeeder/GeminiClient.php` |
| Pixabay search + download | `app/Services/SmartSeeder/PixabayClient.php` |
| Seeding records, translations and documents | `app/Services/SmartSeeder/SmartSeederRunner.php` |
| Seedable modules (Blog + manifest) | `app/Services/SmartSeeder/SeedableModules.php` |
| Seeder stub | `stubs/smart-seeder/seeder.stub` |

To switch to another AI provider, replace `GeminiClient::generateJson()`; the schema, validation and seeder format stay the same.

## Tests

`php artisan test tests/Feature/SmartSeeder` – Gemini and Pixabay are faked with `Http::fake()`, files go to a sandbox; no API key is used.
