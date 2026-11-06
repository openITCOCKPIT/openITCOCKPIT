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


use Cake\Log\Log;
use JBBCode\DocumentElement;
use JBBCode\ElementNode;
use JBBCode\TextNode;
use JBBCode\Tokenizer;
use Throwable;

/**
 * Renders mixed Markdown and BBCode as safe HTML.
 */
class BBCMarkDownParser extends BBCodeParser {

    /**
     * LF line breaks are represented as a backslash followed by n.
     *
     * @var string
     */
    public const ESCAPED_LF = 'escaped_lf';

    /**
     * The source already contains real line breaks.
     *
     * @var string
     */
    public const LITERAL_NEWLINES = 'literal_newlines';

    /**
     * Convert text containing Markdown and BBCode to safe HTML.
     * Preserve line breaks without adding paragraph wrappers.
     *
     * @param string|null $source
     * @param string $representation Use LITERAL_NEWLINES for text or ESCAPED_LF for encoded line breaks.
     * @return string
     * @throws \InvalidArgumentException
     */
    public function render(?string $source, string $representation = self::LITERAL_NEWLINES): string {
        $source = $source ?? '';
        if ($representation === self::ESCAPED_LF) {
            $source = str_replace('\\n', "\n", $source);
        } else if ($representation !== self::LITERAL_NEWLINES) {
            throw new \InvalidArgumentException('Unknown source representation');
        }
        $source = str_replace(["\r\n", "\r"], "\n", $source);

        try {
            return $this->renderHtml($source);
        } catch (Throwable $error) {
            // Exception messages can contain source text; log only the error class.
            $this->reportFailure($error);
            return '<pre>' . htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        }
    }

    /**
     * Render the combined output with the BBCode-aware Parsedown extension.
     *
     * @param string $source
     * @return string
     */
    protected function renderHtml(string $source): string {
        $BBCodeParsedown = new BBCodeParsedown($this);
        return $BBCodeParsedown->render($source);
    }

    /**
     * Log the error class without exposing the source text.
     *
     * @param Throwable $error
     * @return void
     */
    protected function reportFailure(Throwable $error): void {
        try {
            Log::error('Markdown and BBCode rendering failed (' . get_class($error) . ')');
        } catch (Throwable $loggingError) {
            // Logging failures must not prevent the escaped-text fallback.
        }
    }

    /**
     * Read a registered BBCode token using JBBCode's option parser.
     *
     * @param string $source
     * @return array|null
     */
    public function readToken(string $source): ?array {
        if (!preg_match('/^\[(\/?)([a-z][a-z0-9-]*)(?=[=\] ])/i', $source, $match)) {
            return null;
        }
        $tag = strtolower($match[2]);
        if (!$this->codeExists($tag) && !$this->codeExists($tag, true)) {
            return null;
        }
        if ($match[1] === '/') {
            if (!preg_match('/^\[\/[a-z][a-z0-9-]*\]/i', $source, $close)) {
                return null;
            }
            return [
                'tag'     => $tag,
                'closing' => true,
                'source'  => $close[0]
            ];
        }
        $DocumentElement = new DocumentElement();
        $Tokenizer = new Tokenizer($source);
        try {
            $ElementNode = $this->parseStartState($DocumentElement, $Tokenizer);
        } catch (\ValueError $error) {
            // JBBCode 1.4's malformed multi-option input can reach array_combine().
            return null;
        }
        if ($ElementNode === $DocumentElement) {
            return null;
        }
        if ($ElementNode->getAttribute()) {
            $ElementNode->setAttribute(array_change_key_case($ElementNode->getAttribute(), CASE_LOWER));
        }
        $length = strlen($source) - strlen($Tokenizer->toString());
        return [
            'tag'     => $tag,
            'closing' => false,
            'source'  => substr($source, 0, $length),
            'node'    => $ElementNode
        ];
    }

