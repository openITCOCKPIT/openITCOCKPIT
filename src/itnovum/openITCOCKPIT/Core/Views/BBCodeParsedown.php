<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

namespace itnovum\openITCOCKPIT\Core\Views;


/**
 * Extends Parsedown with support for mixed Markdown and BBCode.
 */
class BBCodeParsedown extends \Parsedown {

    /**
     * Markdown elements that need block-level containers.
     *
     * @var string[]
     */
    private const BLOCKS = [
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'ul',
        'ol',
        'li',
        'blockquote',
        'table',
        'thead',
        'tbody',
        'tr',
        'td',
        'th',
        'div'
    ];

    /**
     * @var BBCMarkDownParser
     */
    private BBCMarkDownParser $BBCMarkDownParser;

    /**
     * @var bool
     */
    private bool $reserving = false;

    /**
     * @var bool
     */
    private bool $probingReferences = false;

    /**
     * @var string
     */
    private string $prefix;

    /**
     * @var array
     */
    private array $reserved = [];

    /**
     * @var int
     */
    private int $sequence = 0;

    /**
     * Active Markdown and BBCode formatting contexts.
     *
     * @var array
     */
    private array $contexts = [];

    /**
     * Open elements in the combined output tree.
     *
     * @var array
     */
    private array $frames = [];

    /**
     * @var array
     */
    private array $result = [];

    /**
     * BBCodeParsedown constructor.
     * @param BBCMarkDownParser $BBCMarkDownParser
     */
    public function __construct(BBCMarkDownParser $BBCMarkDownParser) {
        $this->BBCMarkDownParser = $BBCMarkDownParser;
        $this->setSafeMode(true);
        $this->setBreaksEnabled(true);
        $this->InlineTypes["\x1A"] = ['ReservedNode'];
        $this->inlineMarkerList .= "\x1A";
        // Indented/fenced Markdown code gets first refusal and never calls inline handlers.
        $this->unmarkedBlockTypes[] = 'BbCodeLiteral';
    }

    /**
     * Convert the source to Markdown nodes and apply the BBCode formatting.
     *
     * @param string $source
     * @return string
     */
    public function render(string $source): string {
        $this->reserved = [];
        $this->sequence = 0;
        do {
            $this->prefix = "\x1A" . bin2hex(random_bytes(16)) . ':';
        } while (str_contains($source, $this->prefix));
        // Parsedown trims surrounding blank lines. Keep their visible line breaks,
        // just like the existing BBCode presentation.
        preg_match('/\A((?:[ \t]*\n)*)(.*?)(\n[ \t\n]*)?\z/s', $source, $parts);
        $nodes = $this->handleElementsRecursive([
            parent::inlineText($parts[1])['element'],
            ['elements' => $this->textElements($parts[2])],
            parent::inlineText($parts[3] ?? '')['element']
        ]);
        $events = [];
        $this->flatten($nodes, $events);
        $nodes = $this->assemble($this->BBCMarkDownParser->parseMixedNodes($events));
        return trim($this->elements($nodes), "\n");
    }

    /**
     * Keep line breaks instead of adding paragraph wrappers.
     *
     * @param array $lines
     * @return array
     */
    protected function linesElements(array $lines) {
        $nodes = array_values(array_filter(parent::linesElements($lines)));
        foreach ($nodes as $index => &$node) {
            if (isset($node['paragraphBreaks'])) {
                // Parsedown still recognizes paragraph boundaries for Markdown syntax.
                // Display ordinary text with line breaks instead of <p> wrappers.
                $breaks = $node['paragraphBreaks'] + (int)isset($nodes[$index + 1]['paragraphBreaks']);
                $node['handler']['argument'] .= str_repeat("\n", $breaks);
                unset($node['paragraphBreaks']);
            }
        }
        return $nodes;
    }

    /**
     * Preserve source lengths and paragraph separators for later processing.
     *
     * @param array $Component
     * @return array
     */
    protected function extractElement(array $Component) {
        $node = parent::extractElement($Component);
        if (($Component['type'] ?? '') === 'Paragraph') {
            unset($node['name']);
            $node['paragraphBreaks'] = $Component['interrupted'] ?? 0;
        }
        if ($this->reserving && isset($Component['extent'])) {
            $node['sourceExtent'] = $Component['extent'];
        }
        return $node;
    }

