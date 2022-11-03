<?php

/**
 * Virtuemart Card Gate Plus payment extension
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      CardGate B.V., <info@cardgate.com>
 * @copyright   Copyright (c) 2022 CardGate B.V. - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.application.component.controller' );
JLoader::register('vmDefines', JPATH_ROOT.'/administrator/components/com_virtuemart/helpers/vmdefines.php');
vmDefines::defines(0);
/**
 * Cgp Component Controller
 *
 * @package    Joomla.JController
 * @subpackage Components
 */
class CgpController extends JControllerLegacy {

    public function callback() {
        // Process callback

        $response = $this->_process_callback();

        if ( version_compare( JVERSION, '1.6.0', 'lt' ) ) {
            $defaultView = 'cgp';
            $basePath = JPATH_ROOT . '/components/com_cgp';
        } else {
            $defaultView = $this->default_view;
            $basePath = $this->basePath;
        }
        $document = JFactory::getDocument();
        $viewType = $document->getType();
        $viewName = JRequest::getCmd( 'view', $defaultView );
        $viewLayout = JRequest::getCmd( 'layout', 'default' );
        $view = $this->getView( $viewName, $viewType, '', array( 'base_path' => $basePath ) );
        // Set the layout
        $view->setLayout( $viewLayout );
        // Assign vars
        $view->assignRef( "document", $document );
        $view->assignRef( "response", $response );
        // Display the view
        $view->display();

        return $this;
    }

    protected function _process_callback() {


        defined( 'DS' ) or define( 'DS', DIRECTORY_SEPARATOR );

        if ( !class_exists( 'VmConfig' ) ) {
            require(VMPATH_ADMIN .DS. 'helpers' . DS . 'config.php');
        }

        if ( !class_exists( 'vmPSPlugin' ) ) {
            require(VMPATH_ADMIN .DS.'plugins'.DS.'vmpsplugin.php');
        }

        if ( !class_exists( 'VirtueMartCart' ) ) {
            require(VMPATH_SITE . DS . 'helpers' . DS . 'cart.php');
        }

        if ( !class_exists( 'VirtueMartModelOrders' ) ) {
            require( VMPATH_SITE. DS . 'models' . DS . 'orders.php' );
        }

        JPluginHelper::importPlugin( 'vmpayment' );
        $dispatcher = JEventDispatcher::getInstance();
        $return = $dispatcher->trigger( 'plgVmOnCgpCallback', array( $_GET ) );
        return $return[0];
    }

}
