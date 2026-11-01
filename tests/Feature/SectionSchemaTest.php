<?php

namespace Tests\Feature;

use App\Content\SectionSchema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SectionSchemaTest extends TestCase
{
    private function valid(): array
    {
        return ['id' => 'hero', 'type' => 'hero', 'title' => ['en' => 'Hi', 'ar' => 'مرحبا'],
            'buttons' => [['label' => ['en' => 'Go'], 'href' => '/consultation', 'style' => 'gold']],
            'style' => ['theme' => 'dark', 'overlay' => 40, 'padding' => 'lg', 'align' => 'start'],
            'motion' => ['preset' => 'fade-up', 'intensity' => 100, 'delay' => 0]];
    }

    public function test_valid_section_passes(): void
    {
        SectionSchema::validate([$this->valid()]);
        $this->assertTrue(true);
    }

    public static function attacks(): array
    {
        return [
            'unknown type' => [fn ($s) => [...$s, 'type' => 'rawHtml']],
            'unknown field' => [fn ($s) => [...$s, 'onclick' => 'x']],
            'script in title' => [fn ($s) => [...$s, 'title' => ['en' => '<script>alert(1)</script>']]],
            'html in title' => [fn ($s) => [...$s, 'title' => ['en' => '<img src=x onerror=alert(1)>']]],
            'javascript link' => [fn ($s) => [...$s, 'buttons' => [['label' => ['en' => 'x'], 'href' => 'javascript:alert(1)', 'style' => 'gold']]]],
            'protocol-relative link' => [fn ($s) => [...$s, 'buttons' => [['label' => ['en' => 'x'], 'href' => '//evil.example', 'style' => 'gold']]]],
            'http link' => [fn ($s) => [...$s, 'buttons' => [['label' => ['en' => 'x'], 'href' => 'http://evil.example', 'style' => 'gold']]]],
            'missing english' => [fn ($s) => [...$s, 'title' => ['ar' => 'x']]],
            'bad theme' => [fn ($s) => [...$s, 'style' => [...$s['style'], 'theme' => 'neon']]],
            'overlay out of range' => [fn ($s) => [...$s, 'style' => [...$s['style'], 'overlay' => 99]]],
            'unknown motion' => [fn ($s) => [...$s, 'motion' => ['preset' => 'spin']]],
            'motion delay too long' => [fn ($s) => [...$s, 'motion' => ['preset' => 'fade-up', 'delay' => 5000]]],
            'media path traversal' => [fn ($s) => [...$s, 'media' => '../../.env']],
            'slide without image' => [fn ($s) => [...$s, 'slides' => [['id' => 's1', 'title' => ['en' => 'x']]]]],
            'slide with html' => [fn ($s) => [...$s, 'slides' => [['id' => 's1', 'media' => 'hero-x', 'title' => ['en' => '<b>x</b>']]]]],
            'slide unknown field' => [fn ($s) => [...$s, 'slides' => [['id' => 's1', 'media' => 'hero-x', 'onload' => 'x']]]],
            'slide region injection' => [fn ($s) => [...$s, 'slides' => [['id' => 's1', 'media' => 'hero-x', 'region' => '../x']]]],
            'too many slides' => [fn ($s) => [...$s, 'slides' => array_fill(0, 11, ['id' => 's', 'media' => 'hero-x'])]],
        ];
    }

    #[DataProvider('attacks')]
    public function test_rejected(callable $mutate): void
    {
        $this->expectException(ValidationException::class);
        SectionSchema::validate([$mutate($this->valid())]);
    }
}
