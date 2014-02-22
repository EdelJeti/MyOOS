<?php
/* ----------------------------------------------------------------------
   $Id: down_for_maintenance.php 409 2013-06-11 15:53:40Z r23 $

   MyOOS [Shopsystem]
   http://www.oos-shop.de/

   Copyright (c) 2003 - 2014 by the MyOOS Development Team.
   ----------------------------------------------------------------------
   Based on:

   WebMakers.com Added: Down for Maintenance No Store
   Written by Linda McGrath osCOMMERCE@WebMakers.com
   http://www.thewebmakerscorner.com
   ----------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------- */

  /** ensure this file is being included by a parent file */
  defined( 'OOS_VALID_MOD' ) OR die( 'Direct Access to this location is not allowed.' );

  if (!$oEvent->installed_plugin('down_for_maintenance')) {
    oos_redirect(oos_href_link($aContents['main']));
  }

  
  require_once MYOOS_INCLUDE_PATH . '/includes/languages/' . $sLanguage . '/info_down_for_maintenance.php';

  $aTemplate['page'] = $sTheme . '/system/info.tpl';

  $nPageType = OOS_PAGE_TYPE_MAINPAGE;
  $contents_cache_id = $sTheme . '|down_for_maintenance|' . $sLanguage;

  require_once MYOOS_INCLUDE_PATH . '/includes/oos_system.php';
  if (!isset($option)) {
    require_once MYOOS_INCLUDE_PATH . '/includes/info_message.php';
    require_once MYOOS_INCLUDE_PATH . '/includes/oos_blocks.php';
  }

  if ( (USE_CACHE == 'true') && (!isset($_SESSION)) ) {
    $smarty->setCaching(Smarty::CACHING_LIFETIME_CURRENT);
    $smarty->setCacheLifetime (3600);
  }

  if (!$smarty->isCached($aTemplate['page'], $contents_cache_id)) {

    // links breadcrumb
    $oBreadcrumb->add($aLang['navbar_title']);
    $sCanonical = oos_href_link($aContents['info_down_for_maintenance'], '', 'NONSSL', FALSE, TRUE);
    $sPagetitle = $aLang['heading_title'];
    
    // assign Smarty variables;
    $smarty->assign(
        array(
            'breadcrumb'    => $oBreadcrumb->trail(BREADCRUMB_SEPARATOR),
            'heading_title' => $aLang['heading_title'],
            'pagetitle'         => htmlspecialchars($sPagetitle),
            'canonical'         => $sCanonical
        )
    );
  }

// display the template
$smarty->display($aTemplate['page'], $contents_cache_id);