    /**
     * Check if the token content must remain literal.
     *
     * @param array $token
     * @return bool
     */
    public function isAtomic(array $token): bool {
        return !$token['closing'] && ($token['tag'] === 'code' || !$token['node']->getCodeDefinition()->parseContent());
    }

    /**
     * Read a code, URL or image body without interpreting its content.
     *
     * @param string $source
     * @param array $token
     * @return array
     */
    public function readAtomic(string $source, array $token): array {
        $ElementNode = $token['node'];
        $bodySource = substr($source, strlen($token['source']));
        $Tokenizer = new Tokenizer($bodySource);
        // Also protect [code], whose legacy definition deliberately remains unchanged.
        $this->parseAsTextUntilClose($ElementNode, $Tokenizer);
        $consumed = strlen($bodySource) - strlen($Tokenizer->toString());
        $close = '[/' . $token['tag'] . ']';
        $complete = $consumed >= strlen($close)
            && strcasecmp(substr($bodySource, $consumed - strlen($close), strlen($close)), $close) === 0;
        $length = strlen($token['source']) + $consumed;
        $atomic = $token + [
            'complete' => $complete,
            'extent'   => $length,
            'original' => substr($source, 0, $length),
            'body'     => $complete ? substr($bodySource, 0, $consumed - strlen($close)) : $bodySource
        ];
        $element = $atomic['complete'] ? self::templateElement($atomic['node'], $atomic['body']) : null;
        $atomic['element'] = ($element ?? ['text' => $atomic['original']]) + ['source' => $atomic['original']];
        return $atomic;
    }

    /**
     * Apply the registered BBCode definitions to the Markdown nodes.
     *
     * @param array $events
     * @return array
     */
    public function parseMixedNodes(array $events): array {
        $this->reset();
        $Parent = $this->treeRoot;
        $NodeMap = new \WeakMap();
        foreach ($events as $index => $event) {
            $target = null;
            if ($event['type'] === 'bb') {
                $token = $event['token'];
                if (!$token['closing']) {
                    $Parent = $this->parseStartState($Parent, new Tokenizer($token['source']));
                    $Parent->setAttribute($token['node']->getAttribute() ?? []);
                    $NodeMap[$Parent] = [
                        'id'     => 'bb' . $index,
                        'source' => $token['source']
                    ];
                    continue;
                }
                $target = $Parent->closestParentOfType($token['tag']);
                if ($target === $Parent) {
                    $NodeMap[$Parent] += ['close' => $token['source']];
                    $Parent = $this->parseStartState($Parent, new Tokenizer($token['source']));
                    continue;
                }
                // JBBCode knows which ancestors a crossing close would end. Keep
                // their original tags literal instead of accepting auto-closing.
                for ($Scope = $Parent; $target !== null && $Scope !== $target->getParent(); $Scope = $Scope->getParent()) {
                    $NodeMap[$Scope] += ['invalid' => true];
                }
                $event = [
                    'type' => 'leaf',
                    'node' => ['text' => $token['source']]
                ];
            }
            $TextNode = new TextNode('');
            $Parent->addChild($TextNode);
            $NodeMap[$TextNode] = $event;
            if ($target !== null) {
                $Parent = $target->getParent();
            }
        }
        $this->removeOverNestedElements();
        return $this->readNodes($this->treeRoot, $NodeMap);
    }