    /**
     * End a list before a line containing only BBCode boundaries.
     *
     * @param array $Line
     * @param array $Block
     * @return array|null
     */
    protected function blockListContinue($Line, array $Block) {
        if ($Line['indent'] <= $Block['indent']) {
            $source = trim($Line['text']);
            while ($source !== '') {
                $token = $this->BBCMarkDownParser->readToken($source);
                if ($token === null || $this->BBCMarkDownParser->isAtomic($token)) {
                    break;
                }
                $source = ltrim(substr($source, strlen($token['source'])));
            }
            if ($source === '') {
                return null;
            }
        }
        return parent::blockListContinue($Line, $Block);
    }

    /**
     * Start collecting an incomplete literal BBCode body.
     *
     * @param array $Line
     * @param array|null $Block
     * @return array|null
     */
    protected function blockBbCodeLiteral($Line, $Block = null) {
        if ($this->probingReferences || (in_array($Line['text'][0], ['`', '~'], true) && $this->blockFencedCode($Line) !== null)) {
            return;
        }
        $source = $Line['body'];
        $offset = 0;
        foreach ($this->syntaxElements($source) as $node) {
            if (!empty($node['incompleteAtomic'])) {
                $token = $this->BBCMarkDownParser->readToken(substr($source, $offset));
                return [
                    'source'  => $source,
                    'offset'  => $offset,
                    'tag'     => $token['tag'],
                    'element' => []
                ];
            }
            $offset += isset($node['sourceText']) ? strlen($node['sourceText']) : $node['sourceExtent'];
        }
    }

    /**
     * Collect lines until the literal BBCode body is closed.
     *
     * @param array $Line
     * @param array $Block
     * @return array|null
     */
    protected function blockBbCodeLiteralContinue($Line, $Block) {
        if (isset($Block['complete'])) {
            return;
        }
        $Block['source'] .= str_repeat("\n", ($Block['interrupted'] ?? 0) + 1)
            . $Line['body'];
        unset($Block['interrupted']);
        if (stripos(substr($Block['source'], $Block['offset']), '[/' . $Block['tag'] . ']') !== false) {
            $Block['complete'] = true;
        }
        return $Block;
    }

    /**
     * Resolve reference links before treating an incomplete body as literal text.
     *
     * @param array $Block
     * @return array
     */
    protected function blockBbCodeLiteralComplete($Block) {
        $source = substr($Block['source'], $Block['offset']);
        $atomic = $this->BBCMarkDownParser->readAtomic($source, $this->BBCMarkDownParser->readToken($source));
        if (!$atomic['complete']) {
            // An apparent unclosed [code]/[url]/[img] may be a forward reference
            // such as [code][reference]. Let Parsedown resolve that ambiguity.
            $definitions = $this->DefinitionData;
            $this->probingReferences = true;
            try {
                $nodes = $this->linesElements(explode("\n", $Block['source']));
            } finally {
                $this->probingReferences = false;
            }
            if ($this->inlineExplicitLink(['text' => $source]) !== null) {
                $Block['element'] = ['elements' => $nodes];
                return $Block;
            }
            $this->DefinitionData = $definitions;
            $Block['element'] = [
                'name' => 'pre',
                'text' => $Block['source']
            ];
        } else {
            // Resolve after block parsing, when forward reference definitions are available.
            $Block['element'] = [
                'handler' => [
                    'function'    => 'bbCodeLiteralElements',
                    'argument'    => [
                        'source' => $Block['source'],
                        'offset' => $Block['offset']
                    ],
                    'destination' => 'elements'
                ]
            ];
        }
        return $Block;
    }

    /**
     * Parse Markdown around a reserved literal BBCode body.
     *
     * @param array $block
     * @return array
     */
    protected function bbCodeLiteralElements(array $block): array {
        $source = substr($block['source'], $block['offset']);
        if ($this->inlineExplicitLink(['text' => $source]) !== null) {
            return $this->linesElements(explode("\n", $block['source']));
        }
        $atomic = $this->BBCMarkDownParser->readAtomic($source, $this->BBCMarkDownParser->readToken($source));
        $reserved = $this->reserve($atomic['element']);
        $source = substr($block['source'], 0, $block['offset']) . $reserved . substr($source, $atomic['extent']);
        return $this->linesElements(explode("\n", $source));
    }

