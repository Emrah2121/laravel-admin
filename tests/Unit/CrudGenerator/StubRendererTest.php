<?php

namespace Tests\Unit\CrudGenerator;

use App\Services\CrudGenerator\CrudGeneratorException;
use App\Services\CrudGenerator\StubRenderer;
use PHPUnit\Framework\TestCase;

class StubRendererTest extends TestCase
{
    private StubRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new StubRenderer(sys_get_temp_dir());
    }

    public function test_replaces_whitelisted_placeholders_and_keeps_blade_echoes(): void
    {
        $output = $this->renderer->renderString(
            "class [% class %] {}\n{{ \$[%variable%]->id }}",
            ['class' => 'Product', 'variable' => 'product']
        );

        $this->assertSame("class Product {}\n{{ \$product->id }}", $output);
    }

    public function test_rejects_unknown_placeholders(): void
    {
        $this->expectException(CrudGeneratorException::class);

        $this->renderer->renderString('[% class %]', ['class' => 'Product', 'evil' => '<?php system(1);']);
    }

    public function test_rejects_missing_values(): void
    {
        $this->expectException(CrudGeneratorException::class);

        $this->renderer->renderString('[% class %] [% table %]', ['class' => 'Product']);
    }

    public function test_values_are_not_rendered_recursively(): void
    {
        $output = $this->renderer->renderString('[% class %]', ['class' => '[% table %]']);

        $this->assertSame('[% table %]', $output);
    }
}
