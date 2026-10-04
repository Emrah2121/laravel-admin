<?php

namespace App\Services\CrudGenerator;

final class FileOperation
{
    public const CREATE = 'create';

    public const INJECT = 'inject';

    public const PUT = 'put';

    public const STYLE_PHP = 'php';

    public const STYLE_BLADE = 'blade';

    private function __construct(
        public readonly string $type,
        public readonly string $path,
        public readonly ?string $contents = null,
        public readonly ?string $module = null,
        public readonly ?string $anchor = null,
        public readonly ?string $body = null,
        public readonly string $style = self::STYLE_PHP,
        public readonly bool $before = false,
        public readonly string $indent = '',
    ) {}

    public static function create(string $path, string $contents): self
    {
        return new self(self::CREATE, $path, $contents);
    }

    public static function put(string $path, string $contents): self
    {
        return new self(self::PUT, $path, $contents);
    }

    /**
     * Inserts $body, wrapped in managed markers, on its own lines next to the
     * single line that contains $anchor.
     */
    public static function inject(
        string $path,
        string $module,
        string $anchor,
        string $body,
        string $style = self::STYLE_PHP,
        bool $before = false,
        string $indent = '',
    ): self {
        return new self(self::INJECT, $path, null, $module, $anchor, $body, $style, $before, $indent);
    }

    public function startMarker(): string
    {
        return $this->marker('start');
    }

    public function endMarker(): string
    {
        return $this->marker('end');
    }

    public function hasRegion(string $current): bool
    {
        // The marker must end its line, so "Product" never matches "ProductCategory" or "Product labels".
        return preg_match('/' . preg_quote($this->startMarker(), '/') . '\r?$/m', $current) === 1;
    }

    public function anchorCount(string $current): int
    {
        return substr_count($current, $this->anchor);
    }

    public function apply(?string $current): string
    {
        if ($this->type !== self::INJECT) {
            return $this->contents;
        }

        if ($this->hasRegion($current)) {
            return $current;
        }

        if ($this->anchorCount($current) !== 1) {
            throw new CrudGeneratorException("Injection anchor not found exactly once in {$this->path}.");
        }

        $eol = str_contains($current, "\r\n") ? "\r\n" : "\n";
        $region = implode($eol, [
            $this->indent . $this->startMarker(),
            rtrim(str_replace(["\r\n", "\n"], $eol, $this->body)),
            $this->indent . $this->endMarker(),
        ]) . $eol;

        $position = strpos($current, $this->anchor);

        if ($this->before) {
            $lineStart = strrpos(substr($current, 0, $position), "\n");
            $offset = $lineStart === false ? 0 : $lineStart + 1;

            // Keep the blank line that separated the anchor from the code above it.
            if (str_ends_with(substr($current, 0, $offset), $eol . $eol)) {
                $region .= $eol;
            }
        } else {
            // Anchors may span several lines; insert after the line where the anchor ends.
            $lineEnd = strpos($current, "\n", $position + strlen($this->anchor) - 1);
            $offset = $lineEnd === false ? strlen($current) : $lineEnd + 1;

            if ($lineEnd === false) {
                $region = $eol . $region;
            }
        }

        return substr($current, 0, $offset) . $region . substr($current, $offset);
    }

    /**
     * Inserted lines with surrounding context, for previews.
     *
     * @return array<int, array{type: string, line: int, text: string}>
     */
    public static function diff(string $old, string $new, int $context = 3): array
    {
        $oldLines = preg_split('/\r\n|\n/', $old);
        $newLines = preg_split('/\r\n|\n/', $new);

        $prefix = 0;
        $max = min(count($oldLines), count($newLines));

        while ($prefix < $max && $oldLines[$prefix] === $newLines[$prefix]) {
            $prefix++;
        }

        $suffix = 0;

        while (
            $suffix < $max - $prefix
            && $oldLines[count($oldLines) - 1 - $suffix] === $newLines[count($newLines) - 1 - $suffix]
        ) {
            $suffix++;
        }

        $diff = [];
        $addedEnd = count($newLines) - $suffix;

        for ($i = max(0, $prefix - $context); $i < min(count($newLines), $addedEnd + $context); $i++) {
            $diff[] = [
                'type' => $i >= $prefix && $i < $addedEnd ? 'add' : 'context',
                'line' => $i + 1,
                'text' => $newLines[$i],
            ];
        }

        foreach (array_slice($oldLines, $prefix, count($oldLines) - $suffix - $prefix) as $offset => $removed) {
            $diff[] = ['type' => 'remove', 'line' => $prefix + $offset + 1, 'text' => $removed];
        }

        return $diff;
    }

    private function marker(string $edge): string
    {
        $text = "crud-generator:{$edge} {$this->module}";

        return $this->style === self::STYLE_BLADE ? "{{-- {$text} --}}" : "// {$text}";
    }
}