    /**
     * Convert the JBBCode tree to structured render nodes.
     *
     * @param ElementNode $Parent
     * @param \WeakMap $NodeMap
     * @return array
     * @throws \UnexpectedValueException
     */
    private function readNodes(ElementNode $Parent, \WeakMap $NodeMap): array {
        $events = [];
        foreach ($Parent->getChildren() as $Child) {
            if (!isset($NodeMap[$Child])) {
                throw new \UnexpectedValueException('Unexpected JBBCode node');
            }
            $data = $NodeMap[$Child];
            if ($Child instanceof TextNode) {
                $events[] = $data;
                continue;
            }
            $template = isset($data['close']) && empty($data['invalid']) ? self::templateElement($Child) : null;
            $events[] = $template !== null
                ? [
                    'type' => 'bbOpen',
                    'node' => $template
                ] + $data
                : [
                    'type' => 'leaf',
                    'node' => ['text' => $data['source']]
                ];
            array_push($events, ...$this->readNodes($Child, $NodeMap));
            if (isset($data['close'])) {
                $events[] = $template !== null
                    ? [
                        'type' => 'bbClose',
                        'id'   => $data['id']
                    ]
                    : [
                        'type' => 'leaf',
                        'node' => ['text' => $data['close']]
                    ];
            }
        }
        return $events;
    }

    /**
     * Convert a registered BBCode template to a safe Parsedown element.
     *
     * @param ElementNode $ElementNode
     * @param string|null $body
     * @return array|null
     */
    private static function templateElement(ElementNode $ElementNode, ?string $body = null): ?array {
        $CodeDefinition = $ElementNode->getCodeDefinition();
        if (!$CodeDefinition->hasValidInputs($ElementNode)) {
            return null;
        }
        $options = $ElementNode->getAttribute() ?? [];
        if ($CodeDefinition->usesOption() && count($options) !== 1) {
            return null;
        }
        $option = $options ? (string)reset($options) : '';
        $template = $CodeDefinition->getReplacementText();
        $element = null;

        if (preg_match('/^<(strong|em|u)>\{param\}<\/\1>$/D', $template, $match)) {
            $element = ['name' => $match[1]];
        } else if ($template === '<span style="color: {option}">{param}</span>' && self::isColor($option)) {
            $element = [
                'name'       => 'span',
                'attributes' => ['style' => 'color: ' . $option]
            ];
        } else if (preg_match('/^<span style="font-size: (xx-small|x-small|small|large|x-large|xx-large);">\{param\}<\/span>$/D', $template, $match)) {
            $element = [
                'name'       => 'span',
                'attributes' => ['style' => 'font-size: ' . $match[1] . ';']
            ];
        } else if (preg_match('/^<p align="(left|right|center|justify)">\{param\}<\/p>$/D', $template, $match)) {
            $element = [
                'name'       => 'div',
                'attributes' => ['style' => 'text-align: ' . $match[1]]
            ];
        } else if ($template === '<a href="{option}">{param}</a>') {
            $element = [
                'name'       => 'a',
                'attributes' => ['href' => $option]
            ];
        } else if ($body !== null && $template === '<a href="{param}">{param}</a>') {
            $element = [
                'name'       => 'a',
                'attributes' => ['href' => $body],
                'text'       => $body
            ];
        } else if ($body !== null && in_array($template, ['<img src="{param}" />', '<img src="{param}" alt="{option}" />'], true)) {
            $element = [
                'name'       => 'img',
                'attributes' => ['src' => $body]
            ];
            if ($CodeDefinition->usesOption()) {
                $element['attributes']['alt'] = $option;
            }
        } else if ($body !== null && $template === '<pre style="line-height: 12px;">{param}</pre>') {
            $element = [
                'name'    => 'pre',
                'element' => [
                    'name' => 'code',
                    'text' => $body
                ]
            ];
        }
        if ($element !== null) {
            $element['autobreak'] = in_array($element['name'], ['div', 'pre'], true);
        }
        return $element;
    }

    /**
     * Allow color literals without accepting arbitrary CSS declarations.
     *
     * @param string $value
     * @return bool
     */
    private static function isColor(string $value): bool {
        // Restrict dynamic CSS to a color literal. The browser handles unsupported
        // names/ranges; arbitrary functions, escapes and extra declarations are excluded.
        return (bool)preg_match('/\A(?:[a-z]+|#(?:[a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})|(?:rgb|hsl)a?\([0-9., %]+\))\z/i', $value);
    }

}
