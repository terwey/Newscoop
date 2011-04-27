<?php
/**
 * @package Newscoop
 *
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */

namespace NewsML;

include('IFeedReader.php');

/**
 * NewsML NewsMessage Reader class
 */
class NewsML_NewsMessageReader implements IFeedReader
{
    /** @var SimpleXMLElement */
    private $root;

    public function __construct(\SimpleXMLElement $root)
    {
        $this->root = $root;
    }

    public function count()
    {
        return count($this->root->packageItem->itemRef);
    }

    public function item($index)
    {
        $node = $this->root;
        if (!$node) {
            return NULL;
        }
        // var_dump($node); exit;
        return $node;

        //return (object)array(
        //    'guid' => (string) $node->attributes()->guid,
        //);
    }

    public function getHeader()
    {
        return $this->root->header;
    }

    public static function canRead(\SimpleXMLElement $root)
    {
        return $root->getName() == 'newsMessage';
    }

}
