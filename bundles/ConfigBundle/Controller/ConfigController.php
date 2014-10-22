<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\Form;

/**
 * Class ConfigController
 */
class ConfigController extends FormController
{

    /**
     * Controller action for editing the application configuration
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction()
    {
        // Set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array('config:config:full'), "RETURN_ARRAY");

        if (!$permissions['config:config:full']) {
            return $this->accessDenied();
        }

        $params = $this->getBundleParams();

        /* @type \Mautic\ConfigBundle\Model\ConfigModel $model */
        $model = $this->factory->getModel('config');

        // Create the form
        $action = $this->generateUrl('mautic_config_action', array('objectAction' => 'edit'));
        $form   = $model->createForm($params, $this->get('form.factory'), array('action' => $action));

        /// Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
                $configurator = $this->get('mautic.configurator');

                // Bind request to the form
                $post     = $this->request->request;
                $formData = $form->getData();

                foreach ($formData as $bundle => $bundleConfig) {
                    foreach ($bundleConfig as $key => $value) {
                        $formData[$bundle][$key] = $post->get('config[' . $key . ']', null, true);
                    }
                }

                foreach ($formData as $object) {
                    $configurator->mergeParameters($object);
                }

                try {
                    $configurator->write();

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.config.config.notice.updated', array(), 'flashes')
                    );

                    $this->clearCache();
                } catch (RuntimeException $exception) {
                    $this->request->getSession()->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('mautic.config.config.error.not.updated', array(), 'flashes')
                    );
                }

                $returnUrl = $this->generateUrl('mautic_config_action', array('objectAction' => 'edit'));
                $viewParams = array();
                $template = 'MauticConfigBundle:Config:form';
                $passthroughVars = array(
                    'activeLink'    => 'mautic_config_index',
                    'mauticContent' => 'config'
                );
            } else {
                $returnUrl = $this->generateUrl('mautic_dashboard_index');
                $viewParams = array();
                $template  = 'MauticDashboardBundle:Default:index';
                $passthroughVars = array(
                    'activeLink'    => 'mautic_dashboard_index',
                    'mauticContent' => 'dashboard'
                );
            }

            if ($cancelled || $form->get('buttons')->get('save')->isClicked()) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParams,
                    'contentTemplate' => $template,
                    'passthroughVars' => $passthroughVars
                ));
            }
        }

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'params'      => $params,
                'permissions' => $permissions,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity(),
                'form'        => $this->setFormTheme($form, 'MauticConfigBundle:Config:form.html.php', 'MauticConfigBundle:Config')
            ),
            'contentTemplate' => 'MauticConfigBundle:Config:form.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_config_index',
                'mauticContent'  => 'config',
                'route'          => $this->generateUrl('mautic_config_action', array('objectAction' => 'edit')),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    /**
     * Retrieves the parameters defined in each bundle and merges with the local params
     *
     * @return array
     */
    private function getBundleParams()
    {
        require $this->container->getParameter('kernel.root_dir') . '/config/local.php';
        $localParams = $parameters;

        $params = array();
        $mauticBundles = $this->factory->getParameter('bundles');

        foreach ($mauticBundles as $bundle) {
            // Build the path to the bundle configuration
            $paramsFile = $bundle['directory'] . '/Config/parameters.php';

            if (file_exists($paramsFile)) {
                require_once $paramsFile;
                foreach ($parameters as $key => $value) {
                    if (array_key_exists($key, $localParams)) {
                        $parameters[$key] = $localParams[$key];
                    }
                }
                $params[$bundle['bundle']] = $parameters;
            }
        }

        return $params;
    }
}
