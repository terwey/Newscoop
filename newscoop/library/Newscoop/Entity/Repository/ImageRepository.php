<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Newscoop\Image\LocalImage;
use Doctrine\ORM\Query;

/**
 * Image Repository
 */
class ImageRepository extends EntityRepository
{
    /**
     * Get images for storage update
     *
     * @param int $maxResults
     * @return array
     */
    public function findImagesForStorageUpdate($maxResults)
    {
        $query = $this->createQueryBuilder('i')
            ->andWhere('i.isUpdatedStorage = 0')
            ->andWhere('i.location = :local')
            ->setMaxResults($maxResults)
            ->getQuery();

        $query->setParameter('local', LocalImage::LOCATION_LOCAL);

        return $query->getResult();
    }

    /**
     * Get all images
     *
     * @return Query
     */
    public function getImages()
    {
        $query = $this->createQueryBuilder('i')
            ->getQuery();

        return $query;
    }

    /**
     * Get single image by id
     *
     * @param int $number
     *
     * @return Query
     */
    public function getImage($number)
    {
        $query = $this->createQueryBuilder('i')
            ->andWhere('i.id = :number')
            ->setParameter('number', $number)
            ->getQuery();

        return $query;
    }

    /**
     * Get count of references for given file
     *
     * @param string $file
     *
     * @return int
     */
    public function getImageFileReferencesCount($file)
    {
        $query = $this->createQueryBuilder('i')
            ->select('COUNT(i)')
            ->where('i.basename = :file')
            ->getQuery();

        $query->setParameter('file', $file);

        return $query->getSingleScalarResult();
    }

    /**
     * Get images attached to article
     *
     * @param int $number Article number
     *
     * @return Query
     */
    public function getArticleImages($number)
    {
        $query = $this->createQueryBuilder('ai')
            ->andWhere('ai.articleNumber = :number')
            ->setParameter('number', $number)
            ->getQuery();

        return $query;
    }

    /**
     * Get single ArticleImage
     *
     * @param int        $articleNumber Article number
     * @param LocalImage $image         Image object
     *
     * @return Query
     */
    public function getArticleImage($articleNumber, LocalImage $image)
    {
        $query = $this->createQueryBuilder('ai')
            ->andWhere('ai.articleNumber = :number')
            ->andWhere('ai.image = :image')
            ->setParameters(array(
                'number' => $articleNumber,
                'image' => $image
            ))
            ->getQuery();

        return $query;
    }

    /**
     * Get ArticleImages for specific image
     *
     * @param LocalImage $image
     *
     * @return Query
     */
    public function getArticleImagesForImage(LocalImage $image)
    {
        $query = $this->createQueryBuilder('ai')
            ->andWhere('ai.image = :image')
            ->setParameters(array(
                'image' => $image
            ))
            ->getQuery();

        return $query;
    }
}