    /**
     * Protect BBCode options before processing Markdown emphasis.
     *
     * @param string $text
     * @param array $nonNestables
     * @return array
     */
    protected function lineElements($text, $nonNestables = []) {
        $source = '';
        foreach ($this->syntaxElements($text) as $node) {
            if (isset($node['sourceText'])) {
                $source .= $node['sourceText'];
            } else {
                unset($node['sourceExtent'], $node['incompleteAtomic']);
                $source .= $this->reserve($node);
            }
        }
        return parent::lineElements($source, $nonNestables);
    }

    /**
     * Use Parsedown to recognize links, images, code and escaped characters.
     *
     * @param string $source
     * @return array
     */
    private function syntaxElements(string $source): array {
        $types = $this->InlineTypes;
        $markers = $this->inlineMarkerList;
        $this->reserving = true;
        $this->InlineTypes = [
            '['    => ['ExplicitLink', 'BbCode', 'Link'],
            '!'    => ['Image'],
            '`'    => ['Code'],
            '\\'   => ['EscapeSequence'],
            "\x1A" => ['ReservedNode']
        ];
        $this->inlineMarkerList = "[!`\\\x1A";
        try {
            return parent::lineElements($source);
        } finally {
            $this->InlineTypes = $types;
            $this->inlineMarkerList = $markers;
            $this->reserving = false;
        }
    }

    /**
     * Reserve an inline node for the current render call.
     *
     * @param array $node
     * @return string
     */
    private function reserve(array $node): string {
        $id = $this->prefix . count($this->reserved) . "\x1A";
        $this->reserved[$id] = $node;
        return $id;
    }

    /**
     * Keep the original text while reserving inline syntax.
     *
     * @param string $text
     * @return array
     */
    protected function inlineText($text) {
        return $this->reserving
            ? [
                'extent'  => strlen($text),
                'element' => ['sourceText' => $text]
            ]
            : parent::inlineText($text);
    }

    /**
     * Give complete Markdown links priority over registered BBCode tags.
     *
     * @param array $Excerpt
     * @return array|null
     */
    protected function inlineExplicitLink($Excerpt) {
        $source = $Excerpt['text'];
        $link = parent::inlineLink([
            'text'    => $source,
            'context' => $source
        ]);
        if ($link !== null && preg_match('/^\[((?:[^][]++|(?R))*+)\]/', $source, $label)
            && $link['extent'] > strlen($label[0])) {
            $link['element']['source'] = substr($source, 0, $link['extent']);
            return $link;
        }
        return null;
    }

    /**
     * Read a BBCode token or a literal code, URL or image body.
     *
     * @param array $Excerpt
     * @return array|null
     */
    protected function inlineBbCode($Excerpt) {
        $token = $this->BBCMarkDownParser->readToken($Excerpt['text']);
        if ($token === null) {
            return;
        }
        if ($this->BBCMarkDownParser->isAtomic($token)) {
            $atomic = $this->BBCMarkDownParser->readAtomic($Excerpt['text'], $token);
            return [
                'extent'  => $atomic['extent'],
                'element' => $atomic['element'] + ['incompleteAtomic' => !$atomic['complete']]
            ];
        }
        return [
            'extent'  => strlen($token['source']),
            'element' => ['bbToken' => $token]
        ];
    }

    /**
     * Restore a reserved inline node before generating HTML.
     *
     * @param array $Excerpt
     * @return array|null
     * @throws \UnexpectedValueException
     */
    protected function inlineReservedNode($Excerpt) {
        if (preg_match('/^' . preg_quote($this->prefix, '/') . '[0-9]+\x1A/', $Excerpt['text'], $match)) {
            if (!isset($this->reserved[$match[0]])) {
                throw new \UnexpectedValueException('Unknown reserved node');
            }
            return [
                'extent'  => strlen($match[0]),
                'element' => $this->reserved[$match[0]]
            ];
        }
    }

    /**
     * Keep the original email link text for nested-link fallback.
     *
     * @param array $Excerpt
     * @return array|null
     */
    protected function inlineEmailTag($Excerpt) {
        $link = parent::inlineEmailTag($Excerpt);
        if ($link !== null) {
            $link['element']['source'] = substr($Excerpt['text'], 0, $link['extent']);
        }
        return $link;
    }

