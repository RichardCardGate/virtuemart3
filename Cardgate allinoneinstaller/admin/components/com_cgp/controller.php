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
 * @author      Paul Saparov, <support@cardgate.com>
 * @copyright   Copyright (c) 2012 Card Gate Plus B.V. - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.application.component.controller' );

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
            require(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
        }
       
        if ( !class_exists( 'vmPSPlugin' ) ) {
            require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
        }
        
        if ( !class_exists( 'VirtueMartCart' ) ) {
            require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
        }

        if ( !class_exists( 'VirtueMartModelOrders' ) ) {
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        }
       
        JPluginHelper::importPlugin( 'vmpayment' );
        
        $dispatcher = JEventDispatcher::getInstance();
        
        $returnValues = $dispatcher->trigger( 'plgVmOnCgpCallback', array( $_POST ) );

        return $_POST['transaction_id'] . "." . $_POST['status'];
    }

}
