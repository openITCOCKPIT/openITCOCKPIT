<?php

namespace App\Test\TestCase\itnovum\openITCOCKPIT\Core\Views;

use DOMDocument;
use DOMXPath;
use itnovum\openITCOCKPIT\Core\Views\BBCMarkDownParser;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * BBCMarkDownParser Test Case
 *
 * Run without the application's bootstrap:
 * vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php \
 *     tests/TestCase/itnovum/openITCOCKPIT/Core/Views/BBCMarkDownParserTest.php
 */
class BBCMarkDownParserTest extends TestCase
{

    /**
     * A recognized opening tag selects the unchanged BBCode renderer for all text.
     *
     * @param string $text
     * @return void
     */
    #[DataProvider('bbCodeCases')]
    public function testBbCodeUsesExistingRenderer(string $text): void
    {
        $BBCodeParser = new BBCodeParser();
        $BBCMarkDownParser = new BBCMarkDownParser();
        $expected = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($text, true));

        self::assertSame($expected, $BBCMarkDownParser->render($text));
        self::assertSame($expected, $BBCMarkDownParser->render($text, true));
    }

    /**
     * @return void
     */
    public function testBbCodeAndMarkdownAreNotCombined(): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        self::assertSame('## <strong>**test**</strong>', $BBCMarkDownParser->render('## [b]**test**[/b]'));
        self::assertSame('<p align="center">**test**</p>', $BBCMarkDownParser->render('[center]**test**[/center]'));
        self::assertSame('<h2>test</h2>', $BBCMarkDownParser->render('## test'));
    }

    /**
     * @param string $text
     * @param array $expectedNodes
     * @return void
     */
    #[DataProvider('markdownCases')]
    public function testMarkdownWithoutBbCode(string $text, array $expectedNodes): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        $DOMXPath = $this->document($BBCMarkDownParser->render($text));
        foreach ($expectedNodes as $query => $expected) {
            $actual = array_map(function ($node) {
                return trim($node->textContent);
            }, iterator_to_array($DOMXPath->query($query)));
            self::assertSame($expected, $actual, $query);
        }
    }

    /**
     * @param string $text
     * @param bool $escapedNewlines
     * @return void
     */
    #[DataProvider('newlines')]
    public function testMarkdownLineBreaks(string $text, bool $escapedNewlines): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        self::assertSame("<p>first<br />\nsecond</p>", $BBCMarkDownParser->render($text, $escapedNewlines));
    }

    /**
     * @return void
     */
    public function testEscapedLineBreaksAreDecodedBeforeMarkdownParsing(): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        $DOMXPath = $this->document($BBCMarkDownParser->render('# first\n\n## second\n\n- item', true));
        self::assertSame('first', $DOMXPath->evaluate('string(//h1)'));
        self::assertSame('second', $DOMXPath->evaluate('string(//h2)'));
        self::assertSame('item', $DOMXPath->evaluate('string(//li)'));
        self::assertSame('<p>first\nsecond</p>', $BBCMarkDownParser->render('first\nsecond'));
        self::assertSame('<p><code>C:\new\tools</code></p>', $BBCMarkDownParser->render('`C:\new\tools`'));
    }

    /**
     * @return void
     */
    public function testEmptyInputAndRepeatedCalls(): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        self::assertSame('', $BBCMarkDownParser->render(null));
        self::assertSame('', $BBCMarkDownParser->render(''));
        self::assertStringContainsString('<a href=', $BBCMarkDownParser->render("[label][ref]\n\n[ref]: https://example.test"));
        self::assertSame('<p>[ref]</p>', $BBCMarkDownParser->render('[ref]'));
        $BBCMarkDownParser->render('[b]unclosed');
        self::assertSame('<p>plain</p>', $BBCMarkDownParser->render('plain'));
    }

    /**
     * @param string $text
     * @return void
     */
    #[DataProvider('unsafeInput')]
    public function testNeitherRendererProducesExecutableHtml(string $text): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        $DOMXPath = $this->document($BBCMarkDownParser->render($text));
        self::assertCount(0, $DOMXPath->query('//script | //iframe | //object | //svg | //math | //embed'));
        foreach ($DOMXPath->query('//@*') as $attribute) {
            self::assertFalse(str_starts_with(strtolower($attribute->name), 'on'));
            if (in_array($attribute->name, ['href', 'src'], true)) {
                self::assertDoesNotMatchRegularExpression('/^(javascript|vbscript|data:text):/i', $attribute->value);
            }
            if ($attribute->name === 'style') {
                self::assertDoesNotMatchRegularExpression('/expression|url\(|var\(|background/i', $attribute->value);
            }
        }
    }

    /**
     * @return array
     */
    public static function bbCodeCases(): array
    {
        $cases = [
            'editor color' => ["[color='#00C851']color[/color]"],
            'editor size' => ["[text='x-large']size[/text]"],
            'bold' => ['[b]bold[/b]'],
            'italic' => ['[i]italic[/i]'],
            'underline' => ['[u]underline[/u]'],
            'left' => ['[left]left[/left]'],
            'center' => ['[center]center[/center]'],
            'right' => ['[right]right[/right]'],
            'justify' => ['[justify]block[/justify]'],
            'editor link' => ["[url='https://example.test/' tab]link[/url]"],
            'uppercase tag' => ['[B]bold[/B]'],
            'color' => ['[color=red]color[/color]'],
            'quoted option' => ['[color="red"]color[/color]'],
            'whitespace before option' => ["[color = 'red']color[/color]"],
            'link with label' => ['[url=https://example.test]link[/url]'],
            'link body' => ['[url]https://example.test[/url]'],
            'image' => ['[img]https://example.test/image.png[/img]'],
            'code' => ['[code]**text**[/code]'],
            'nested tags' => ['[b][i]nested[/i][/b]'],
            'unclosed tag' => ['[b]unclosed'],
            'crossing tags' => ['[b][i]cross[/b][/i]'],
            'real and encoded line breaks' => ["[b]first\nsecond\\nthird\\r\\nfourth[/b]"],
            'BBCode in later paragraph' => ["# Heading\n\n- first\n\n[b]last[/b]"],
            'BBCode in inline code still selects BBCode' => ['`[b]text[/b]`'],
            'BBCode in fenced code still selects BBCode' => ["```\n[b]text[/b]\n```"],
            'escaped opening still selects BBCode' => ['\[b]text\[/b]'],
            'Markdown link named after a tag selects BBCode' => ['[b](https://example.test)']
        ];
        foreach (['xx-small', 'x-small', 'small', 'large', 'x-large', 'xx-large'] as $tag) {
            $cases['legacy size ' . $tag] = ['[' . $tag . ']size[/' . $tag . ']'];
        }
        // Leading Markdown proves which renderer was selected, even for editor-only tags.
        foreach ($cases as &$case) {
            $case[0] = "## Heading\n" . $case[0];
        }
        return $cases;
    }

    /**
     * @return array
     */
    public static function markdownCases(): array
    {
        $cases = [
            'consecutive headings' => ["# test\n## test", ['//h1' => ['test'], '//h2' => ['test']]],
            'hashes on the same line' => ['# test ## test', ['//h1' => ['test ## test'], '//h2' => []]],
            'plain text' => ['Advisory assessment:', ['//p' => ['Advisory assessment:']]],
            'unknown tag' => ['## [OK] text', ['//h2' => ['[OK] text']]],
            'tag prefix is not a tag' => ['## [bold]text[/bold]', ['//h2' => ['[bold]text[/bold]']]],
            'closing tag alone' => ['## [/b] text', ['//h2' => ['[/b] text']]],
            'incomplete opening' => ['## [b text', ['//h2' => ['[b text']]],
            'unordered list' => ["- first\n- second", ['//ul/li' => ['first', 'second']]],
            'ordered list' => ["1. first\n2. second", ['//ol/li' => ['first', 'second']]],
            'nested list' => ["- parent\n  - child", ['//ul/li/ul/li' => ['child']]],
            'emphasis' => ['**bold** *italic* ~~removed~~', ['//strong' => ['bold'], '//em' => ['italic'], '//del' => ['removed']]],
            'quotation' => ['> quoted', ['//blockquote/p' => ['quoted']]],
            'link' => ['[link](https://example.test)', ['//a/@href' => ['https://example.test']]],
            'inline code' => ['`# **literal**`', ['//code' => ['# **literal**'], '//h1 | //strong' => []]],
            'fenced code' => ["```text\n# **literal**\n```", ['//pre/code' => ['# **literal**'], '//h1 | //strong' => []]],
            'table' => ["| name | value |\n| --- | --- |\n| a | b |", ['//th' => ['name', 'value'], '//td' => ['a', 'b']]],
            'definition list from Parsedown Extra' => ["Term\n: Definition", ['//dl/dt' => ['Term'], '//dl/dd' => ['Definition']]],
            'raw HTML stays text' => ['<strong>literal</strong>', ['//p' => ['<strong>literal</strong>'], '//strong' => []]],
            'escaped heading' => ['\# literal', ['//p' => ['# literal'], '//h1' => []]]
        ];
        foreach (range(1, 6) as $level) {
            $cases['heading level ' . $level] = [str_repeat('#', $level) . ' test', ['//h' . $level => ['test']]];
        }
        return $cases;
    }

    /**
     * @return array
     */
    public static function newlines(): array
    {
        return [
            ["first\nsecond", false],
            ["first\r\nsecond", false],
            ["first\rsecond", false],
            ['first\nsecond', true],
            ['first\r\nsecond', true],
            ['first\rsecond', true]
        ];
    }

    /**
     * @return array
     */
    public static function unsafeInput(): array
    {
        $cases = [];
        foreach ([
            '<script>alert(1)</script><img src=x onerror="alert(1)">',
            '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
            '[link](javascript:alert(1))',
            '![image](data:image/svg+xml,<svg onload="alert(1)">)',
            '[url=javascript:alert(1)]link[/url]',
            '[img]javascript:alert(1)[/img]',
            '[color=red;background-image:url(https://example.test)]text[/color]',
            '[color="expression(alert(1))"]text[/color]'
        ] as $index => $source) {
            $cases['input ' . $index] = [$source];
            $cases['BBCode prefix ' . $index] = ['[b]prefix[/b] ' . $source];
        }
        return $cases;
    }

    /**
     * @param string $html
     * @return DOMXPath
     */
    private function document(string $html): DOMXPath
    {
        $DOMDocument = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $DOMDocument->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return new DOMXPath($DOMDocument);
    }
}
