<?php

namespace Tests\Feature\CrudGenerator;

use App\Services\CrudGenerator\CrudGenerator;
use App\Services\CrudGenerator\ModuleSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpecValidatorTest extends TestCase
{
    use InteractsWithSandbox;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();
    }

    private function errors(ModuleSpec $spec): array
    {
        return CrudGenerator::make()->validate($spec);
    }

    public function test_sample_spec_is_valid(): void
    {
        $this->assertSame([], $this->errors($this->sampleSpec()));
    }

    public function test_derived_names(): void
    {
        $spec = ModuleSpec::fromArray(['name' => 'blog post', 'fields' => []]);

        $this->assertSame('BlogPost', $spec->name);
        $this->assertSame('BlogPosts', $spec->plural);
        $this->assertSame('blog_posts', $spec->key());
        $this->assertSame('blog_post_translations', $spec->translationTable());
        $this->assertSame('blog_post_id', $spec->foreignKey());
        $this->assertSame('blog-posts', $spec->routePath());
        $this->assertSame('blog_post', $spec->routeParameter());
        $this->assertSame('blog_posts index', $spec->permission('index'));
        $this->assertSame('Blog Posts', $spec->label);
    }

    public static function invalidNames(): array
    {
        return [
            'php reserved word' => ['Class'],
            'type keyword' => ['String'],
            'imported class' => ['Controller'],
            'translation suffix' => ['PageTranslation'],
            'digits first' => ['1Product'],
            'symbols' => ['Pro$duct'],
            'too short' => ['P'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_rejects_invalid_module_names(string $name): void
    {
        $this->assertArrayHasKey('name', $this->errors($this->sampleSpec(['name' => $name])));
    }

    public static function invalidFieldNames(): array
    {
        return [
            'camelCase' => ['subTitle'],
            'leading digit' => ['1title'],
            'double underscore' => ['sub__title'],
            'reserved column' => ['created_at'],
            'locale' => ['locale'],
            'foreign key' => ['gadget_id'],
            'base column' => ['slug'],
            'model relation' => ['translations'],
            'php keyword' => ['class'],
            'injection attempt' => ["title'];system('x');//"],
        ];
    }

    #[DataProvider('invalidFieldNames')]
    public function test_rejects_invalid_field_names(string $field): void
    {
        $spec = $this->sampleSpec();
        $data = $spec->toArray();
        $data['fields'][] = ['name' => $field, 'label' => 'Extra', 'type' => 'string'];

        $this->assertArrayHasKey('fields.6.name', $this->errors(ModuleSpec::fromArray($data)));
    }

    public function test_rejects_duplicate_field_names(): void
    {
        $data = $this->sampleSpec()->toArray();
        $data['fields'][] = $data['fields'][0];

        $this->assertArrayHasKey('fields.6.name', $this->errors(ModuleSpec::fromArray($data)));
    }

    public function test_rejects_labels_with_markup_or_quotes(): void
    {
        $data = $this->sampleSpec()->toArray();
        $data['label'] = "Gadgets'); alert(1);//";
        $data['fields'][0]['label'] = '<script>x</script>';

        $errors = $this->errors(ModuleSpec::fromArray($data));

        $this->assertArrayHasKey('label', $errors);
        $this->assertArrayHasKey('fields.0.label', $errors);
    }

    public function test_requires_a_translatable_string_field(): void
    {
        $spec = $this->sampleSpec(['fields' => [
            ['name' => 'sku', 'label' => 'SKU', 'type' => 'string', 'translatable' => false],
        ]]);

        $this->assertArrayHasKey('fields', $this->errors($spec));
    }

    public function test_image_fields_are_never_translatable(): void
    {
        $spec = $this->sampleSpec(['fields' => [
            ['name' => 'photo', 'label' => 'Photo', 'type' => 'image', 'translatable' => true],
        ]]);

        $this->assertFalse($spec->fields[0]->translatable);
    }

    public function test_rejects_bad_types_and_lengths(): void
    {
        $spec = $this->sampleSpec(['fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'string', 'translatable' => true, 'max_length' => 500],
            ['name' => 'price', 'label' => 'Price', 'type' => 'decimal'],
        ]]);

        $errors = $this->errors($spec);

        $this->assertArrayHasKey('fields.0.max_length', $errors);
        $this->assertArrayHasKey('fields.1.type', $errors);
    }

    public function test_rejects_existing_blog_module(): void
    {
        $errors = $this->errors($this->sampleSpec(['name' => 'Blog']));

        $this->assertArrayHasKey('name', $errors);
        $messages = implode(' ', $errors['name']);
        $this->assertStringContainsString('app/Models/Blog.php', $messages);
        $this->assertStringContainsString('backend.blogs.index', $messages);
    }

    public function test_rejects_names_colliding_with_existing_models(): void
    {
        $this->assertArrayHasKey('name', $this->errors($this->sampleSpec(['name' => 'Setting'])));
    }

    public function test_plural_must_singularize_back(): void
    {
        $errors = $this->errors($this->sampleSpec(['plural' => 'Widgets']));

        $this->assertArrayHasKey('plural', $errors);
    }
}
