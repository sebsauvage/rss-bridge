<?php

namespace DiDom;

use DiDom\Exceptions\InvalidSelectorException;
use InvalidArgumentException;
use RuntimeException;

class Query
{
    /**
     * Types of expression.
     *
     * @const string
     */
    const TYPE_XPATH = 'XPATH';
    const TYPE_CSS   = 'CSS';

    /**
     * @var array
     */
    protected static $compiled = [];

    /**
     * Converts a CSS selector into an XPath expression.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return string XPath expression
     *
     * @throws InvalidSelectorException if the expression is empty
     */
    public static function compile($expression, $type = self::TYPE_CSS)
    {
        if ( ! is_string($expression)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, gettype($expression)));
        }

        if ( ! is_string($type)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 2 to be string, %s given', __METHOD__, gettype($type)));
        }

        if (strcasecmp($type, self::TYPE_XPATH) !== 0 && strcasecmp($type, self::TYPE_CSS) !== 0) {
            throw new RuntimeException(sprintf('Unknown expression type "%s"', $type));
        }

        $expression = trim($expression);

        if ($expression === '') {
            throw new InvalidSelectorException('The expression must not be empty');
        }

        if (strcasecmp($type, self::TYPE_XPATH) === 0) {
            return $expression;
        }

        if ( ! array_key_exists($expression, static::$compiled)) {
            static::$compiled[$expression] = static::cssToXpath($expression);
        }

        return static::$compiled[$expression];
    }

    /**
     * Converts a CSS selector into an XPath expression.
     *
     * @param string $selector A CSS selector
     * @param string $prefix Specifies the nesting of nodes
     *
     * @return string XPath expression
     *
     * @throws InvalidSelectorException
     */
    public static function cssToXpath($selector, $prefix = '//')
    {
        $paths = [];

        while ($selector !== '') {
            list($xpath, $selector) = static::parseAndConvertSelector($selector, $prefix);

            if (substr($selector, 0, 1) === ',') {
                $selector = trim($selector, ', ');
            }

            $paths[] = $xpath;
        }

        return implode('|', $paths);
    }

    /**
     * @param string $selector
     * @param string $prefix
     *
     * @return array
     *
     * @throws InvalidSelectorException
     */
    protected static function parseAndConvertSelector($selector, $prefix = '//')
    {
        if (substr($selector, 0, 1) === '>') {
            $prefix = '/';

            $selector = ltrim($selector, '> ');
        }

        $segments = self::getSegments($selector);
        $xpath = '';

        while (count($segments) > 0) {
            $xpath .= self::buildXpath($segments, $prefix);

            $selector = trim(substr($selector, strlen($segments['selector'])));
            $prefix = isset($segments['rel']) ? '/' : '//';

            if ($selector === '' || substr($selector, 0, 2) === '::' || substr($selector, 0, 1) === ',') {
                break;
            }

            $segments = self::getSegments($selector);
        }

        // if selector has property
        if (substr($selector, 0, 2) === '::') {
            $property = self::parseProperty($selector);
            $propertyXpath = self::convertProperty($property['name'], $property['args']);

            $selector = substr($selector, strlen($property['property']));
            $selector = trim($selector);

            $xpath .= '/' . $propertyXpath;
        }

        return [$xpath, $selector];
    }

    /**
     * @param string $selector
     *
     * @return array
     *
     * @throws InvalidSelectorException
     */
    protected static function parseProperty($selector)
    {
        $name = '(?P<name>[\w\-]+)';
        $args = '(?:\((?P<args>[^\)]+)?\))?';

        $regexp = '/^::' . $name . $args . '/is';

        if (preg_match($regexp, $selector, $matches) !== 1) {
            throw new InvalidSelectorException(sprintf('Invalid property "%s"', $selector));
        }

        $result = [];

        $result['property'] = $matches[0];
        $result['name'] = $matches['name'];
        $result['args'] = isset($matches['args']) ? explode(',', $matches['args']) : [];

        $result['args'] = array_map('trim', $result['args']);

        return $result;
    }

    /**
     * @param string $name
     * @param array $parameters
     *
     * @return string
     *
     * @throws InvalidSelectorException if the specified property is unknown
     */
    protected static function convertProperty($name, array $parameters = [])
    {
        if ($name === 'text') {
            return 'text()';
        }

        if ($name === 'attr') {
            if (count($parameters) === 0) {
                return '@*';
            }

            $attributes = [];

            foreach ($parameters as $attribute) {
                $attributes[] = sprintf('name() = "%s"', $attribute);
            }

            return sprintf('@*[%s]', implode(' or ', $attributes));
        }

        throw new InvalidSelectorException(sprintf('Unknown property "%s"', $name));
    }

    /**
     * Converts a CSS pseudo-class into an XPath expression.
     *
     * @param string $pseudo Pseudo-class
     * @param string $tagName
     * @param array $parameters
     *
     * @return string
     *
     * @throws InvalidSelectorException if the specified pseudo-class is unknown
     */
    protected static function convertPseudo($pseudo, &$tagName, array $parameters = [])
    {
        switch ($pseudo) {
            case 'first-child':
                return 'position() = 1';
                break;
            case 'last-child':
                return 'position() = last()';
                break;
            case 'nth-child':
                $xpath = sprintf('(name()="%s") and (%s)', $tagName, self::convertNthExpression($parameters[0]));
                $tagName = '*';

                return $xpath;
                break;
            case 'contains':
                $string = trim($parameters[0], '\'"');

                if (count($parameters) === 1) {
                    return self::convertContains($string);
                }

                if ($parameters[1] !== 'true' && $parameters[1] !== 'false') {
                    throw new InvalidSelectorException(sprintf('Parameter 2 of "contains" pseudo-class must be equal true or false, "%s" given', $parameters[1]));
                }

                $caseSensitive = $parameters[1] === 'true';

                if (count($parameters) === 2) {
                    return self::convertContains($string, $caseSensitive);
                }

                if ($parameters[2] !== 'true' && $parameters[2] !== 'false') {
                    throw new InvalidSelectorException(sprintf('Parameter 3 of "contains" pseudo-class must be equal true or false, "%s" given', $parameters[2]));
                }

                $fullMatch = $parameters[2] === 'true';

                return self::convertContains($string, $caseSensitive, $fullMatch);
                break;
            case 'has':
                return self::cssToXpath($parameters[0], './/');
                break;
            case 'not':
                return sprintf('not(self::%s)', self::cssToXpath($parameters[0], ''));
                break;
            case 'nth-of-type':
                return self::convertNthExpression($parameters[0]);
                break;
            case 'empty':
                return 'count(descendant::*) = 0';
                break;
            case 'not-empty':
                return 'count(descendant::*) > 0';
                break;
        }

        throw new InvalidSelectorException(sprintf('Unknown pseudo-class "%s"', $pseudo));
    }

    /**
     * @param array $segments
     * @param string $prefix Specifies the nesting of nodes
     *
     * @return string XPath expression
     *
     * @throws InvalidArgumentException if you neither specify tag name nor attributes
     */
    public static function buildXpath(array $segments, $prefix = '//')
    {
        $tagName = isset($segments['tag']) ? $segments['tag'] : '*';

        $attributes = [];

        // if the id attribute specified
        if (isset($segments['id'])) {
            $attributes[] = sprintf('@id="%s"', $segments['id']);
        }

        // if the class attribute specified
        if (isset($segments['classes'])) {
            foreach ($segments['classes'] as $class) {
                $attributes[] = sprintf('contains(concat(" ", normalize-space(@class), " "), " %s ")', $class);
            }
        }

        // if the attributes specified
        if (isset($segments['attributes'])) {
            foreach ($segments['attributes'] as $name => $value) {
                $attributes[] = self::convertAttribute($name, $value);
            }
        }

        // if the pseudo class specified
        if (isset($segments['pseudo'])) {
            $expression = isset($segments['expr']) ? trim($segments['expr']) : '';

            $parameters = explode(',', $expression);
            $parameters = array_map('trim', $parameters);

            $attributes[] = self::convertPseudo($segments['pseudo'], $tagName, $parameters);
        }

        if (count($attributes) === 0 && ! isset($segments['tag'])) {
            throw new InvalidArgumentException('The array of segments must contain the name of the tag or at least one attribute');
        }

        $xpath = $prefix . $tagName;

        if ($count = count($attributes)) {
            $xpath .= ($count > 1) ? sprintf('[(%s)]', implode(') and (', $attributes)) : sprintf('[%s]', $attributes[0]);
        }

        return $xpath;
    }

    /**
     * @param string $name The name of an attribute
     * @param string $value The value of an attribute
     *
     * @return string
     */
    protected static function convertAttribute($name, $value)
    {
        $isSimpleSelector = ! in_array(substr($name, 0, 1), ['^', '!'], true);
        $isSimpleSelector = $isSimpleSelector && ( ! in_array(substr($name, -1), ['^', '$', '*', '!', '~'], true));

        if ($isSimpleSelector) {
            // if specified only the attribute name
            $xpath = $value === null ? '@'.$name : sprintf('@%s="%s"', $name, $value);

            return $xpath;
        }

        // if the attribute name starts with ^
        // example: *[^data-]
        if (substr($name, 0, 1) === '^') {
            $xpath = sprintf('@*[starts-with(name(), "%s")]', substr($name, 1));

            return $value === null ? $xpath : sprintf('%s="%s"', $xpath, $value);
        }

        // if the attribute name starts with !
        // example: input[!disabled]
        if (substr($name, 0, 1) === '!') {
            $xpath = sprintf('not(@%s)', substr($name, 1));

            return $xpath;
        }

        $symbol = substr($name, -1);
        $name = substr($name, 0, -1);

        switch ($symbol) {
            case '^':
                $xpath = sprintf('starts-with(@%s, "%s")', $name, $value);
                break;
            case '$':
                $xpath = sprintf('substring(@%s, string-length(@%s) - string-length("%s") + 1) = "%s"', $name, $name, $value, $value);
                break;
            case '*':
                $xpath = sprintf('contains(@%s, "%s")', $name, $value);
                break;
            case '!':
                $xpath = sprintf('not(@%s="%s")', $name, $value);
                break;
            case '~':
                $xpath = sprintf('contains(concat(" ", normalize-space(@%s), " "), " %s ")', $name, $value);
                break;
        }

        return $xpath;
    }

    /**
     * Converts nth-expression into an XPath expression.
     *
     * @param string $expression nth-expression
     *
     * @return string
     *
     * @throws InvalidSelectorException if the given nth-child expression is empty or invalid
     */
    protected static function convertNthExpression($expression)
    {
        if ($expression === '') {
            throw new InvalidSelectorException('nth-child (or nth-last-child) expression must not be empty');
        }

        if ($expression === 'odd') {
            return 'position() mod 2 = 1 and position() >= 1';
        }

        if ($expression === 'even') {
            return 'position() mod 2 = 0 and position() >= 0';
        }

        if (is_numeric($expression)) {
            return sprintf('position() = %d', $expression);
        }

        if (preg_match("/^(?P<mul>[0-9]?n)(?:(?P<sign>\+|\-)(?P<pos>[0-9]+))?$/is", $expression, $segments)) {
            if (isset($segments['mul'])) {
                $multiplier = $segments['mul'] === 'n' ? 1 : trim($segments['mul'], 'n');
                $sign = (isset($segments['sign']) && $segments['sign'] === '+') ? '-' : '+';
                $position = isset($segments['pos']) ? $segments['pos'] : 0;

                return sprintf('(position() %s %d) mod %d = 0 and position() >= %d', $sign, $position, $multiplier, $position);
            }
        }

        throw new InvalidSelectorException(sprintf('Invalid nth-child expression "%s"', $expression));
    }

    /**
     * @param string $string
     * @param bool $caseSensitive
     * @param bool $fullMatch
     *
     * @return string
     */
    protected static function convertContains($string, $caseSensitive = true, $fullMatch = false)
    {
        if ($caseSensitive && $fullMatch) {
            return sprintf('text() = "%s"', $string);
        }

        if ($caseSensitive && ! $fullMatch) {
            return sprintf('contains(text(), "%s")', $string);
        }

        $strToLowerFunction = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

        if ( ! $caseSensitive && $fullMatch) {
            return sprintf("php:functionString(\"{$strToLowerFunction}\", .) = php:functionString(\"{$strToLowerFunction}\", \"%s\")", $string);
        }

        // if ! $caseSensitive and ! $fullMatch
        return sprintf("contains(php:functionString(\"{$strToLowerFunction}\", .), php:functionString(\"{$strToLowerFunction}\", \"%s\"))", $string);
    }

    /**
     * Splits the CSS selector into parts (tag name, ID, classes, attributes, pseudo-class).
     *
     * @param string $selector CSS selector
     *
     * @return array
     *
     * @throws InvalidSelectorException if the selector is empty or not valid
     */
    public static function getSegments($selector)
    {
        $selector = trim($selector);

        if ($selector === '') {
            throw new InvalidSelectorException('The selector must not be empty');
        }

        $tag = '(?P<tag>[\*|\w|\-]+)?';
        $id = '(?:#(?P<id>[\w|\-]+))?';
        $classes = '(?P<classes>\.[\w|\-|\.]+)*';
        $attrs = '(?P<attrs>(?:\[.+?\])*)?';
        $name = '(?P<pseudo>[\w\-]+)';
        $expr = '(?:\((?P<expr>[^\)]+)\))';
        $pseudo = '(?::'.$name.$expr.'?)?';
        $rel = '\s*(?P<rel>>)?';

        $regexp = '/'.$tag.$id.$classes.$attrs.$pseudo.$rel.'/is';

        if (preg_match($regexp, $selector, $segments)) {
            if ($segments[0] === '') {
                throw new InvalidSelectorException(sprintf('Invalid selector "%s"', $selector));
            }

            $result['selector'] = $segments[0];

            if (isset($segments['tag']) && $segments['tag'] !== '') {
                $result['tag'] = $segments['tag'];
            }

            // if the id attribute specified
            if (isset($segments['id']) && $segments['id'] !== '') {
                $result['id'] = $segments['id'];
            }

            // if the attributes specified
            if (isset($segments['attrs'])) {
                $attributes = trim($segments['attrs'], '[]');
                $attributes = explode('][', $attributes);

                foreach ($attributes as $attribute) {
                    if ($attribute !== '') {
                        list($name, $value) = array_pad(explode('=', $attribute, 2), 2, null);

                        if ($name === '') {
                            throw new InvalidSelectorException(sprintf('Invalid selector "%s": attribute name must not be empty', $selector));
                        }

                        // equal null if specified only the attribute name
                        $result['attributes'][$name] = is_string($value) ? trim($value, '\'"') : null;
                    }
                }
            }

            // if the class attribute specified
            if (isset($segments['classes'])) {
                $classes = trim($segments['classes'], '.');
                $classes = explode('.', $classes);

                foreach ($classes as $class) {
                    if ($class !== '') {
                        $result['classes'][] = $class;
                    }
                }
            }

            // if the pseudo class specified
            if (isset($segments['pseudo']) && $segments['pseudo'] !== '') {
                $result['pseudo'] = $segments['pseudo'];

                if (isset($segments['expr']) && $segments['expr'] !== '') {
                    $result['expr'] = $segments['expr'];
                }
            }

            // if it is a direct descendant
            if (isset($segments['rel'])) {
                $result['rel'] = $segments['rel'];
            }

            return $result;
        }

        throw new InvalidSelectorException(sprintf('Invalid selector "%s"', $selector));
    }

    /**
     * @return array
     */
    public static function getCompiled()
    {
        return static::$compiled;
    }

    /**
     * @param array $compiled
     *
     * @throws InvalidArgumentException if the attributes is not an array
     */
    public static function setCompiled(array $compiled)
    {
        static::$compiled = $compiled;
    }
}
