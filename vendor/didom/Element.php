<?php

namespace DiDom;

use DiDom\Exceptions\InvalidSelectorException;
use DOMCdataSection;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * @property string $tag
 */
class Element extends Node
{
    /**
     * @var ClassAttribute
     */
    protected $classAttribute;

    /**
     * @var StyleAttribute
     */
    protected $styleAttribute;

    /**
     * @param DOMElement|DOMText|DOMComment|DOMCdataSection|string $tagName The tag name of an element
     * @param string|null $value The value of an element
     * @param array $attributes The attributes of an element
     */
    public function __construct($tagName, $value = null, array $attributes = [])
    {
        if (is_string($tagName)) {
            $document = new DOMDocument('1.0', 'UTF-8');

            $node = $document->createElement($tagName);

            $this->setNode($node);
        } else {
            $this->setNode($tagName);
        }

        if ($value !== null) {
            $this->setValue($value);
        }

        foreach ($attributes as $attrName => $attrValue) {
            $this->setAttribute($attrName, $attrValue);
        }
    }

    /**
     * Creates a new element.
     *
     * @param DOMNode|string $name The tag name of an element
     * @param string|null $value The value of an element
     * @param array $attributes The attributes of an element
     *
     * @return Element
     */
    public static function create($name, $value = null, array $attributes = [])
    {
        return new Element($name, $value, $attributes);
    }

    /**
     * Creates a new element node by CSS selector.
     *
     * @param string $selector
     * @param string|null $value
     * @param array $attributes
     *
     * @return Element
     *
     * @throws InvalidSelectorException
     */
    public static function createBySelector($selector, $value = null, array $attributes = [])
    {
        return Document::create()->createElementBySelector($selector, $value, $attributes);
    }

