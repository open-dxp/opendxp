<?php
declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_HeadLink
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace OpenDxp\Twig\Extension\Templating;

use OpenDxp;
use OpenDxp\Event\FrontendEvents;
use OpenDxp\Twig\Extension\Templating\Placeholder\CacheBusterAware;
use OpenDxp\Twig\Extension\Templating\Placeholder\Container;
use OpenDxp\Twig\Extension\Templating\Placeholder\ContainerService;
use OpenDxp\Twig\Extension\Templating\Placeholder\Exception;
use OpenDxp\Twig\Extension\Templating\Traits\WebLinksTrait;
use Override;
use stdClass;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * HeadLink
 *
 * @see        http://www.w3.org/TR/xhtml1/dtds.html
 *
 * @method $this appendAlternate($href, $type, $title, $extras)
 * @method $this appendStylesheet($href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 * @method $this offsetSetAlternate($index, $href, $type, $title, $extras)
 * @method $this offsetSetStylesheet($index, $href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 * @method $this prependAlternate($href, $type, $title, $extras)
 * @method $this prependStylesheet($href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 * @method $this setAlternate($href, $type, $title, $extras)
 * @method $this setStylesheet($href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 *
 */
class HeadLink extends CacheBusterAware
{
    use WebLinksTrait;

    /**
     * $_validAttributes
     *
     */
    protected array $_itemKeys = [
        'charset',
        'href',
        'hreflang',
        'id',
        'media',
        'rel',
        'rev',
        'type',
        'title',
        'extras',
        'sizes',
    ];

    /**
     * @var string registry key
     */
    protected string $_regKey = 'HeadLink';

    /**
     * Default attributes for generated WebLinks (HTTP/2 push).
     *
     * @var array
     */
    protected $webLinkAttributes = ['as' => 'style'];

    /**
     * HeadLink constructor.
     *
     * Use PHP_EOL as separator
     *
     */
    public function __construct(
        ContainerService $containerService,
        WebLinkExtension $webLinkExtension
    ) {
        parent::__construct($containerService);

        $this->webLinkExtension = $webLinkExtension;
        $this->setSeparator(PHP_EOL);
    }

    /**
     * headLink() - View Helper Method
     *
     * Returns current object instance. Optionally, allows passing array of
     * values to build link.
     *
     *
     * @return $this
     */
    public function __invoke(?array $attributes = null, string $placement = Container::APPEND): static
    {
        if (null !== $attributes) {
            $item = $this->createData($attributes);
            match ($placement) {
                Container::SET => $this->set($item),
                Container::PREPEND => $this->prepend($item),
                default => $this->append($item),
            };
        }

        return $this;
    }

    /**
     * Overload method access
     *
     * Creates the following virtual methods:
     * - appendStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - offsetSetStylesheet($index, $href, $media, $conditionalStylesheet, $extras)
     * - prependStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - setStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - appendAlternate($href, $type, $title, $extras)
     * - offsetSetAlternate($index, $href, $type, $title, $extras)
     * - prependAlternate($href, $type, $title, $extras)
     * - setAlternate($href, $type, $title, $extras)
     */
    #[Override]
    public function __call(string $method, array $args): mixed
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(?P<type>Stylesheet|Alternate)$/', $method, $matches)) {
            $argc = count($args);
            $action = $matches['action'];
            $type = $matches['type'];
            $index = null;

            if ('offsetSet' === $action && 0 < $argc) {
                $index = array_shift($args);
                --$argc;
            }

            if (1 > $argc) {
                throw new Exception(sprintf('%s requires at least one argument', $method));
            }

            if (is_array($args[0])) {
                $item = $this->createData($args[0]);
            } else {
                $dataMethod = 'createData' . $type;
                $item = $this->$dataMethod($args);
            }

            if ($item) {
                if ('offsetSet' === $action) {
                    $this->offsetSet($index, $item);
                } else {
                    $this->$action($item);
                }
            }

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Check if value is valid
     *
     *
     */
    protected function _isValid(mixed $value): bool
    {
        if (!$value instanceof stdClass) {
            return false;
        }

        $vars = get_object_vars($value);
        $keys = array_keys($vars);
        $intersection = array_intersect($this->_itemKeys, $keys);

        return $intersection !== [];
    }

    /**
     * append()
     *
     * @param  stdClass $value
     *
     */
    public function append($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('append() expects a data token; please use one of the custom append*() methods');
        }

        $this->getContainer()->append($value);
    }

    /**
     * offsetSet()
     *
     * @param  string|int $offset
     *
     */
    #[Override]
    public function offsetSet($offset, mixed $value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('offsetSet() expects a data token; please use one of the custom offsetSet*() methods');
        }

        $this->getContainer()->offsetSet($offset, $value);
    }

    /**
     * prepend()
     *
     * @param stdClass $value
     */
    public function prepend($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('prepend() expects a data token; please use one of the custom prepend*() methods');
        }

        $this->getContainer()->prepend($value);
    }

    /**
     * set()
     *
     * @param stdClass $value
     */
    public function set($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('set() expects a data token; please use one of the custom set*() methods');
        }

        $this->getContainer()->set($value);
    }

    /**
     * Create HTML link element from data item
     *
     *
     */
    public function itemToString(stdClass $item): string
    {
        $attributes = (array) $item;
        $link = '<link ';

        foreach ($this->_itemKeys as $itemKey) {
            if (isset($attributes[$itemKey])) {
                if (is_array($attributes[$itemKey])) {
                    foreach ($attributes[$itemKey] as $key => $value) {
                        $link .= sprintf('%s="%s" ', $key, ($this->_autoEscape) ? $this->_escape($value) : $value);
                    }
                } else {
                    $link .= sprintf('%s="%s" ', $itemKey, ($this->_autoEscape) ? $this->_escape($attributes[$itemKey]) : $attributes[$itemKey]);
                }
            }
        }

        $link .= '/>';

        if (($link === '<link />') || ($link === '<link >')) {
            return '';
        }

        if (isset($attributes['conditionalStylesheet'])
            && !empty($attributes['conditionalStylesheet'])
            && is_string($attributes['conditionalStylesheet'])) {
            if (str_replace(' ', '', $attributes['conditionalStylesheet']) === '!IE') {
                $link = '<!-->' . $link . '<!--';
            }
            $link = '<!--[if ' . $attributes['conditionalStylesheet'] . ']>' . $link . '<![endif]-->';
        }

        return $link;
    }

    /**
     * Render link elements as string
     *
     *
     */
    #[Override]
    public function toString(int|string|null $indent = null): string
    {
        $this->prepareEntries();

        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $items = [];
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            $items[] = $this->itemToString($item);
        }

        return $indent . implode($this->_escape($this->getSeparator()) . $indent, $items);
    }

    /**
     * prepares entries with cache buster prefix
     */
    protected function prepareEntries(): void
    {
        foreach ($this as &$item) {
            // adds the automatic cache buster functionality
            if ($this->isCacheBuster() && isset($item->href)) {
                $realFile = OPENDXP_WEB_ROOT . $item->href;
                if (file_exists($realFile)) {
                    $item->href = '/cache-buster-' . filemtime($realFile) . $item->href;
                }
            }

            $event = new GenericEvent($this, [
                'item' => $item,
            ]);
            OpenDxp::getEventDispatcher()->dispatch($event, FrontendEvents::VIEW_HELPER_HEAD_LINK);

            $source = $item->href ?? '';
            $itemAttributes = $item->extras ?? [];

            if (isset($item->extras) && is_array($item->extras) && isset($item->extras['webLink'])) {
                unset($item->extras['webLink']);
            }

            if (is_array($itemAttributes) && !empty($source)) {
                $this->handleWebLink($item, $source, $itemAttributes);
            }
        }
    }

    /**
     * Create data item for stack
     *
     *
     */
    public function createData(array $attributes): stdClass
    {
        return (object) $attributes;
    }

    /**
     * Create item for stylesheet link item
     *
     * @return stdClass|false Returns false if stylesheet is a duplicate
     */
    public function createDataStylesheet(array $args): bool|stdClass
    {
        $rel = 'stylesheet';
        $type = 'text/css';
        $media = 'screen';
        $conditionalStylesheet = false;
        $extras = [];
        $href = array_shift($args);

        if ($this->_isDuplicateStylesheet($href)) {
            return false;
        }

        if (0 < count($args)) {
            $media = array_shift($args);
            $media = is_array($media) ? implode(',', $media) : (string) $media;
        }
        if (0 < count($args)) {
            $conditionalStylesheet = array_shift($args);
            if (empty($conditionalStylesheet) || !is_string($conditionalStylesheet)) {
                $conditionalStylesheet = null;
            }
        }

        if (0 < count($args) && is_array($args[0])) {
            $extras = array_shift($args);
            $extras = (array) $extras;
        }

        $attributes = ['rel' => $rel, 'type' => $type, 'href' => $href, 'media' => $media, 'conditionalStylesheet' => $conditionalStylesheet, 'extras' => $extras];

        return $this->createData($this->_applyExtras($attributes));
    }

    /**
     * Is the linked stylesheet a duplicate?
     *
     *
     */
    protected function _isDuplicateStylesheet(string $uri): bool
    {
        foreach ($this->getContainer() as $item) {
            if (($item->rel == 'stylesheet') && ($item->href == $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create item for alternate link item
     *
     *
     */
    public function createDataAlternate(array $args): stdClass
    {
        if (3 > count($args)) {
            throw new Exception(sprintf('Alternate tags require 3 arguments; %s provided', count($args)));
        }

        $rel = 'alternate';
        $href = array_shift($args);
        $type = array_shift($args);
        $title = array_shift($args);
        $extras = [];

        if (0 < count($args) && is_array($args[0])) {
            $extras = array_shift($args);
            $extras = (array) $extras;

            if (isset($extras['media']) && is_array($extras['media'])) {
                $extras['media'] = implode(',', $extras['media']);
            }
        }

        $href = (string) $href;
        $type = (string) $type;
        $title = (string) $title;

        $attributes = ['rel' => $rel, 'href' => $href, 'type' => $type, 'title' => $title, 'extras' => $extras];

        return $this->createData($this->_applyExtras($attributes));
    }

    /**
     * Apply any overrides specified in the 'extras' array
     *
     *
     */
    protected function _applyExtras(array $attributes): array
    {
        if (isset($attributes['extras'])) {
            foreach ($attributes['extras'] as $eKey => $eVal) {
                if (isset($attributes[$eKey])) {
                    $attributes[$eKey] = $eVal;
                    unset($attributes['extras'][$eKey]);
                }
            }
        }

        return $attributes;
    }
}
