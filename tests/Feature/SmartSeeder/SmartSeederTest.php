<?php

namespace Tests\Feature\SmartSeeder;

use App\Models\Admin;
use App\Models\AiSetting;
use App\Models\Blog;
use App\Models\Document;
use App\Models\Language;
use App\Services\SmartSeeder\SmartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Feature\CrudGenerator\InteractsWithSandbox;
use Tests\TestCase;

class SmartSeederTest extends TestCase
{
    use InteractsWithSandbox;
    use RefreshDatabase;

    private const LOCALES = ['az', 'en', 'ru'];

    private Admin $developer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();

        foreach (self::LOCALES as $code) {
            Language::create(['name' => strtoupper($code), 'code' => $code, 'default' => $code === 'az', 'status' => true]);
        }

        Cache::forget('active_langs');
        Storage::fake('documents');

        $this->developer = $this->makeAdmin(Admin::DEVELOPER_ROLE, 'developer@example.com');

        AiSetting::current()->update(['gemini_api_key' => 'gemini-secret-key', 'pexels_api_key' => 'pexels-secret-key']);
    }

    private function makeAdmin(string $roleName, string $email): Admin
    {
        $role = Role::findOrCreate($roleName, 'admin');

        $admin = Admin::create([
            'name' => $roleName,
            'email' => $email,
            'role_id' => $role->id,
            'status' => '1',
            'password' => 'password',
        ]);
        $admin->assignRole($role);

        return $admin;
    }

    private function aiRecords(int $count, array $overrides = []): array
    {
        $records = [];

        for ($i = 1; $i <= $count; $i++) {
            $translations = [];

            foreach (self::LOCALES as $code) {
                $translations[$code] = [
                    'title' => "Web development {$i} {$code}",
                    'subtitle' => "Fast sites {$i} {$code}",
                    'content' => "<p>Content {$i} {$code}</p><script>alert(1)</script>",
                ];
            }

            $records[] = array_merge([
                'slug' => "Web Development {$i}",
                'publish_date' => '2026-05-0' . $i,
                'order' => $i,
                'status' => true,
                'translations' => $translations,
                'image_queries' => ['images' => "programmer laptop {$i}"],
            ], $overrides);
        }

        return ['records' => $records];
    }

    private function fakeApis(array $aiResponse): void
    {
        $image = $this->jpeg();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($aiResponse)]]], 'finishReason' => 'STOP']],
            ]),
            'api.pexels.com/*' => function (Request $request) {
                $seed = crc32($request->url());

                return Http::response(['photos' => [[
                    'id' => $seed,
                    'url' => "https://www.pexels.com/photo/{$seed}/",
                    'photographer' => 'Jane Doe',
                    'photographer_url' => 'https://www.pexels.com/@jane',
                    'src' => ['large' => "https://images.pexels.com/photos/{$seed}/large.jpeg"],
                ]]]);
            },
            'images.pexels.com/*' => Http::response($image, 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    private function jpeg(): string
    {
        $image = imagecreatetruecolor(20, 20);
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    private function generate(int $count = 3): string
    {
        $this->actingAs($this->developer, 'admin')
            ->post(route('backend.smart_seeder.store'), ['module' => 'Blog', 'count' => $count, 'instruction' => 'Programming services'])
            ->assertSessionHasNoErrors();

        $seeders = SmartSeeder::make()->seeders();
        $this->assertCount(1, $seeders);

        return $seeders[0]['class'];
    }

    public function test_generation_writes_seeder_data_and_photos(): void
    {
        $this->fakeApis($this->aiRecords(3));

        $class = $this->generate();

        $this->assertMatchesRegularExpression('/^BlogSmartSeeder\d{14}$/', $class);
        $this->assertFileExists($this->sandboxPath("database/seeders/Smart/{$class}.php"));

        $data = json_decode(file_get_contents($this->sandboxPath("database/seeders/Smart/data/{$class}.json")), true);
        $this->assertSame('App\\Models\\Blog', $data['meta']['model']);
        $this->assertSame(['images' => 'blog_images'], $data['meta']['collections']);
        $this->assertCount(3, $data['records']);
        $this->assertSame('web-development-1', $data['records'][0]['main']['slug']);
        $this->assertSame('Web development 2 ru', $data['records'][1]['translations']['ru']['title']);
        $this->assertStringNotContainsString('<script', $data['records'][0]['translations']['az']['content']);
        $this->assertCount(3, $data['credits']);

        foreach ($data['records'] as $record) {
            $this->assertFileExists($this->sandboxPath('database/seeders/Smart/' . $record['images']['images'][0]));
        }

        Http::assertSent(function (Request $request) {
            if (!str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            $schema = $request['generationConfig']['responseSchema'];
            $translations = $schema['properties']['records']['items']['properties']['translations'];

            return $request->hasHeader('x-goog-api-key', 'gemini-secret-key')
                && str_contains($request->url(), '/models/gemini-3.8-flash:generateContent')
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && $translations['required'] === self::LOCALES
                && $schema['properties']['records']['minItems'] === 3
                && str_contains($request['contents'][0]['parts'][0]['text'], 'Programming services');
        });

        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.smart_seeder.show', ['seeder' => $class]))
            ->assertOk()
            ->assertSee('Web development 1 en')
            ->assertSee('Jane Doe');

        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.smart_seeder.asset', ['seeder' => $class, 'file' => '1_images.jpg']))
            ->assertOk();
    }

    public function test_running_the_seeder_creates_records_once(): void
    {
        $this->fakeApis($this->aiRecords(3));
        $class = $this->generate();

        require_once $this->sandboxPath("database/seeders/Smart/{$class}.php");

        $this->actingAs($this->developer, 'admin')
            ->post(route('backend.smart_seeder.run', ['seeder' => $class]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(3, Blog::count());
        $this->assertSame(9, DB::table('blog_translations')->count());
        $this->assertSame('Fast sites 3 en', Blog::where('slug', 'web-development-3')->first()->translate('en')->subtitle);

        $documents = Document::where('manipulationable_type', 'blog')->where('collection_name', 'blog_images')->get();
        $this->assertCount(3, $documents);
        Storage::disk('documents')->assertExists($documents->first()->getAttributes()['document']);
        $this->assertSame(1, Blog::first()->files()->count());

        $this->actingAs($this->developer, 'admin')->post(route('backend.smart_seeder.run', ['seeder' => $class]));

        $this->assertSame(3, Blog::count());
        $this->assertSame(3, Document::count());
    }

    public function test_slugs_are_made_unique_against_existing_rows(): void
    {
        Blog::create(['slug' => 'web-development-1', 'status' => 1, 'order' => 1]);
        $this->fakeApis($this->aiRecords(2, ['slug' => 'Web Development 1']));

        $class = $this->generate(2);
        $data = json_decode(file_get_contents($this->sandboxPath("database/seeders/Smart/data/{$class}.json")), true);

        $this->assertSame(['web-development-1-2', 'web-development-1-3'], array_column(array_column($data['records'], 'main'), 'slug'));
    }

    public function test_incomplete_ai_response_writes_nothing(): void
    {
        $response = $this->aiRecords(2);
        unset($response['records'][1]['translations']['ru']);
        $this->fakeApis($response);
        $before = $this->sandboxSnapshot();

        $this->actingAs($this->developer, 'admin')
            ->from(route('backend.smart_seeder.index'))
            ->post(route('backend.smart_seeder.store'), ['module' => 'Blog', 'count' => 2, 'instruction' => 'Anything'])
            ->assertRedirect(route('backend.smart_seeder.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, '[ru] is missing'));

        $this->assertSame($before, $this->sandboxSnapshot());
    }

    public function test_gemini_errors_are_reported_safely(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => "Quota 'x' exceeded\nnow"]], 429)]);

        $this->actingAs($this->developer, 'admin')
            ->from(route('backend.smart_seeder.index'))
            ->post(route('backend.smart_seeder.store'), ['module' => 'Blog', 'count' => 2, 'instruction' => 'Anything'])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'rate limit')
                && !str_contains($message, "'") && !str_contains($message, "\n"));

        $this->assertDirectoryDoesNotExist($this->sandboxPath('database/seeders/Smart'));
    }

    public function test_missing_keys_block_generation(): void
    {
        AiSetting::current()->update(['pexels_api_key' => null]);
        Http::fake();

        $this->actingAs($this->developer, 'admin')
            ->post(route('backend.smart_seeder.store'), ['module' => 'Blog', 'count' => 2, 'instruction' => 'Anything'])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Pexels'));

        Http::assertNothingSent();
    }

    public function test_settings_are_encrypted_and_never_rendered(): void
    {
        $this->actingAs($this->developer, 'admin')
            ->put(route('backend.smart_seeder.settings'), ['gemini_api_key' => 'new-gemini-key', 'gemini_model' => 'gemini-3.5-flash-lite', 'pexels_api_key' => ''])
            ->assertRedirect(route('backend.smart_seeder.index'));

        $settings = AiSetting::current();
        $this->assertSame('new-gemini-key', $settings->gemini_api_key);
        $this->assertSame('pexels-secret-key', $settings->pexels_api_key);
        $this->assertSame('gemini-3.5-flash-lite', $settings->gemini_model);
        $this->assertStringNotContainsString('new-gemini-key', DB::table('ai_settings')->value('gemini_api_key'));

        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.smart_seeder.index'))
            ->assertOk()
            ->assertDontSee('new-gemini-key')
            ->assertDontSee('pexels-secret-key');
    }

    public function test_access_is_developer_only_and_follows_the_generator_flag(): void
    {
        $admin = $this->makeAdmin('Admin', 'admin@example.com');

        $this->actingAs($admin, 'admin')->get(route('backend.smart_seeder.index'))->assertForbidden();
        $this->actingAs($admin, 'admin')->post(route('backend.smart_seeder.store'))->assertForbidden();
        $this->actingAs($admin, 'admin')->get(route('backend.dashboard'))->assertDontSee(route('backend.smart_seeder.index'), false);
        $this->actingAs($this->developer, 'admin')->get(route('backend.dashboard'))->assertSee(route('backend.smart_seeder.index'), false);

        config(['crud-generator.enabled' => false]);

        $this->actingAs($this->developer, 'admin')->get(route('backend.smart_seeder.index'))->assertNotFound();
    }

    public function test_unknown_seeders_and_unsafe_asset_names_return_404(): void
    {
        $this->actingAs($this->developer, 'admin')->get(route('backend.smart_seeder.show', ['seeder' => 'Nope']))->assertNotFound();
        $this->actingAs($this->developer, 'admin')->post(route('backend.smart_seeder.run', ['seeder' => 'Nope']))->assertNotFound();

        $this->fakeApis($this->aiRecords(1));
        $class = $this->generate(1);
        File::put($this->sandboxPath('database/seeders/Smart/data/secret.txt'), 'x');

        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.smart_seeder.asset', ['seeder' => $class, 'file' => '..%2Fdata%2Fsecret.txt']))
            ->assertNotFound();
    }
}
