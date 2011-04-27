<?php
/**
 * @package Newscoop
 *
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */

/**
 * NewsML Package Reader class
 */
class NewsML_PackageItemReader implements IFeedReader
{
    /** @var SimpleXMLElement */
    private $root;

    public function __construct(SimpleXMLElement $root)
    {
        $this->root = $root;
    }

    public function count()
    {
        return count($this->root->packageItem);
    }

    public function item($index)
    {
        $node = $this->root->itemSet->newsItem[$index];
        if (!$node) {
            return NULL;
        }

        return (object)array(
            'guid' => (string) $node->attributes()->guid,
        );
    }

    public static function canRead(SimpleXMLElement $root)
    {
        return $root->getName() == 'packageItem';
    }
}
