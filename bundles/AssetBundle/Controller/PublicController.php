<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller;

use Mautic\AssetBundle\Event\AssetEvent;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\AssetBundle\AssetEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PublicController extends CommonFormController
{
    public function downloadAction($slug)
    {
        //find the asset
        $security   = $this->factory->getSecurity();
        $model      = $this->factory->getModel('asset.asset');
        $translator = $this->get('translator');
        $entity     = $model->getEntityBySlugs($slug);

        if (!empty($entity)) {
            $category     = $entity->getCategory();
            $catPublished = (!empty($category)) ? $category->isPublished() : true;
            $published    = $entity->isPublished();

            //make sure the asset is published or deny access if not
            if ((!$catPublished || !$published) && (!$security->hasEntityAccess(
                    'asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy()))
            ) {
                $model->trackDownload($entity, $this->request, 401);
                throw new AccessDeniedHttpException($translator->trans('mautic.core.url.error.401'));
            }

            //make sure URLs match up
            $url        = $model->generateUrl($entity, false);
            $requestUri = $this->request->getRequestUri();
            //remove query
            $query      = $this->request->getQueryString();

            if (!empty($query)) {
                $requestUri = str_replace("?{$query}", '', $url);
            }

            //redirect if they don't match
            if ($requestUri != $url) {
                $model->trackDownload($entity, $this->request, 301);
                return $this->redirect($url, 301);
            }

            $userAccess = $security->hasEntityAccess('asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy());

            //all the checks pass so provide the asset for download

            $dispatcher = $this->get('event_dispatcher');

            if ($dispatcher->hasListeners(AssetEvents::ASSET_ON_DOWNLOAD)) {
                $event = new AssetEvent($entity);
                $dispatcher->dispatch(AssetEvents::ASSET_ON_DOWNLOAD, $event);
            }

            $model->trackDownload($entity, $this->request, 200);

            $response = new Response();
            $response->headers->set('Content-Type', $entity->getFileMimeType());
            $response->headers->set('Content-Disposition', 'attachment;filename="'.$entity->getOriginalFileName());
            $response->setContent($entity->getFileContents());

            return $response;

        }
        $model->trackDownload($entity, $this->request, 404);
        throw $this->createNotFoundException($translator->trans('mautic.core.url.error.404'));
    }
}