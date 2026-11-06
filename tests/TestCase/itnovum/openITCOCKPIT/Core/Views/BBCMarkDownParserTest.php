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
     * Keep the plain BBCode and combined rendering APIs separate.
     *
     * @return void
     */
    public function testCombinedParserExtendsBbCodeWithoutChangingPlainRendering(): void
    {
        $BBCodeParser = new BBCodeParser();
        $BBCMarkDownParser = new BBCMarkDownParser();
        $source = '## [b]**mixed**[/b]';

        self::assertInstanceOf(BBCodeParser::class, $BBCMarkDownParser);
        self::assertSame('## <strong>**mixed**</strong>', $BBCodeParser->asHtml($source));
        self::assertSame('<h2><strong><strong>mixed</strong></strong></h2>', $BBCMarkDownParser->render($source));
    }

    /**
     * Preserve inherited BBCode rendering when reusing the parser.
     *
     * @param string|null $source
     * @param bool $breaks
     * @param string $expected
     * @return void
     */
    #[DataProvider('legacyRendering')]
    public function testExistingApiStaysUnchangedAfterRenderingMixedMarkup(?string $source, bool $breaks, string $expected): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        self::assertInstanceOf(\JBBCode\Parser::class, $BBCMarkDownParser->getParser());
        self::assertSame($expected, $BBCMarkDownParser->asHtml($source, $breaks));
        $BBCMarkDownParser->render('[b]**mixed**[/b]', BBCMarkDownParser::LITERAL_NEWLINES);
        self::assertSame($expected, $BBCMarkDownParser->asHtml($source, $breaks));
    }

    /**
     * @return void
     */
    public function testCrossingAndUnclosedTagsKeepTheirOriginalText(): void
    {
        foreach (['[b][i]cross[/b][/i]', '[b]open', '[color=red]a[/color][/b][i]open'] as $source) {
            $DOMXPath = $this->document($this->render($source));
            self::assertCount(0, $DOMXPath->query('//strong | //em'));
            self::assertStringContainsString('[', $DOMXPath->evaluate('string(//body)'));
        }
        self::assertSame('[b][i]cross[/b][/i]', $this->render('[b][i]cross[/b][/i]'));
    }

    /**
     * @param string $source
     * @param string $query
     * @param string $text
     * @return void
     */
    #[DataProvider('mixedSyntax')]
    public function testMixedSyntax(string $source, string $query, string $text): void
    {
        $nodes = $this->document($this->render($source))->query($query);
        self::assertCount(1, $nodes);
        self::assertSame($text, $nodes[0]->textContent);
    }

    /**
     * @param string $tag
     * @param string $option
     * @param string $query
     * @return void
     */
    #[DataProvider('registeredFamilies')]
    public function testExistingDefinitionsRemainTheSource(string $tag, string $option, string $query): void
    {
        $source = '[' . $tag . $option . ']content[/' . $tag . ']';
        $BBCodeParser = new BBCodeParser();
        self::assertTrue($BBCodeParser->getParser()->codeExists($tag, $option !== ''));
        $oldText = $this->document($BBCodeParser->asHtml($source, false))->evaluate('string(//body)');
        $nodes = $this->document($this->render($source))->query('//' . $query);
        self::assertCount(1, $nodes);
        self::assertSame(trim($oldText), trim($nodes[0]->textContent));
    }

    /**
     * @return void
     */
    public function testFormattingSpansBlankLinesHeadingsAndLists(): void
    {
        $html = $this->render("[color=red]First\nline\n\nSecond\n\n## Heading\n\n- item\n- next\n\nLast[/color]\n\nOutside");
        $DOMXPath = $this->document($html);
        self::assertCount(2, $DOMXPath->query('//body/span'));
        self::assertStringContainsString("First\nline\n\nSecond", $DOMXPath->query('//body/span')[0]->textContent);
        self::assertCount(1, $DOMXPath->query('//h2/span'));
        self::assertCount(2, $DOMXPath->query('//li/span'));
        self::assertCount(4, $DOMXPath->query('//body/span/br'));
        self::assertCount(0, $DOMXPath->query('//p | //span/ul | //span/h2'));
        self::assertSame('Outside', trim($DOMXPath->evaluate('string(//body/text()[last()])')));
    }

    /**
     * @return void
     */
    public function testAlignmentContainsMultipleBlocks(): void
    {
        $html = $this->render("[center]\nFirst\n\nSecond\n\n- item\n- next\n[/center]\n\nOutside");
        $DOMXPath = $this->document($html);
        self::assertCount(0, $DOMXPath->query('//p'));
        self::assertCount(4, $DOMXPath->query('//div/br'));
        self::assertCount(2, $DOMXPath->query('//div/ul/li'));
        self::assertSame('Outside', trim($DOMXPath->evaluate('string(//body/text()[last()])')));
        self::assertStringNotContainsString('[center]', $html);
    }

    /**
     * Keep Markdown and BBCode syntax literal inside code.
     *
     * @param string $source
     * @param string $expected
     * @return void
     */
    #[DataProvider('literalCode')]
    public function testCodeIsOpaque(string $source, string $expected): void
    {
        $DOMXPath = $this->document($this->render($source));
        self::assertCount(1, $DOMXPath->query('//code'));
        self::assertSame($expected, $DOMXPath->query('//code')[0]->textContent);
        self::assertCount(0, $DOMXPath->query('//code/strong | //code/span'));
    }

    /**
     * @param string $source
     * @param string $innerText
     * @return void
     */
    #[DataProvider('nestedLinks')]
    public function testLinksNeverNest(string $source, string $innerText): void
    {
        $DOMXPath = $this->document($this->render($source));
        self::assertCount(1, $DOMXPath->query('//a'));
        self::assertSame($innerText, $DOMXPath->query('//a')[0]->textContent);
    }

    /**
     * @return void
     */
    public function testImagesAndAttributesStayData(): void
    {
        $html = $this->render('[img="A & B <tag>"]https://example.test/a_b?q="x"&a=[b][/img]');
        $DOMXPath = $this->document($html);
        self::assertCount(1, $DOMXPath->query('//img'));
        self::assertSame('A & B <tag>', $DOMXPath->query('//img')[0]->getAttribute('alt'));
        self::assertSame('https://example.test/a_b?q="x"&a=[b]', $DOMXPath->query('//img')[0]->getAttribute('src'));
        self::assertCount(0, $DOMXPath->query('//strong | //tag'));
        $ImageDOMXPath = $this->document($this->render('[img]https://example.test/a_b.png[/img]'));
        self::assertCount(1, $ImageDOMXPath->query('//img[@src="https://example.test/a_b.png"]'));
    }

    /**
     * @return void
     */
    public function testEscapedUnknownAndPlainText(): void
    {
        $html = $this->render("Advisory assessment:\n[OK] A & B <module>\n\\[b]literal\\[/b]");
        $DOMXPath = $this->document($html);
        self::assertCount(0, $DOMXPath->query('//h1 | //h2 | //strong | //module'));
        self::assertCount(2, $DOMXPath->query('//br'));
        self::assertStringContainsString('[OK] A & B <module>', $DOMXPath->evaluate('string(//body)'));
        self::assertStringContainsString('[b]literal[/b]', $DOMXPath->evaluate('string(//body)'));
        self::assertStringNotContainsString('&amp;amp;', $html);
    }

    /**
     * @param string $source
     * @return void
     */
    #[DataProvider('existingLineBreaks')]
    public function testOrdinaryTextAndBbCodeKeepExistingLineBreaks(string $source): void
    {
        $BBCodeParser = (new BBCodeParser())->asHtml($source);
        $html = $this->render($source);
        // Serializer whitespace does not affect layout; the <br> positions do.
        self::assertSame(str_replace("\n", '', $BBCodeParser), str_replace("\n", '', $html));
        self::assertCount(0, $this->document($html)->query('//p'));
    }

    /**
     * @return void
     */
    public function testWhitespaceOnlyLinesStayVisible(): void
    {
        $source = "\n \nfirst\n \nsecond\n \n";
        self::assertCount(substr_count($source, "\n"), $this->document($this->render($source))->query('//br'));
    }

    /**
     * Reject executable content in plain and mixed formatting.
     *
     * @param string $source
     * @return void
     */
    #[DataProvider('attacks')]
    public function testUntrustedOutputCannotCreateExecutableContent(string $source): void
    {
        $DOMXPath = $this->document($this->render($source));
        self::assertCount(0, $DOMXPath->query('//script | //iframe | //object | //svg | //math | //embed'));
        foreach ($DOMXPath->query('//@*') as $attribute) {
            self::assertFalse(str_starts_with(strtolower($attribute->name), 'on'));
            if (in_array($attribute->name, ['href', 'src'], true)) {
                self::assertDoesNotMatchRegularExpression('/^(javascript|vbscript|data:text):/i', $attribute->value);
            }
            if ($attribute->name === 'style') {
                self::assertDoesNotMatchRegularExpression('/expression|url\(|var\(|background|onclick/i', $attribute->value);
            }
        }
    }

    /**
     * @param string $source
     * @param string $mode
     * @param int $breaks
     * @return void
     */
    #[DataProvider('normalization')]
    public function testExplicitSourceNormalization(string $source, string $mode, int $breaks): void
    {
        self::assertCount($breaks, $this->document($this->render($source, $mode))->query('//br'));
    }

    /**
     * @return void
     */
    public function testLiteralBackslashesAndTransportLimitation(): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        $source = '`C:\\new\\tools\\results`';
        self::assertSame('C:\\new\\tools\\results', $this->document($BBCMarkDownParser->render($source))->query('//code')[0]->textContent);
        self::assertSame('C: ew\\tools\\results', $this->document($this->render($source, BBCMarkDownParser::ESCAPED_LF))->query('//code')[0]->textContent);
        self::assertStringContainsString('\\t', $this->render('path\\tend', BBCMarkDownParser::ESCAPED_LF));
    }

    /**
     * @param string $literal
     * @param string $source
     * @param string $mode
     * @return void
     */
    #[DataProvider('combinedNewlines')]
    public function testDefaultAndExplicitNewlinesKeepTheSameMixedStructure(string $literal, string $source, string $mode): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        self::assertSame($BBCMarkDownParser->render($literal), $this->render($source, $mode));
    }

    /**
     * @return void
     */
    public function testUnknownSourceRepresentationIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('text', 'guess');
    }

    /**
     * @return void
     */
    public function testReferencesAndFormattingDoNotLeakBetweenResults(): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        $linked = $BBCMarkDownParser->render("[label][ref]\n\n[ref]: https://example.test", BBCMarkDownParser::LITERAL_NEWLINES);
        self::assertCount(1, $this->document($linked)->query('//a'));
        self::assertSame('[ref]', $BBCMarkDownParser->render('[ref]', BBCMarkDownParser::LITERAL_NEWLINES));
        foreach (['[color=red]unclosed', '[center]**mixed**[/center]', '[code]unclosed', '[b][i]cross[/b][/i]'] as $source) {
            $BBCMarkDownParser->render($source, BBCMarkDownParser::LITERAL_NEWLINES);
            self::assertSame('<h2>clean</h2>', $BBCMarkDownParser->render('## clean', BBCMarkDownParser::LITERAL_NEWLINES));
        }
    }

    /**
     * @param string $source
     * @return void
     */
    #[DataProvider('incompleteAtomicBlocks')]
    public function testIncompleteAtomicBlocksStayLiteral(string $source): void
    {
        $DOMXPath = $this->document($this->render($source));
        self::assertSame($source, trim($DOMXPath->evaluate('string(//body)')));
        self::assertCount(0, $DOMXPath->query('//a | //img | //strong | //h1 | //ul'));
    }

    /**
     * @return void
     */
    public function testEmptyMalformedAndRepeatedResults(): void
    {
        $BBCMarkDownParser = new BBCMarkDownParser();
        foreach ([null, ''] as $source) {
            self::assertSame('', $BBCMarkDownParser->render($source));
        }
        foreach ([
            '[b]open',
            '[b][i]cross[/b][/i]',
            '[url]https://example.test',
            '[code]**literal**',
            '[color="bad" extra=x]text[/color]',
        ] as $source) {
            self::assertStringContainsString('[', $BBCMarkDownParser->render($source, BBCMarkDownParser::LITERAL_NEWLINES));
            self::assertSame('plain', $BBCMarkDownParser->render('plain', BBCMarkDownParser::LITERAL_NEWLINES));
        }
    }

    /**
     * @return void
     */
    public function testRegistryLookalikesAndLengthDoNotLoseContent(): void
    {
        $source = "\x1A0123456789abcdef:0\x1A __OITC_NODE_0__ " . str_repeat('x', 9000) . ' [b]tail';
        $html = $this->render($source);
        self::assertStringContainsString("\x1A0123456789abcdef:0\x1A", $html);
        self::assertStringContainsString(str_repeat('x', 9000), $html);
        self::assertStringContainsString('[b]tail', $html);
    }

    /**
     * Display the escaped source if rendering fails.
     *
     * @return void
     */
    public function testFailureReturnsNormalizedEscapedOriginalAndReportsNoContent(): void
    {
        $BBCMarkDownParser = $this->getMockBuilder(BBCMarkDownParser::class)
            ->onlyMethods(['renderHtml', 'reportFailure'])
            ->getMock();
        $error = new \RuntimeException('forced failure');
        $BBCMarkDownParser->expects(self::once())
            ->method('renderHtml')
            ->with("<script>\n&")
            ->willThrowException($error);
        $BBCMarkDownParser->expects(self::once())
            ->method('reportFailure')
            ->with($error);

        self::assertSame("<pre>&lt;script&gt;\n&amp;</pre>", $BBCMarkDownParser->render('<script>\\n&', BBCMarkDownParser::ESCAPED_LF));
    }

    /**
     * @return void
     */
    public function testImpossibleAlignmentIsReadableText(): void
    {
        $html = $this->render('## text [center]middle[/center] end');
        self::assertCount(0, $this->document($html)->query('//div'));
        self::assertStringContainsString('[center]middle[/center]', $html);
        $html = $this->render('## [code]**literal**[/code]');
        self::assertCount(0, $this->document($html)->query('//pre'));
        self::assertStringContainsString('[code]**literal**[/code]', $html);
    }

    /**
     * @return void
     */
    public function testAlignmentCanStartWithinOrdinaryText(): void
    {
        $DOMXPath = $this->document($this->render('text [center]middle[/center] end'));
        self::assertSame('middle', $DOMXPath->evaluate('string(//div)'));
        self::assertStringContainsString('text ', $DOMXPath->evaluate('string(//body)'));
        self::assertStringContainsString(' end', $DOMXPath->evaluate('string(//body)'));
    }

    /**
     * @return void
     */
    public function testOrdinaryMarkdownStillUsesParsedown(): void
    {
        $source = "# Heading\n\nParagraph with *emphasis* and ~~deleted~~.\n\n> quotation\n\n1. first\n2. second\n\n---\n\n| column | value |\n| --- | --- |\n| a | b |";
        $DOMXPath = $this->document($this->render($source));
        foreach (['//h1', '//em', '//del', '//blockquote', '//ol', '//hr', '//table'] as $query) {
            self::assertCount(1, $DOMXPath->query($query));
        }
    }

    /**
     * Check rendered content, nesting and attributes for each syntax example.
     *
     * @param string $source
     * @param array $expectedNodes
     * @return void
     */
    #[DataProvider('markdownCases')]
    #[DataProvider('mixedBlockCases')]
    public function testMarkdownAndCombinedStructure(string $source, array $expectedNodes): void
    {
        $html = $this->render($source);
        self::assertStringNotContainsString("\x1A", $html, 'Internal node IDs must not reach the HTML.');
        $DOMXPath = $this->document($html);
        foreach ($expectedNodes as $query => $expected) {
            $actual = array_map(function ($node) {
                return trim($node->textContent);
            }, iterator_to_array($DOMXPath->query($query)));
            self::assertSame($expected, $actual, $query);
        }
    }

    /**
     * @param string $source
     * @param string $query
     * @return void
     */
    #[DataProvider('inlineNesting')]
    public function testEveryInlineFamilyNestsInBothDirections(string $source, string $query): void
    {
        $DOMXPath = $this->document($this->render($source));
        self::assertCount(1, $DOMXPath->query($query));
        self::assertSame('text', $DOMXPath->evaluate('string(//body)'));
    }

    /**
     * @param string $tag
     * @param string $option
     * @param string $query
     * @return void
     */
    #[DataProvider('inlineFamilies')]
    public function testEveryInlineFamilySpansMarkdownBlocks(string $tag, string $option, string $query): void
    {
        $source = '[' . $tag . $option . "]\n## Heading\n\n- first\n- second\n\n> quote\n\n| column |\n| --- |\n| value |\n[/" . $tag . "]\n\nOutside";
        $html = $this->render($source);
        $DOMXPath = $this->document($html);
        foreach (['//h2/' => 1, '//li/' => 2, '//blockquote/' => 1, '//th/' => 1, '//td/' => 1] as $parent => $count) {
            self::assertCount($count, $DOMXPath->query($parent . $query), $parent . $query);
        }
        self::assertSame('Outside', trim($DOMXPath->evaluate('string(//body/text()[last()])')));
        self::assertCount(0, $DOMXPath->query('//span/h2 | //span/ul | //strong/blockquote | //em/table'));
        self::assertStringNotContainsString('[/' . $tag . ']', $html);
    }

    /**
     * @return void
     */
    public function testMarkdownBlockRecognitionStillRespectsBlankLines(): void
    {
        $html = $this->render("[color=red]Ordinary text\n\nHeading\n=======\n\nBefore table\n\n| column | value |\n| --- | --- |\n| a | b |\n\nAfter[/color]");
        $DOMXPath = $this->document($html);
        self::assertSame('Heading', $DOMXPath->evaluate('string(//h1)'));
        self::assertCount(2, $DOMXPath->query('//th/span[@style="color: red"]'));
        self::assertSame('column', $DOMXPath->evaluate('string(//th[1])'));
        self::assertStringContainsString('Ordinary text', $DOMXPath->evaluate('string(//body/span[1])'));
        self::assertCount(0, $DOMXPath->query('//p | //span/h1 | //span/table'));
    }

    /**
     * @return void
     */
    public function testUnknownTagsCanStillBeMarkdownReferences(): void
    {
        $DOMXPath = $this->document($this->render("[OK]\n\n[OK]: https://example.test"));
        self::assertCount(1, $DOMXPath->query('//a[@href="https://example.test"]'));
    }

    /**
     * @return void
     */
    public function testAttributesAreReservedBeforeEmphasisAndNeverRestoredAsSource(): void
    {
        $html = $this->render('**[url=https://example.test/a*b_c?q={param}&v=__OITC_NODE_0__]Label[/url]**');
        $DOMXPath = $this->document($html);
        self::assertCount(1, $DOMXPath->query('//strong/a'));
        self::assertSame('https://example.test/a*b_c?q={param}&v=__OITC_NODE_0__', $DOMXPath->query('//a')[0]->getAttribute('href'));
        self::assertStringNotContainsString("\x1A", $html);
    }

    /**
     * @return void
     */
    public function testSyntheticJournalRemainsReadable(): void
    {
        $source = <<<'OUTPUT'
Timer findings:
- example-task.service: exit-code; exit 1
- Inspect the service journal around the timer trigger.

Service journal:
- example-host.invalid systemd[1]: Starting example-task.service
- example-host.invalid python3[1234]: Traceback (most recent call last):
- File "/opt/example/task.py", line 10, in <module>
- TypeError: 'NoneType' object is not iterable
- example-task.service: Failed with result 'exit-code'.

Interpretation:
- Inspect the named service journal.
OUTPUT;
        $DOMXPath = $this->document($this->render($source));
        $text = $DOMXPath->evaluate('string(//body)');
        self::assertStringContainsString("TypeError: 'NoneType' object is not iterable", $text);
        self::assertStringContainsString('<module>', $text);
        self::assertStringContainsString('example-task.service', $text);
        self::assertCount(0, $DOMXPath->query('//module | //script'));
        self::assertCount(8, $DOMXPath->query('//li'));
        // Also cover a pasted journal with spaces instead of physical line breaks.
        $SingleLineDOMXPath = $this->document($this->render(str_replace("\n", ' ', $source)));
        self::assertStringContainsString("TypeError: 'NoneType' object is not iterable", $SingleLineDOMXPath->evaluate('string(//body)'));
        self::assertStringContainsString('<module>', $SingleLineDOMXPath->evaluate('string(//body)'));
    }

    /**
     * @return array
     */
    public static function mixedSyntax(): array
    {
        return [
            ['[color=red]**Important**[/color]', '//body/span[@style="color: red"]/strong', 'Important'],
            ['**[color=red]Important[/color]**', '//body/strong/span[@style="color: red"]', 'Important'],
            ['## [color=red]Warning[/color]', '//h2/span', 'Warning'],
            ['- [b]**Important**[/b]', '//ul/li/strong/strong', 'Important'],
            ['[url=https://example.test]**Label**[/url]', '//a[@href="https://example.test"]/strong', 'Label'],
            ['[**[color=red]Label[/color]**](https://example.test)', '//a/strong/span', 'Label'],
            ['[b](https://example.test)', '//a[@href="https://example.test"]', 'b'],
            ["[b][reference]\n\n[reference]: https://example.test", '//a', 'b'],
            ["[code][reference]\n\n[reference]: https://example.test", '//a', 'code'],
            ["[code][reference]\nextra\n[/code]\n\n[reference]: https://example.test", '//a', 'code'],
            ["[b]bold[/b]\n\n[b]: https://example.test", '//strong', 'bold'],
            ['[url]https://example.test/a_b[/url]', '//a[@href="https://example.test/a_b"]', 'https://example.test/a_b'],
            ['[URL=https://example.test]Label[/URL]', '//a', 'Label'],
            ['[color="#aabbcc"]Color[/color]', '//span[@style="color: #aabbcc"]', 'Color'],
            ['[color="rgb(1, 2, 3)"]Color[/color]', '//span[@style="color: rgb(1, 2, 3)"]', 'Color'],
            ['[b]bold[/b][i]italic[/i][u]underlined[/u]', '//em', 'italic'],
            ['[b]outer[i]inner[/i]tail[/b]', '//strong/em', 'inner'],
            ['[color=red]a[/color][color=blue]b[/color]', '//span[@style="color: blue"]', 'b'],
            ['**[b]one[/b][i]two[/i]**', '//strong/em', 'two'],
        ];
    }

    /**
     * @return array
     */
    public static function legacyRendering(): array
    {
        return [
            [null, true, ''],
            ['<tag> & **Markdown**', true, '&lt;tag&gt; &amp; **Markdown**'],
            ['[b]bold[/b]', true, '<strong>bold</strong>'],
            ['[center]center[/center]', true, '<p align="center">center</p>'],
            ['[code][b]nested[/b][/code]', false, '<pre style="line-height: 12px;"><strong>nested</strong></pre>'],
            ["a\\nb\nc", false, "a\\nb\nc"],
            ["a\\nb\nc", true, "a<br>b<br />\nc"],
        ];
    }

    /**
     * @return array
     */
    public static function registeredFamilies(): array
    {
        return [
            ['b', '', 'strong'],
            ['i', '', 'em'],
            ['u', '', 'u'],
            ['color', '=red', 'span[@style="color: red"]'],
            ['color', '=#aabbcc', 'span[@style="color: #aabbcc"]'],
            ['color', '=rgb(1,2,3)', 'span[@style="color: rgb(1,2,3)"]'],
            ['xx-small', '', 'span[@style="font-size: xx-small;"]'],
            ['x-small', '', 'span[@style="font-size: x-small;"]'],
            ['small', '', 'span[@style="font-size: small;"]'],
            ['large', '', 'span[@style="font-size: large;"]'],
            ['x-large', '', 'span[@style="font-size: x-large;"]'],
            ['xx-large', '', 'span[@style="font-size: xx-large;"]'],
            ['left', '', 'div[@style="text-align: left"]'],
            ['right', '', 'div[@style="text-align: right"]'],
            ['center', '', 'div[@style="text-align: center"]'],
            ['justify', '', 'div[@style="text-align: justify"]'],
            ['code', '', 'pre/code'],
        ];
    }

    /**
     * @return array
     */
    public static function literalCode(): array
    {
        return [
            ['`[b]**literal**[/b]`', '[b]**literal**[/b]'],
            ["```text\n[b]**literal**[/b]\n```", '[b]**literal**[/b]'],
            ["    [b]**literal**[/b]", '[b]**literal**[/b]'],
            ['[code][b]**literal**[/b][/code]', '[b]**literal**[/b]'],
            ['[b][code]**literal**[/code][/b]', '**literal**'],
            // Tabs follow Parsedown's four-column block indentation, including in BBCode.
            ["[code]\n\t[b]**literal**[/b]\n\n  last\n[/code]", "\n    [b]**literal**[/b]\n\n  last\n"],
            ["[code]\n\titem\n    item\n[/code]", "\n    item\n    item\n"],
            ["[code][b]\n\n**literal**[/b][/code]", "[b]\n\n**literal**[/b]"],
            ["Before [code]**literal**\n\n[b]still literal[/b][/code] after", "**literal**\n\n[b]still literal[/b]"],
            ['`[code]literal[/code]`', '[code]literal[/code]'],
            ["```\n[code]\n**literal**\n[/code]\n```", "[code]\n**literal**\n[/code]"],
            ["```[code]\n[b]literal[/b]\n```", '[b]literal[/b]'],
            'tilde fence' => ["~~~text\n[b]**literal**[/b]\n~~~", '[b]**literal**[/b]'],
            'long fence containing short fence' => ["````\n```\n[b]literal[/b]\n```\n````", "```\n[b]literal[/b]\n```"],
            'fence inside quotation' => ["> ```text\n> [b]**literal**[/b]\n> ```", '[b]**literal**[/b]'],
            'fence inside list item' => ["- item\n\n  ```text\n  [b]**literal**[/b]\n  ```", '[b]**literal**[/b]'],
            'indented multiline code' => ["    [b]literal[/b]\n\n    **second**", "[b]literal[/b]\n\n**second**"],
            'double backticks' => ['``a `backtick` and [b]literal[/b]``', 'a `backtick` and [b]literal[/b]'],
            'empty fenced block' => ["```\n```", ''],
            // Parsedown retains the initial newline when the fence is never closed.
            'unclosed fence' => ["```\n[b]**literal**[/b]", "\n[b]**literal**[/b]"],
            'BBCode code keeps HTML and entities literal' => ['[code]<script>&amp; "quoted"</script>[/code]', '<script>&amp; "quoted"</script>'],
            'BBCode code keeps links and tables literal' => [
                "[code][label](https://example.test)\n[url]https://example.test[/url]\n| a | b |[/code]",
                "[label](https://example.test)\n[url]https://example.test[/url]\n| a | b |",
            ],
        ];
    }

    /**
     * @return array
     */
    public static function nestedLinks(): array
    {
        return [
            ['[url=https://outer.test][inner](https://inner.test)[/url]', '[inner](https://inner.test)'],
            ['[url=https://outer.test][url]https://inner.test[/url][/url]', '[url]https://inner.test[/url]'],
            ['[[url=https://inner.test]inner[/url]](https://outer.test)', '[url=https://inner.test]inner[/url]'],
            ['[url=https://outer.test]<https://inner.test>[/url]', '<https://inner.test>'],
            'plain autolink in BBCode link' => ['[url=https://outer.test]https://inner.test[/url]', 'https://inner.test'],
            'email in BBCode link' => ['[url=https://outer.test]<check@example.test>[/url]', '<check@example.test>'],
            'reference in BBCode link' => ["[url=https://outer.test][label][ref][/url]\n\n[ref]: https://inner.test", '[label][ref]'],
            'atomic BBCode link in Markdown link' => ['[[url]https://inner.test[/url]](https://outer.test)', '[url]https://inner.test[/url]'],
        ];
    }

    /**
     * @return array
     */
    public static function existingLineBreaks(): array
    {
        return array_map(function ($source) {
            return [$source];
        }, [
            'plain',
            "first\nsecond",
            "first\n\nsecond",
            "first\n\n\nsecond",
            "\nfirst\nsecond\n",
            "\n\n",
            "[color=red]first\n\nsecond[/color]",
            "[b]\nfirst\n\nsecond\n[/b]",
            "[b]first[/b]\n\n[i]second[/i]",
        ]);
    }

    /**
     * @return array
     */
    public static function attacks(): array
    {
        $sources = [
            '<script>alert(1)</script><img src=x onerror="alert(1)">',
            '[x](javascript:alert(1))',
            '[url]javascript://example.test/x[/url]',
            '[url=javascript://example.test/x]click[/url]',
            '[img]javascript://example.test/x[/img]',
            '[img]data:text/html,<script>alert(1)</script>[/img]',
            '[img="x onerror=alert(1)"]https://example.test/image.png[/img]',
            '[color=red;background-image:url(https://evil.test)]text[/color]',
            '[color="expression(alert(1))"]text[/color]',
            '[color="var(--unsafe)"]text[/color]',
            '[color="red\" onclick=alert(1)"]text[/color]',
            '[url="https://example.test\" onmouseover=alert(1)"]text[/url]',
            '[x](data:text/html;base64,PHNjcmlwdD4=)',
            '[x](jav&#x61;script:alert(1))',
            '![image](javascript:alert(1))',
            '![image](data:image/svg+xml,<svg onload="alert(1)">)',
            '<svg onload="alert(1)"></svg>',
            '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
            '<math href="javascript:alert(1)">content</math>',
            '[COLOR="red;position:fixed"]text[/COLOR]',
            '[color="r\\65 d"]text[/color]',
            '[x](vbscript:alert(1))',
        ];
        $cases = [];
        foreach ($sources as $index => $source) {
            $cases['plain attack ' . $index] = [$source];
            $cases['BBCode around attack ' . $index] = ['[b]' . $source . '[/b]'];
            $cases['mixed heading attack ' . $index] = ['## [color=red]' . $source . '[/color]'];
        }
        return $cases;
    }

    /**
     * @return array
     */
    public static function normalization(): array
    {
        return [
            ["a\nb\r\nc\rd", BBCMarkDownParser::LITERAL_NEWLINES, 3],
            ['a\\nb\\nc', BBCMarkDownParser::ESCAPED_LF, 2],
            ["a\r\\nb", BBCMarkDownParser::ESCAPED_LF, 1],
            ['a\\nb', BBCMarkDownParser::LITERAL_NEWLINES, 0],
        ];
    }

    /**
     * @return array
     */
    public static function combinedNewlines(): array
    {
        $sources = [
            'headings and lists' => "## Heading\n\n- first\n- second",
            'formatting across blocks' => "[color=red]first\n\n## Heading\n\n- item\n[/color]",
            'literal code' => "[code]# heading\n[b]literal[/b]\n[/code]",
            'reference link' => "[**[i]label[/i]**][ref]\n\n[ref]: https://example.test",
            'alignment and blank lines' => "[center]\nfirst\n\n- item\n[/center]\n\nlast",
        ];
        $cases = [];
        foreach ($sources as $name => $source) {
            $cases[$name . ' LF'] = [$source, $source, BBCMarkDownParser::LITERAL_NEWLINES];
            $cases[$name . ' CRLF'] = [$source, str_replace("\n", "\r\n", $source), BBCMarkDownParser::LITERAL_NEWLINES];
            $cases[$name . ' CR'] = [$source, str_replace("\n", "\r", $source), BBCMarkDownParser::LITERAL_NEWLINES];
            $cases[$name . ' escaped LF'] = [$source, str_replace("\n", '\\n', $source), BBCMarkDownParser::ESCAPED_LF];
        }
        return $cases;
    }

    /**
     * @return array
     */
    public static function incompleteAtomicBlocks(): array
    {
        return [
            'code'  => ["[code]**literal**\n# literal\n- literal"],
            'url'   => ['[url]https://example.test'],
            'image' => ['[img]https://example.test/image.png'],
        ];
    }

    /**
     * @return array
     */
    public static function markdownCases(): array
    {
        $cases = [
            'setext heading level one' => ["Heading\n=======", ['//h1' => ['Heading']]],
            'setext heading level two' => ["Heading\n-------", ['//h2' => ['Heading']]],
            'optional closing hashes' => ['### Heading ###', ['//h3' => ['Heading']]],
            'consecutive heading levels' => [
                "# test\n## test",
                ['//body/h1' => ['test'], '//body/h2' => ['test']]
            ],
            'heading markers within a heading stay text' => [
                '# test ## test',
                ['//body/h1' => ['test ## test'], '//h2' => []]
            ],
            // Parsedown accepts headings without a space in its default non-strict mode.
            'heading without whitespace follows Parsedown' => [
                '#test',
                ['//body/h1' => ['test']]
            ],
            'too many heading markers stay text' => [
                '####### test',
                ['//body' => ['####### test'], '//h6' => []]
            ],
            'unordered plus list' => ["+ first\n+ second", ['//ul/li' => ['first', 'second']]],
            'unordered asterisk list' => ["* first\n* second", ['//ul/li' => ['first', 'second']]],
            'ordered start number' => ["3. third\n4. fourth", ['//ol/@start' => ['3'], '//ol/li' => ['third', 'fourth']]],
            'nested unordered list' => ["- parent\n  - child\n    - grandchild", ['//ul/li/ul/li/ul/li' => ['grandchild']]],
            'ordered list inside unordered list' => ["- parent\n  1. first\n  2. second", ['//ul/li/ol/li' => ['first', 'second']]],
            'unordered list inside ordered list' => ["1. parent\n   - first\n   - second", ['//ol/li/ul/li' => ['first', 'second']]],
            'loose list' => ["- first\n\n- second", ['//ul/li' => ['first', 'second']]],
            'empty list item' => ["- \n- second", ['//ul/li' => ['', 'second']]],
            'empty heading' => ['# ', ['//h1' => ['']]],
            'nested quotation' => ["> outer\n>\n>> inner", ['//blockquote/blockquote' => ['inner']]],
            'list in quotation' => ["> - first\n> - second", ['//blockquote/ul/li' => ['first', 'second']]],
            'quotation in list' => ["- item\n\n  > quote", ['//ul/li/blockquote' => ['quote']]],
            'underscore emphasis' => ['_italic_ and __bold__', ['//em' => ['italic'], '//strong' => ['bold']]],
            'nested emphasis' => ['**bold and *italic***', ['//strong/em' => ['italic']]],
            'strikethrough' => ['~~removed~~ stays', ['//del' => ['removed']]],
            'escaped syntax' => ['\# literal \*stars\* \[label\]', ['//body' => ['# literal *stars* [label]'], '//h1 | //em | //a' => []]],
            'escaped list marker' => ['\- literal', ['//body' => ['- literal'], '//ul' => []]],
            'hard line break' => ["first  \nsecond", ['//br' => [''], '//p' => []]],
            'link title and query string' => [
                '[label](https://example.test/a?x=1&y=2 "A & B")',
                ['//a' => ['label'], '//a/@href' => ['https://example.test/a?x=1&y=2'], '//a/@title' => ['A & B']],
            ],
            'relative link' => ['[relative](/docs/checks)', ['//a/@href' => ['/docs/checks']]],
            'fragment link' => ['[section](#details)', ['//a/@href' => ['#details']]],
            'reference link with title' => ["[label][ref]\n\n[ref]: https://example.test \"Title\"", ['//a' => ['label'], '//a/@title' => ['Title']]],
            'collapsed reference' => ["[label][]\n\n[label]: https://example.test", ['//a' => ['label']]],
            'case insensitive reference' => ["[LABEL]\n\n[label]: https://example.test", ['//a' => ['LABEL']]],
            'undefined reference stays text' => ['[label][missing]', ['//body' => ['[label][missing]'], '//a' => []]],
            'angle autolink' => ['<https://example.test/a_b>', ['//a/@href' => ['https://example.test/a_b']]],
            'plain autolink' => ['Visit https://example.test/a_b.', ['//a/@href' => ['https://example.test/a_b']]],
            'email autolink' => ['<check@example.test>', ['//a/@href' => ['mailto:check@example.test']]],
            'image title and alt' => ['![A & B](https://example.test/image.png "Caption")', ['//img/@alt' => ['A & B'], '//img/@title' => ['Caption']]],
            'linked image' => [
                '[![image](https://example.test/image.png)](https://example.test)',
                ['//a/img/@src' => ['https://example.test/image.png'], '//a/@href' => ['https://example.test']],
            ],
            'escaped table separator' => ["| column | value |\n| --- | --- |\n| a\\|b | c |", ['//td' => ['a|b', 'c']]],
            'empty table cell' => ["| first | second |\n| --- | --- |\n| | value |", ['//td' => ['', 'value']]],
            'table alignment' => [
                "| left | middle | right |\n| :--- | :---: | ---: |\n| a | b | c |",
                ['//th/@style' => ['text-align: left;', 'text-align: center;', 'text-align: right;'], '//td' => ['a', 'b', 'c']],
            ],
            // Parsedown safe mode displays source entities literally, like legacy BBCode.
            'entities and unicode' => [
                'A &amp; B &lt;module&gt; **café 日本語 🚦**',
                [
                    '//body'   => ['A &amp; B &lt;module&gt; café 日本語 🚦'],
                    '//strong' => ['café 日本語 🚦'],
                    '//module' => [],
                ],
            ],
            'raw html stays text' => ['<span class="test">literal</span>', ['//body' => ['<span class="test">literal</span>'], '//span' => []]],
            'inline code in heading' => ['## `x < y`', ['//h2/code' => ['x < y']]],
            'code fence language' => [
                "```json\n{\"key\": \"[b]literal[/b]\"}\n```",
                ['//pre/code/@class' => ['language-json'], '//code' => ['{"key": "[b]literal[/b]"}']],
            ],
        ];
        foreach (range(1, 6) as $level) {
            $cases['ATX heading level ' . $level] = [str_repeat('#', $level) . ' Heading', ['//h' . $level => ['Heading']]];
        }
        foreach (['---', '***', '___'] as $rule) {
            $cases['horizontal rule ' . $rule] = [$rule, ['//hr' => ['']]];
        }
        return $cases;
    }

    /**
     * @return array
     */
    public static function mixedBlockCases(): array
    {
        $cases = [
            'consecutive headings with different BBCode formatting' => [
                "# [b]test[/b]\n## [color=red]test[/color]",
                ['//body/h1/strong' => ['test'], '//body/h2/span[@style="color: red"]' => ['test'], '//h2/strong' => []]
            ],
            'BBCode spans consecutive heading levels' => [
                "[b]\n# first\n## second\n[/b]\n\nOutside",
                ['//body/h1/strong' => ['first'], '//body/h2/strong' => ['second'], '//body/text()[last()]' => ['Outside'], '//strong/h1 | //strong/h2' => []]
            ],
            'heading markers after an inline BBCode opening stay text' => [
                '[b]# test[/b]',
                ['//body/strong' => ['# test'], '//h1' => []]
            ],
            'escaped heading marker with BBCode formatting' => [
                '\\# [b]test[/b]',
                ['//body' => ['# test'], '//body/strong' => ['test'], '//h1' => []]
            ],
            'BBCode code keeps heading syntax literal' => [
                "[code]# test\n## [b]test[/b][/code]",
                ['//pre/code' => ["# test\n## [b]test[/b]"], '//h1 | //h2 | //strong' => []]
            ],
            'color across quotation and list' => [
                "[color=red]\n> **quote**\n>\n> - *item*\n[/color]",
                ['//blockquote/span/strong' => ['quote'], '//blockquote/ul/li/span/em' => ['item']],
            ],
            'alignment around color and blocks' => [
                "[center][color=red]\n## Heading\n\n- item\n[/color][/center]",
                ['//div[@style="text-align: center"]/h2/span' => ['Heading'], '//div/ul/li/span' => ['item']],
            ],
            'multiple closing tags separated by spaces' => [
                "[center][b]\n- item\n[/b] [/center]",
                ['//div/ul/li/strong' => ['item'], '//li/div' => []],
            ],
            'inline tags on a list continuation stay in the list' => [
                "- item\n[b]continued[/b]",
                ['//li/strong' => ['continued']],
            ],
            'deep mixed inline nesting' => [
                '**[color=red]*[u]nested[/u]*[/color]**',
                ['//strong/span/em/u' => ['nested']],
            ],
            'BBCode in nested lists' => [
                "- [b]parent[/b]\n  1. *[color=red]child[/color]*",
                ['//ul/li/strong' => ['parent'], '//ul/li/ol/li/em/span' => ['child']],
            ],
            'BBCode in Markdown table cells' => [
                "| [b]name[/b] | state |\n| --- | --- |\n| *[u]task[/u]* | [color=red]**failed**[/color] |",
                ['//th/strong' => ['name'], '//td/em/u' => ['task'], '//td/span/strong' => ['failed']],
            ],
            'empty cells within BBCode formatting' => [
                "[color=red]\n\n| first | second |\n| --- | --- |\n| | value |\n[/color]",
                ['//td' => ['', 'value'], '//td/span' => ['value'], '//span/table | //span/tr | //span/td' => []],
            ],
            'empty list item within BBCode formatting' => [
                "[b]\n\n- \n- item\n[/b]",
                ['//li' => ['', 'item'], '//li/strong' => ['item'], '//strong/li' => []],
            ],
            'mixed reference link label' => [
                "[**[i]label[/i]**][target]\n\n[target]: https://example.test",
                ['//a/strong/em' => ['label'], '//a/@href' => ['https://example.test']],
            ],
            'registered tag as collapsed reference' => [
                "[b][]\n\n[b]: https://example.test",
                ['//a' => ['b'], '//strong' => []],
            ],
            'image inside BBCode link' => [
                '[url=https://example.test]![image](https://example.test/image.png)[/url]',
                ['//a/img/@alt' => ['image'], '//a/@href' => ['https://example.test']],
            ],
            'BBCode image inside Markdown link' => [
                '[[img=caption]https://example.test/image.png[/img]](https://example.test)',
                ['//a/img/@alt' => ['caption'], '//a/@href' => ['https://example.test']],
            ],
            'BBCode image inside BBCode link' => [
                '[url=https://example.test][img=caption]https://example.test/image.png[/img][/url]',
                ['//a/img/@alt' => ['caption']],
            ],
            'BBCode formatting across link and text' => [
                '[color=red]before [*label*](https://example.test) after[/color]',
                ['//span/a/em' => ['label'], '//span' => ['before label after']],
            ],
            'link across heading and list' => [
                "[url=https://example.test]\n## Heading\n\n- item\n[/url]",
                ['//h2/a' => ['Heading'], '//li/a' => ['item'], '//a/h2 | //a/ul | //a/a' => []],
            ],
            'formatting resumes after fenced code' => [
                "[color=red]before\n\n```text\n[b]literal[/b]\n```\n\nafter[/color]",
                ['//body/span' => ['before', 'after'], '//pre/code' => ['[b]literal[/b]'], '//pre//span | //code/strong' => []],
            ],
            'autolink followed by formatting' => [
                'https://example.test [b]bold[/b]',
                ['//a/@href' => ['https://example.test'], '//strong' => ['bold']],
            ],
            'Markdown attribute retains BBCode text' => [
                '[label](https://example.test "[b]title[/b]")',
                ['//a/@title' => ['[b]title[/b]'], '//strong' => []],
            ],
            'BBCode size preserves inline Markdown' => [
                '[large]**big** and *italic*[/large]',
                ['//span[@style="font-size: large;"]/strong' => ['big'], '//span/em' => ['italic']],
            ],
        ];
        foreach (range(1, 6) as $level) {
            $heading = str_repeat('#', $level);
            $path = '//body/h' . $level;
            $cases['BBCode inside heading level ' . $level] = [
                $heading . ' [b]**test**[/b] [color=red]*color*[/color]',
                [$path => ['test color'], $path . '/strong/strong' => ['test'], $path . '/span[@style="color: red"]/em' => ['color']]
            ];
            $cases['BBCode around heading level ' . $level] = [
                "[color=red][b]\n" . $heading . " *test*\n[/b][/color]\n\nOutside",
                [$path . '/span[@style="color: red"]/strong/em' => ['test'], '//body/text()[last()]' => ['Outside'], '//span/h' . $level . ' | //strong/h' . $level => []]
            ];
            $cases['BBCode alignment around heading level ' . $level] = [
                "[center]\n" . $heading . " [i]test[/i]\n[/center]",
                ['//body/div[@style="text-align: center"]/h' . $level . '/em' => ['test'], '//p' => []]
            ];
            $cases['BBCode link inside heading level ' . $level] = [
                $heading . ' [url=https://example.test]**test**[/url]',
                [$path . '/a[@href="https://example.test"]/strong' => ['test'], '//a/a' => []]
            ];
            $cases['inline code inside heading level ' . $level] = [
                $heading . ' `[b]test[/b]`',
                [$path . '/code' => ['[b]test[/b]'], '//strong' => []]
            ];
        }
        return $cases;
    }

    /**
     * @return array
     */
    public static function inlineFamilies(): array
    {
        $cases = [];
        foreach (self::registeredFamilies() as [$tag, $option, $query]) {
            if (!in_array($tag, ['left', 'right', 'center', 'justify', 'code'], true)) {
                $cases[$tag . $option] = [$tag, $option, $query];
            }
        }
        return $cases;
    }

    /**
     * @return array
     */
    public static function inlineNesting(): array
    {
        $cases = [];
        foreach (self::inlineFamilies() as [$tag, $option, $bbQuery]) {
            foreach (['**' => 'strong', '*' => 'em', '~~' => 'del'] as $marker => $mdQuery) {
                $open = '[' . $tag . $option . ']';
                $close = '[/' . $tag . ']';
                $cases[$tag . $option . ' around ' . $mdQuery] = [$open . $marker . 'text' . $marker . $close, '//body/' . $bbQuery . '/' . $mdQuery];
                $cases[$mdQuery . ' around ' . $tag . $option] = [$marker . $open . 'text' . $close . $marker, '//body/' . $mdQuery . '/' . $bbQuery];
            }
        }
        return $cases;
    }

    /**
     * Render test input and fail on unexpected parser exceptions.
     *
     * @param string|null $source
     * @param string $mode
     * @return string
     */
    private function render(?string $source, string $mode = BBCMarkDownParser::LITERAL_NEWLINES): string
    {
        $BBCMarkDownParser = $this->getMockBuilder(BBCMarkDownParser::class)
            ->onlyMethods(['reportFailure'])
            ->getMock();
        // Syntax tests must fail if rendering falls back after an exception.
        $BBCMarkDownParser->expects(self::never())
            ->method('reportFailure');

        return $BBCMarkDownParser->render($source, $mode);
    }

    /**
     * Read the generated HTML for DOM and attribute assertions.
     *
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
