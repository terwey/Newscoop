<?php
/**
 * @package Newscoop\Gimme
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\GimmeBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Newscoop\Entity\Article;
use Newscoop\NewscoopException;
use Newscoop\GimmeBundle\Form\Type\ArticleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Articles controller
 */
class ArticlesController extends FOSRestController
{
    /**
     * Get Articles
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when articles found",
     *         404={
     *           "Returned when articles are not found",
     *         }
     *     }
     * )
     *
     * @Route("/articles.{_format}", defaults={"_format"="json"})
     * @Method("GET")
     * @View(serializerGroups={"list"})
     *
     * @return array
     */
    public function getArticlesAction(Request $request)
    {
        $em = $this->container->get('em');
        $publication = $this->get('newscoop_newscoop.publication_service')->getPublication()->getId();

        $articles = $em->getRepository('Newscoop\Entity\Article')
            ->getArticles($publication, $request->get('type', null), $request->get('language', null));

        $paginator = $this->get('newscoop.paginator.paginator_service');
        $articles = $paginator->paginate($articles, array(
            'distinct' => false
        ));

        return $articles;
    }

    /**
     *
     * Get article
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         404={
     *           "Returned when the article is not found",
     *         }
     *     },
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Article number"}
     *     },
     *     filters={
     *          {"name"="language", "dataType"="string", "description"="Language code"}
     *     },
     *     output="\Newscoop\Entity\Article"
     * )
     *
     * @Route("/articles/{number}.{_format}", defaults={"_format"="json"})
     * @Method("GET")
     * @View(serializerGroups={"details"})
     *
     * @return Form
     */
    public function getArticleAction(Request $request, $number)
    {
        $em = $this->container->get('em');
        $publication = $this->get('newscoop_newscoop.publication_service')->getPublication();

        $article = $em->getRepository('Newscoop\Entity\Article')
            ->getArticle($number, $request->get('language', $publication->getLanguage()->getCode()))
            ->getOneOrNullResult();

        return $article;
    }

    /**
     * Link resource with Article entity
     *
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when successful",
     *         404="Returned when resource not found",
     *         409={
     *           "Returned when the link already exists",
     *         }
     *     },
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Article number"}
     *     }
     * )
     *
     * @Route("/articles/{number}.{_format}", defaults={"_format"="json"})
     * @Method("LINK")
     * @View(statusCode=201)
     *
     * @return Form
     */
    public function linkArticleAction(Request $request, $number)
    {
        $em = $this->container->get('em');
        $publication = $this->get('newscoop.publication_service')->getPublication();

        $article = $em->getRepository('Newscoop\Entity\Article')
            ->getArticle($number, $request->get('language', $publication->getLanguage()->getCode()))
            ->getOneOrNullResult();

        if (!$article) {
            throw NotFoundHttpException('Article was not found');
        }

        foreach ($request->attributes->get('links') as $key => $object) {
            if ($object instanceof \Exception) {
                throw $object;
            }

            if ($object instanceof \Newscoop\Image\LocalImage) {
                $imagesService = $this->get('image');
                $imagesService->addArticleImage($article->getNumber(), $object);

                continue;
            }

            if ($object instanceof \Newscoop\Entity\Attachment) {
                $attachmentService = $this->get('attachment');
                $attachmentService->addAttachmentToArticle($article, $object);

                continue;
            }
        }
    }

    /**
     * Unlink resource from Article
     *
     * @ApiDoc(
     *     statusCodes={
     *         204="Returned when successful",
     *         404="Returned when resource not found"
     *     },
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Article number"}
     *     }
     * )
     *
     * @Route("/articles/{number}.{_format}", defaults={"_format"="json"})
     * @Method("UNLINK")
     * @View(statusCode=204)
     *
     * @return Form
     */
    public function unlinkArticleAction(Request $request, $number)
    {
        $em = $this->container->get('em');
        $publication = $this->get('newscoop.publication_service')->getPublication();

        $article = $em->getRepository('Newscoop\Entity\Article')
            ->getArticle($number, $request->get('language', $publication->getLanguage()->getCode()))
            ->getOneOrNullResult();

        if (!$article) {
            throw NotFoundHttpException('Article was not found');
        }

        foreach ($request->attributes->get('links') as $key => $object) {
            if ($object instanceof \Exception) {
                throw $object;
            }

            if ($object instanceof \Newscoop\Image\LocalImage) {
                $imagesService = $this->get('image');
                $articleImage = $em->getRepository('Newscoop\Image\ArticleImage')
                    ->getArticleImage($article->getNumber(), $object)
                    ->getOneOrNullResult();

                if ($articleImage) {
                    $imagesService->removeArticleImage($articleImage);
                }

                continue;
            }

            if ($object instanceof \Newscoop\Entity\Attachment) {
                $attachmentService = $this->get('attachment');
                $attachmentService->removeAttachmentFormArticle($article, $object);

                continue;
            }
        }
    }

    /**
     * @Route("/articles/{number}/{language}.{_format}", defaults={"_format"="json"})
     * @Method("PATCH")
     * @View()
     *
     * @return Form
     */
    public function setArticleAction(Request $request, $number, $language)
    {
        return $this->processForm($request, $number, $language);
    }

    private function processForm($request, $number, $language)
    {
        $em = $this->container->get('em');
        $publication = $this->get('newscoop.publication_service')->getPublication();
        $article = null;
        $articles = $em->getRepository('Newscoop\Entity\Article')
            ->getArticle($number, $language)
            ->getResult();

        if (count($articles) > 0) {
            $article = $articles[0];
        }

        $statusCode = $article ? 200 : 201;

        $form = $this->createForm(new ArticleType(), $article);
        $form->bind($request);

        if ($form->isValid()) {
            if ($statusCode == 201) {
                $em->persist($article);
            }
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            return $response;
        }

        return $form;
    }
}