    /**
     * Checks that the node matches selector.
     *
     * @param string $selector CSS selector
     * @param bool $strict
     *
     * @return bool
     *
     * @throws InvalidSelectorException if the selector is invalid
     * @throws InvalidArgumentException if the tag name is not a string
     * @throws RuntimeException if the tag name is not specified in strict mode
     */
    public function matches($selector, $strict = false)
    {
        if ( ! is_string($selector)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, gettype($selector)));
        }

        if ( ! $this->node instanceof DOMElement) {
            return false;
        }

        if ($selector === '*') {
            return true;
        }

        if ( ! $strict) {
            $innerHtml = $this->html();
            $html = "<root>$innerHtml</root>";

            $selector = 'root > ' . trim($selector);

            $document = new Document();

            $document->loadHtml($html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

            return $document->has($selector);
        }

        $segments = Query::getSegments($selector);

        if ( ! array_key_exists('tag', $segments)) {
            throw new RuntimeException(sprintf('Tag name must be specified in %s', $selector));
        }

        if ($segments['tag'] !== $this->tag && $segments['tag'] !== '*') {
            return false;
        }

        $segments['id'] = array_key_exists('id', $segments) ? $segments['id'] : null;

        if ($segments['id'] !== $this->getAttribute('id')) {
            return false;
        }

        $classes = $this->hasAttribute('class') ? explode(' ', trim($this->getAttribute('class'))) : [];

        $segments['classes'] = array_key_exists('classes', $segments) ? $segments['classes'] : [];

        $diff1 = array_diff($segments['classes'], $classes);
        $diff2 = array_diff($classes, $segments['classes']);

        if (count($diff1) > 0 || count($diff2) > 0) {
            return false;
        }

        $attributes = $this->attributes();

        unset($attributes['id'], $attributes['class']);

        $segments['attributes'] = array_key_exists('attributes', $segments) ? $segments['attributes'] : [];

        $diff1 = array_diff_assoc($segments['attributes'], $attributes);
        $diff2 = array_diff_assoc($attributes, $segments['attributes']);

        // if the attributes are not equal
        if (count($diff1) > 0 || count($diff2) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Determine if an attribute exists on the element.
     *
     * @param string $name The name of an attribute
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return $this->node->hasAttribute($name);
    }

    /**
     * Set an attribute on the element.
     *
     * @param string $name The name of an attribute
     * @param string $value The value of an attribute
     *
     * @return Element
     */
    public function setAttribute($name, $value)
    {
        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if ( ! is_string($value) && $value !== null) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 2 to be string or null, %s given', __METHOD__, (is_object($value) ? get_class($value) : gettype($value))));
        }

        $this->node->setAttribute($name, $value);

        return $this;
    }

    /**
     * Access to the element's attributes.
     *
     * @param string $name The name of an attribute
     * @param string|null $default The value returned if the attribute doesn't exist
     *
     * @return string|null The value of an attribute or null if attribute doesn't exist
     */
    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->node->getAttribute($name);
        }

        return $default;
    }

    /**
     * Unset an attribute on the element.
     *
     * @param string $name The name of an attribute
     *
     * @return Element
     */
    public function removeAttribute($name)
    {
        $this->node->removeAttribute($name);

        return $this;
    }

    /**
     * Unset all attributes of the element.
     *
     * @param string[] $exclusions
     *
     * @return Element
     */
    public function removeAllAttributes(array $exclusions = [])
    {
        if ( ! $this->node instanceof DOMElement) {
            return $this;
        }

        foreach ($this->attributes() as $name => $value) {
            if (in_array($name, $exclusions, true)) {
                continue;
            }

            $this->node->removeAttribute($name);
        }

        return $this;
    }

    /**
     * Alias for getAttribute and setAttribute methods.
     *
     * @param string $name The name of an attribute
     * @param string|null $value The value that will be returned an attribute doesn't exist
     *
     * @return string|null|Element
     */
    public function attr($name, $value = null)
    {
        if ($value === null) {
            return $this->getAttribute($name);
        }

        return $this->setAttribute($name, $value);
    }

    /**
     * Returns the node attributes or null, if it is not DOMElement.
     *
     * @param string[] $names
     *
     * @return array|null
     */
    public function attributes(array $names = null)
    {
        if ( ! $this->node instanceof DOMElement) {
            return null;
        }

        if ($names === null) {
            $result = [];

            foreach ($this->node->attributes as $name => $attribute) {
                $result[$name] = $attribute->value;
            }

            return $result;
        }

        $result = [];

        foreach ($this->node->attributes as $name => $attribute) {
            if (in_array($name, $names, true)) {
                $result[$name] = $attribute->value;
            }
        }

        return $result;
    }

    /**
     * @return ClassAttribute
     *
     * @throws LogicException if the node is not an instance of DOMElement
     */
    public function classes()
    {
        if ($this->classAttribute !== null) {
            return $this->classAttribute;
        }

        if ( ! $this->isElementNode()) {
            throw new LogicException('Class attribute is available only for element nodes');
        }

        $this->classAttribute = new ClassAttribute($this);

        return $this->classAttribute;
    }

    /**
     * @return StyleAttribute
     *
     * @throws LogicException if the node is not an instance of DOMElement
     */
    public function style()
    {
        if ($this->styleAttribute !== null) {
            return $this->styleAttribute;
        }

        if ( ! $this->isElementNode()) {
            throw new LogicException('Style attribute is available only for element nodes');
        }

        $this->styleAttribute = new StyleAttribute($this);

        return $this->styleAttribute;
    }

    /**
     * Dynamically set an attribute on the element.
     *
     * @param string $name The name of an attribute
     * @param string $value The value of an attribute
     *
     * @return Element
     */
    public function __set($name, $value)
    {
        return $this->setAttribute($name, $value);
    }

    /**
     * Dynamically access the element's attributes.
     *
     * @param string $name The name of an attribute
     *
     * @return string|null
     */
    public function __get($name)
    {
        if ($name === 'tag') {
            return $this->node->tagName;
        }

        return $this->getAttribute($name);
    }

    /**
     * Determine if an attribute exists on the element.
     *
     * @param string $name The attribute name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->hasAttribute($name);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $name The name of an attribute
     */
    public function __unset($name)
    {
        $this->removeAttribute($name);
    }
}
