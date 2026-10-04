<?php

namespace Tests\Unit\CrudGenerator;

use App\Services\CrudGenerator\CrudGeneratorException;
use App\Services\CrudGenerator\FileOperation;
use PHPUnit\Framework\TestCase;

class FileOperationTest extends TestCase
{
    private const FILE = "<?php\n\n\$modules = [\n    'blogs',\n];\n";

    public function test_injects_region_before_anchor_line(): void
    {
        $operation = FileOperation::inject('x.php', 'Product', '];', "    'products',", before: true, indent: '    ');

        $this->assertSame(
            "<?php\n\n\$modules = [\n    'blogs',\n    // crud-generator:start Product\n    'products',\n    // crud-generator:end Product\n];\n",
            $operation->apply(self::FILE)
        );
    }

    public function test_injects_region_after_anchor_line(): void
    {
        $operation = FileOperation::inject('x.php', 'Product', "'blogs',", "    'products',", indent: '    ');

        $this->assertStringContainsString(
            "    'blogs',\n    // crud-generator:start Product\n    'products',\n    // crud-generator:end Product\n];",
            $operation->apply(self::FILE)
        );
    }

    public function test_injection_is_idempotent(): void
    {
        $operation = FileOperation::inject('x.php', 'Product', '];', "    'products',", before: true);
        $once = $operation->apply(self::FILE);

        $this->assertSame($once, $operation->apply($once));
    }

    public function test_region_of_another_module_with_same_prefix_is_not_reused(): void
    {
        $category = FileOperation::inject('x.php', 'ProductCategory', '];', "    'product_categories',", before: true);
        $product = FileOperation::inject('x.php', 'Product', '];', "    'products',", before: true);

        $updated = $product->apply($category->apply(self::FILE));

        $this->assertStringContainsString('// crud-generator:start Product' . "\n", $updated);
        $this->assertSame(1, substr_count($updated, "'products',"));
    }

    public function test_blade_markers(): void
    {
        $operation = FileOperation::inject('x.blade.php', 'Product', '@endcan', '<a></a>', FileOperation::STYLE_BLADE);

        $this->assertSame('{{-- crud-generator:start Product --}}', $operation->startMarker());
        $this->assertStringContainsString("@endcan\n{{-- crud-generator:start Product --}}\n<a></a>\n{{-- crud-generator:end Product --}}\n", $operation->apply("@endcan\n"));
    }

    public function test_crlf_files_keep_crlf(): void
    {
        $operation = FileOperation::inject('x.php', 'Product', '];', "'products',", before: true);

        $updated = $operation->apply(str_replace("\n", "\r\n", self::FILE));

        $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $updated));
    }

    public function test_missing_anchor_throws(): void
    {
        $this->expectException(CrudGeneratorException::class);

        FileOperation::inject('x.php', 'Product', 'nope', "'products',")->apply(self::FILE);
    }

    public function test_duplicate_anchor_throws(): void
    {
        $this->expectException(CrudGeneratorException::class);

        FileOperation::inject('x.php', 'Product', "'", "'products',")->apply(self::FILE);
    }

    public function test_diff_marks_inserted_lines_with_context(): void
    {
        $operation = FileOperation::inject('x.php', 'Product', '];', "    'products',", before: true);
        $diff = FileOperation::diff(self::FILE, $operation->apply(self::FILE), 1);

        $this->assertSame(
            [
                ['type' => 'context', 'line' => 4, 'text' => "    'blogs',"],
                ['type' => 'add', 'line' => 5, 'text' => '// crud-generator:start Product'],
                ['type' => 'add', 'line' => 6, 'text' => "    'products',"],
                ['type' => 'add', 'line' => 7, 'text' => '// crud-generator:end Product'],
                ['type' => 'context', 'line' => 8, 'text' => '];'],
            ],
            $diff
        );
    }
}