    /**
     * Keep the original link text for nested-link fallback.
     *
     * @param array $Excerpt
     * @return array|null
     */
    protected function inlineLink($Excerpt) {
        $link = parent::inlineLink($Excerpt);
        if ($link !== null) {
            $link['element']['source'] = substr($Excerpt['text'], 0, $link['extent']);
        }
        return $link;
    }

    /**
     * Prevent automatic links from consuming reserved node IDs.
     *
     * @param array $Excerpt
     * @return array|null
     */
    protected function inlineUrl($Excerpt) {
        // An autolink must never consume a reserved node into its href.
        $Excerpt['context'] = explode("\x1A", $Excerpt['context'], 2)[0];
        $link = parent::inlineUrl($Excerpt);
        if ($link !== null) {
            $link['element']['source'] = $link['element']['text'];
        }
        return $link;
    }

    /**
     * Keep the original autolink text for nested-link fallback.
     *
     * @param array $Excerpt
     * @return array|null
     */
    protected function inlineUrlTag($Excerpt) {
        $link = parent::inlineUrlTag($Excerpt);
        if ($link !== null) {
            $link['element']['source'] = substr($Excerpt['text'], 0, $link['extent']);
        }
        return $link;
    }

    /**
     * Preserve Markdown boundaries while passing nodes to JBBCode.
     *
     * @param array $nodes
     * @param array $events
     * @return void
     */
    private function flatten(array $nodes, array &$events): void {
        foreach ($nodes as $node) {
            if (isset($node['bbToken'])) {
                $events[] = [
                    'type'  => 'bb',
                    'token' => $node['bbToken']
                ];
                continue;
            }
            $name = $node['name'] ?? null;
            if (in_array($name, ['code', 'pre', 'img', 'br', 'hr'], true)) {
                $events[] = [
                    'type' => 'leaf',
                    'node' => $node
                ];
                continue;
            }
            $children = $node['elements'] ?? (isset($node['element']) ? [$node['element']] : []);
            if (isset($node['text']) || isset($node['rawHtml'])) {
                // Parsedown escape/entity nodes are data too; no raw HTML enters the serializer.
                $children[] = [
                    'text'      => $node['text'] ?? $node['rawHtml'],
                    'autobreak' => false
                ];
            }
            if ($name !== null) {
                $id = 'md' . $this->sequence++;
                unset($node['elements'], $node['element'], $node['text'], $node['rawHtml'], $node['nonNestables']);
                $events[] = [
                    'type' => 'open',
                    'id'   => $id,
                    'node' => $node
                ];
            }
            $firstChild = count($events);
            if ($name === null && isset($node['text']) && count($children) === 1) {
                if ($children[0]['text'] !== '') {
                    $events[] = [
                        'type' => 'leaf',
                        'node' => $children[0]
                    ];
                }
            } else {
                $this->flatten($children, $events);
            }
            if (in_array($name, self::BLOCKS, true) && count($events) === $firstChild) {
                // Empty table cells, list items and headings still carry structure.
                $events[$firstChild - 1] = [
                    'type' => 'leaf',
                    'node' => $node + ['elements' => []]
                ];
                continue;
            }
            if ($name !== null) {
                $events[] = [
                    'type' => 'close',
                    'id'   => $id
                ];
            }
        }
    }

    /**
     * Combine Markdown containers and active BBCode formatting.
     *
     * @param array $events
     * @return array
     */
    private function assemble(array $events): array {
        $this->contexts = $this->frames = $this->result = [];
        $invalidBlocks = $this->invalidBlockScopes($events);
        $literalScopes = [];
        $skip = null;
        foreach ($events as $event) {
            $type = $event['type'];
            if ($skip !== null) {
                if ($type === 'close' && $event['id'] === $skip) {
                    $skip = null;
                }
                continue;
            }
            if ($type === 'open' || $type === 'bbOpen') {
                $node = $event['node'];
                $link = ($node['name'] ?? '') === 'a';
                if (($link && $this->hasLink()) || isset($invalidBlocks[$event['id']])) {
                    $this->append(['text' => $event['source'] ?? $node['source'] ?? '']);
                    if ($type === 'open') {
                        $skip = $event['id'];
                    } else {
                        $literalScopes[$event['id']] = $event['close'];
                    }
                    continue;
                }
                unset($node['source']);
                $node['elements'] = [];
                $this->contexts[] = [
                    'id'   => $event['id'],
                    'node' => $node
                ];
            } else if ($type === 'close' || $type === 'bbClose') {
                if (isset($literalScopes[$event['id']])) {
                    $this->append(['text' => $literalScopes[$event['id']]]);
                    unset($literalScopes[$event['id']]);
                } else {
                    $this->contexts = array_values(array_filter($this->contexts, function ($context) use ($event) {
                        return $context['id'] !== $event['id'];
                    }));
                }
            } else if ($type === 'leaf') {
                $this->append($event['node']);
            }
        }
        $this->reconcile([]);
        return $this->result;
    }

