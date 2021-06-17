<?php

namespace DiDom;

use DiDom\Exceptions\InvalidSelectorException;
use DOMAttr;
use DOMCdataSection;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class Document
{
    /**
     * Types of a document.
     *
     * @const string
     */
    const TYPE_HTML = 'html';
    const TYPE_XML = 'xml';

    /**
     * @var DOMDocument
     */
    protected $document;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $encoding;

    /**
     * @param string|null $string An HTML or XML string or a file path
     * @param bool $isFile Indicates that the first parameter is a path to a file
     * @param string $encoding The document encoding
     * @param string $type The document type
     *
     * @throws InvalidArgumentException if parameter 3 is not a string
     */
    public function __construct($string = null, $isFile = false, $encoding = 'UTF-8', $type = Document::TYPE_HTML)
    {
        if ($string instanceof DOMDocument) {
            $this->document = $string;

            return;
        }

        if ( ! is_string($encoding)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 3 to be string, %s given', __METHOD__, gettype($encoding)));
        }

        $this->encoding = $encoding;

        $this->document = new DOMDocument('1.0', $encoding);

        $this->preserveWhiteSpace(false);

        if ($string !== null) {
            $this->load($string, $isFile, $type);
        }
    }

    /**
     * Creates a new document.
     *
     * @param string|null $string An HTML or XML string or a file path
     * @param bool $isFile Indicates that the first parameter is a path to a file
     * @param string $encoding The document encoding
     * @param string $type The document type
     *
     * @return Document
     */
    public static function create($string = null, $isFile = false, $encoding = 'UTF-8', $type = Document::TYPE_HTML)
    {
        return new Document($string, $isFile, $encoding, $type);
    }

    /**
     * Creates a new element node.
     *
     * @param string $name The tag name of the element
     * @param string|null $value The value of the element
     * @param array $attributes The attributes of the element
     *
     * @return Element created element
     */
    public function createElement($name, $value = null, array $attributes = [])
    {
        $node = $this->document->createElement($name);

        return new Element($node, $value, $attributes);
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
    public function createElementBySelector($selector, $value = null, array $attributes = [])
    {
        $segments = Query::getSegments($selector);

        $name = array_key_exists('tag', $segments) ? $segments['tag'] : 'div';

        if (array_key_exists('attributes', $segments)) {
            $attributes = array_merge($attributes, $segments['attributes']);
        }

        if (array_key_exists('id', $segments)) {
            $attributes['id'] = $segments['id'];
        }

        if (array_key_exists('classes', $segments)) {
            $attributes['class'] = implode(' ', $segments['classes']);
        }

        return $this->createElement($name, $value, $attributes);
    }

    /**
     * @param string $content
     *
     * @return Element
     */
    public function createTextNode($content)
    {
        return new Element(new DOMText($content));
    }

    /**
     * @param string $data
     *
     * @return Element
     */
    public function createComment($data)
    {
        return new Element(new DOMComment($data));
    }

    /**
     * @param string $data
     *
     * @return Element
     */
    public function createCdataSection($data)
    {
        return new Element(new DOMCdataSection($data));
    }

    /**
     * @return DocumentFragment
     */
    public function createDocumentFragment()
    {
        return new DocumentFragment($this->document->createDocumentFragment());
    }

    /**
     * Adds a new child at the end of the children.
     *
     * @param Element|DOMNode|array $nodes The appended child
     *
     * @return Element|Element[]
     *
     * @throws InvalidArgumentException if one of elements of parameter 1 is not an instance of DOMNode or Element
     */
    public function appendChild($nodes)
    {
        $returnArray = true;

        if ( ! is_array($nodes)) {
            $nodes = [$nodes];

            $returnArray = false;
        }

        $result = [];

        foreach ($nodes as $node) {
            if ($node instanceof Element) {
                $node = $node->getNode();
            }

            if ( ! $node instanceof DOMNode) {
                throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s\Element or DOMNode, %s given', __METHOD__, __NAMESPACE__, (is_object($node) ? get_class($node) : gettype($node))));
            }

            Errors::disable();

            $cloned = $node->cloneNode(true);
            $newNode = $this->document->importNode($cloned, true);

            $result[] = $this->document->appendChild($newNode);

            Errors::restore();
        }

        $result = array_map(function (DOMNode $node) {
            return new Element($node);
        }, $result);

        return $returnArray ? $result : $result[0];
    }

    /**
     * Set preserveWhiteSpace property.
     *
     * @param bool $value
     *
     * @return Document
     */
    public function preserveWhiteSpace($value = true)
    {
        if ( ! is_bool($value)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be boolean, %s given', __METHOD__, gettype($value)));
        }

        $this->document->preserveWhiteSpace = $value;

        return $this;
    }

    /**
     * Load HTML or XML.
     *
     * @param string $string An HTML or XML string or a file path
     * @param bool $isFile Indicates that the first parameter is a file path
     * @param string $type The type of a document
     * @param int|null $options libxml option constants
     *
     * @return Document
     *
     * @throws InvalidArgumentException if parameter 1 is not a string
     * @throws InvalidArgumentException if parameter 3 is not a string
     * @throws InvalidArgumentException if parameter 4 is not an integer or null
     * @throws RuntimeException if the document type is invalid (not Document::TYPE_HTML or Document::TYPE_XML)
     */
    public function load($string, $isFile = false, $type = Document::TYPE_HTML, $options = null)
    {
        if ( ! is_string($string)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, (is_object($string) ? get_class($string) : gettype($string))));
        }

        if ( ! is_string($type)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 3 to be string, %s given', __METHOD__, (is_object($type) ? get_class($type) : gettype($type))));
        }

        if ( ! in_array(strtolower($type), [Document::TYPE_HTML, Document::TYPE_XML], true)) {
            throw new RuntimeException(sprintf('Document type must be "xml" or "html", %s given', $type));
        }

        if ($options === null) {
            // LIBXML_HTML_NODEFDTD - prevents a default doctype being added when one is not found
            $options = LIBXML_HTML_NODEFDTD;
        }

        if ( ! is_int($options)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 4 to be integer, %s given', __METHOD__, (is_object($options) ? get_class($options) : gettype($options))));
        }

        $string = trim($string);

        if ($isFile) {
            $string = $this->loadFile($string);
        }

        if (strtolower($type) === Document::TYPE_HTML) {
            $string = Encoder::convertToHtmlEntities($string, $this->encoding);
        }

        $this->type = strtolower($type);

        Errors::disable();

        if ($this->type === Document::TYPE_HTML) {
            $this->document->loadHtml($string, $options);
        } else {
            $this->document->loadXml($string, $options);
        }

        Errors::restore();

        return $this;
    }

    /**
     * Load HTML from a string.
     *
     * @param string $html The HTML string
     * @param int|null $options Additional parameters
     *
     * @return Document
     *
     * @throws InvalidArgumentException if parameter 1 is not a string
     */
    public function loadHtml($html, $options = null)
    {
        return $this->load($html, false, Document::TYPE_HTML, $options);
    }

    /**
     * Load HTML from a file.
     *
     * @param string $filename The path to the HTML file
     * @param int|null $options Additional parameters
     *
     * @return Document
     *
     * @throws InvalidArgumentException if parameter 1 not a string
     * @throws RuntimeException if the file doesn't exist
     * @throws RuntimeException if you are unable to load the file
     */
    public function loadHtmlFile($filename, $options = null)
    {
        return $this->load($filename, true, Document::TYPE_HTML, $options);
    }

    /**
     * Load XML from a string.
     *
     * @param string $xml The XML string
     * @param int|null $options Additional parameters
     *
     * @return Document
     *
     * @throws InvalidArgumentException if parameter 1 is not a string
     */
    public function loadXml($xml, $options = null)
    {
        return $this->load($xml, false, Document::TYPE_XML, $options);
    }

    /**
     * Load XML from a file.
     *
     * @param string $filename The path to the XML file
     * @param int|null $options Additional parameters
     *
     * @return Document
     *
     * @throws InvalidArgumentException if the file path is not a string
     * @throws RuntimeException if the file doesn't exist
     * @throws RuntimeException if you are unable to load the file
     */
    public function loadXmlFile($filename, $options = null)
    {
        return $this->load($filename, true, Document::TYPE_XML, $options);
    }

    /**
     * Reads entire file into a string.
     *
     * @param string $filename The path to the file
     *
     * @return string
     *
     * @throws InvalidArgumentException if parameter 1 is not a string
     * @throws RuntimeException if an error occurred
     */
    protected function loadFile($filename)
    {
        if ( ! is_string($filename)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, gettype($filename)));
        }

        try {
            $content = file_get_contents($filename);
        } catch (Exception $exception) {
            throw new RuntimeException(sprintf('Could not load file %s', $filename));
        }

        if ($content === false) {
            throw new RuntimeException(sprintf('Could not load file %s', $filename));
        }

        return $content;
    }

    /**
     * Checks the existence of the node.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return bool
     */
    public function has($expression, $type = Query::TYPE_CSS)
    {
        $expression = Query::compile($expression, $type);
        $expression = sprintf('count(%s) > 0', $expression);

        return $this->createXpath()->evaluate($expression);
    }

    /**
     * Searches for an node in the DOM tree for a given XPath expression or a CSS selector.
     *
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool $wrapNode Returns array of Element if true, otherwise array of DOMElement
     * @param DOMElement|null $contextNode The node in which the search will be performed
     *
     * @return Element[]|DOMElement[]
     *
     * @throws InvalidSelectorException if the selector is invalid
     * @throws InvalidArgumentException if context node is not DOMElement
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapNode = true, $contextNode = null)
    {
        $expression = Query::compile($expression, $type);

        if ($contextNode !== null) {
            if ($contextNode instanceof Element) {
                $contextNode = $contextNode->getNode();
            }

            if ( ! $contextNode instanceof DOMElement) {
                throw new InvalidArgumentException(sprintf('Argument 4 passed to %s must be an instance of %s\Element or DOMElement, %s given', __METHOD__, __NAMESPACE__, (is_object($contextNode) ? get_class($contextNode) : gettype($contextNode))));
            }

            if ($type === Query::TYPE_CSS) {
                $expression = '.' . $expression;
            }
        }

        $nodeList = $this->createXpath()->query($expression, $contextNode);

        $result = [];

        if ($wrapNode) {
            foreach ($nodeList as $node) {
                $result[] = $this->wrapNode($node);
            }
        } else {
            foreach ($nodeList as $node) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * Searches for an node in the DOM tree and returns first element or null.
     *
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool $wrapNode Returns array of Element if true, otherwise array of DOMElement
     * @param DOMElement|null $contextNode The node in which the search will be performed
     *
     * @return Element|DOMElement|null
     *
     * @throws InvalidSelectorException if the selector is invalid
     */
    public function first($expression, $type = Query::TYPE_CSS, $wrapNode = true, $contextNode = null)
    {
        $expression = Query::compile($expression, $type);

        if ($contextNode !== null && $type === Query::TYPE_CSS) {
            $expression = '.' . $expression;
        }

        $expression = sprintf('(%s)[1]', $expression);

        $nodes = $this->find($expression, Query::TYPE_XPATH, false, $contextNode);

        if (count($nodes) === 0) {
            return null;
        }

        return $wrapNode ? $this->wrapNode($nodes[0]) : $nodes[0];
    }

    /**
     * @param DOMElement|DOMText|DOMAttr $node
     *
     * @return Element|string
     *
     * @throws InvalidArgumentException if parameter 1 is not an instance of DOMElement, DOMText, DOMComment, DOMCdataSection or DOMAttr
     */
    protected function wrapNode($node)
    {
        switch (get_class($node)) {
            case 'DOMElement':
            case 'DOMComment':
            case 'DOMCdataSection':
                return new Element($node);

            case 'DOMText':
                return $node->data;

            case 'DOMAttr':
                return $node->value;
        }

        throw new InvalidArgumentException(sprintf('Unknown node type "%s"', get_class($node)));
    }

    /**
     * Searches for a node in the DOM tree for a given XPath expression.
     *
     * @param string $expression XPath expression
     * @param bool $wrapNode Returns array of Element if true, otherwise array of DOMElement
     * @param DOMElement $contextNode The node in which the search will be performed
     *
     * @return Element[]|DOMElement[]
     */
    public function xpath($expression, $wrapNode = true, $contextNode = null)
    {
        return $this->find($expression, Query::TYPE_XPATH, $wrapNode, $contextNode);
    }

    /**
     * Counts nodes for a given XPath expression or a CSS selector.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return int
     *
     * @throws InvalidSelectorException
     */
    public function count($expression, $type = Query::TYPE_CSS)
    {
        $expression = Query::compile($expression, $type);
        $expression = sprintf('count(%s)', $expression);

        return (int) $this->createXpath()->evaluate($expression);
    }

    /**
     * @return DOMXPath
     */
    public function createXpath()
    {
        $xpath = new DOMXPath($this->document);

        $xpath->registerNamespace('php', 'http://php.net/xpath');
        $xpath->registerPhpFunctions();

        return $xpath;
    }

    /**
     * Dumps the internal document into a string using HTML formatting.
     *
     * @return string The document html
     */
    public function html()
    {
        return trim($this->document->saveHTML($this->document));
    }

    /**
     * Dumps the internal document into a string using XML formatting.
     *
     * @param int $options Additional options
     *
     * @return string The document xml
     */
    public function xml($options = 0)
    {
        return trim($this->document->saveXML($this->document, $options));
    }

    /**
     * Nicely formats output with indentation and extra space.
     *
     * @param bool $format Formats output if true
     *
     * @return Document
     */
    public function format($format = true)
    {
        if ( ! is_bool($format)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be boolean, %s given', __METHOD__, gettype($format)));
        }

        $this->document->formatOutput = $format;

        return $this;
    }

    /**
     * Get the text content of this node and its descendants.
     *
     * @return string
     */
    public function text()
    {
        return $this->getElement()->textContent;
    }

    /**
     * Indicates if two documents are the same document.
     *
     * @param Document|DOMDocument $document The compared document
     *
     * @return bool
     *
     * @throws InvalidArgumentException if parameter 1 is not an instance of DOMDocument or Document
     */
    public function is($document)
    {
        if ($document instanceof Document) {
            $element = $document->getElement();
        } else {
            if ( ! $document instanceof DOMDocument) {
                throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMDocument, %s given', __METHOD__, __CLASS__, (is_object($document) ? get_class($document) : gettype($document))));
            }

            $element = $document->documentElement;
        }

        if ($element === null) {
            return false;
        }

        return $this->getElement()->isSameNode($element);
    }

    /**
     * Returns the type of the document (XML or HTML).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the encoding of the document (XML or HTML).
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @return DOMDocument
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return DOMElement
     */
    public function getElement()
    {
        return $this->document->documentElement;
    }

    /**
     * @return Element
     */
    public function toElement()
    {
        if ($this->document->documentElement === null) {
            throw new RuntimeException('Cannot convert empty document to Element');
        }

        return new Element($this->document->documentElement);
    }

    /**
     * Convert the document to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->type === Document::TYPE_HTML ? $this->html() : $this->xml();
    }

    /**
     * Searches for an node in the DOM tree for a given XPath expression or a CSS selector.
     *
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool $wrapNode Returns array of Element if true, otherwise array of DOMElement
     * @param DOMElement|null $contextNode The node in which the search will be performed
     *
     * @return Element[]|DOMElement[]
     *
     * @throws InvalidSelectorException
     *
     * @deprecated Not longer recommended, use Document::find() instead.
     */
    public function __invoke($expression, $type = Query::TYPE_CSS, $wrapNode = true, $contextNode = null)
    {
        return $this->find($expression, $type, $wrapNode, $contextNode);
    }
}
