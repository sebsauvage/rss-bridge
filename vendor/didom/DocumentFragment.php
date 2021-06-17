<?php

namespace DiDom;

use DOMDocumentFragment;
use InvalidArgumentException;

/**
 * @property string $tag
 */
class DocumentFragment extends Node
{
    /**
     * @param DOMDocumentFragment $documentFragment
     */
    public function __construct($documentFragment)
    {
        if ( ! $documentFragment instanceof DOMDocumentFragment) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of DOMDocumentFragment, %s given', __METHOD__, (is_object($documentFragment) ? get_class($documentFragment) : gettype($documentFragment))));
        }

        $this->setNode($documentFragment);
    }

    /**
     * Append raw XML data.
     *
     * @param string $data
     */
    public function appendXml($data)
    {
        $this->node->appendXML($data);
    }
}