    /**
     * Find alignment tags that cross incompatible Markdown containers.
     *
     * @param array $events
     * @return array
     */
    private function invalidBlockScopes(array $events): array {
        $markdown = [];
        $parents = [];
        $invalid = [];
        foreach ($events as $event) {
            if ($event['type'] === 'open') {
                $markdown[$event['id']] = $event['node']['name'];
            } else if ($event['type'] === 'close') {
                unset($markdown[$event['id']]);
            } else if ($event['type'] === 'bbOpen' && $event['node']['name'] === 'div') {
                $parents[$event['id']] = $markdown;
                foreach ($markdown as $name) {
                    if (!in_array($name, ['li', 'ul', 'ol', 'blockquote', 'td', 'th'], true)) {
                        $invalid[$event['id']] = true;
                    }
                }
            } else if ($event['type'] === 'bbClose' && isset($parents[$event['id']])) {
                if ($parents[$event['id']] !== $markdown) {
                    $invalid[$event['id']] = true;
                }
            }
        }
        return $invalid;
    }

    /**
     * Check if the current formatting context already contains a link.
     *
     * @return bool
     */
    private function hasLink(): bool {
        foreach ($this->contexts as $context) {
            if ($context['node']['name'] === 'a') {
                return true;
            }
        }
        return false;
    }

    /**
     * Add a leaf node inside the active block and inline containers.
     *
     * @param array $node
     * @return void
     */
    private function append(array $node): void {
        if (!isset($node['name']) && ($node['text'] ?? '') === '') {
            return;
        }
        $blocks = [];
        $inline = [];
        $pre = ($node['name'] ?? '') === 'pre';
        $blockLeaf = in_array($node['name'] ?? '', self::BLOCKS, true);
        if ($pre && isset($node['source'])) {
            foreach ($this->contexts as $context) {
                if (str_starts_with($context['id'], 'md')
                    && !in_array($context['node']['name'], ['ul', 'ol', 'li', 'blockquote', 'div', 'td', 'th'], true)) {
                    $node = ['text' => $node['source']];
                    $pre = false;
                    break;
                }
            }
        }
        foreach ($this->contexts as $context) {
            if (in_array($context['node']['name'], self::BLOCKS, true)) {
                $blocks[] = $context;
            } else if (!$pre && !$blockLeaf) {
                $inline[] = $context;
            }
        }
        // Reopen inline formatting inside headings/list items when it spans Markdown blocks.
        $this->reconcile(array_merge($blocks, $inline));
        unset($node['source'], $node['nonNestables']);
        $node['autobreak'] = $pre;
        $this->push($node);
    }

    /**
     * Close and reopen containers when the active formatting changes.
     *
     * @param array $desired
     * @return void
     */
    private function reconcile(array $desired): void {
        $common = 0;
        while (isset($this->frames[$common], $desired[$common]) && $this->frames[$common]['id'] === $desired[$common]['id']) {
            $common++;
        }
        while (count($this->frames) > $common) {
            $frame = array_pop($this->frames);
            if ($frame['node']['elements']) {
                $this->push($frame['node']);
            }
        }
        foreach (array_slice($desired, $common) as $frame) {
            $this->frames[] = $frame;
        }
    }

    /**
     * Append an element to its parent or to the output tree.
     *
     * @param array $node
     * @return void
     */
    private function push(array $node): void {
        if ($this->frames) {
            $this->frames[count($this->frames) - 1]['node']['elements'][] = $node;
        } else {
            $this->result[] = $node;
        }
    }

}
