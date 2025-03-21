<?php
/* Copyright (C) 2004		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012-2018	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/api/admin/index.php
 *		\ingroup    api
 *		\brief      Page to setup Webservices REST module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

// Load translation files required by the page
$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Activate Production mode
if ($action == 'setproductionmode') {
	$status = GETPOST('status', 'alpha');

	if (dolibarr_set_const($db, 'API_PRODUCTION_MODE', $status, 'chaine', 0, '', 0) > 0) {
		$error = 0;

		if ($status == 1) {
			$result = dol_mkdir($conf->api->dir_temp);
			if ($result < 0) {
				setEventMessages($langs->trans("ErrorFailedToCreateDir", $conf->api->dir_temp), null, 'errors');
				$error++;
			}
		} else {
			// Delete the cache file otherwise it does not update
			$result = dol_delete_file($conf->api->dir_temp.'/routes.php');
			if ($result < 0) {
				setEventMessages($langs->trans("ErrorFailedToDeleteFile", $conf->api->dir_temp.'/routes.php'), null, 'errors');
				$error++;
			}
		}

		if (!$error) {
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
	} else {
		dol_print_error($db);
	}
}

// Disable compression mode
if ($action == 'setdisablecompression') {
	$status = GETPOST('status', 'alpha');

	if (dolibarr_set_const($db, 'API_DISABLE_COMPRESSION', $status, 'chaine', 0, '', 0) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if ($action == 'save') {
	dolibarr_set_const($db, 'API_RESTRICT_ON_IP', GETPOST('API_RESTRICT_ON_IP', 'alpha'));
}


dol_mkdir(DOL_DATA_ROOT.'/api/temp'); // May have been deleted by a purge


/*
 *	View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-api page-admin-index');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ApiSetup"), $linkback, 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("ApiDesc")."</span><br>\n";
print "<br>\n";

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print '<td>'.$langs->trans("Value")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ApiProductionMode").'</td>';
$production_mode = getDolGlobalBool('API_PRODUCTION_MODE');
if ($production_mode) {
	print '<td><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setproductionmode&token='.newToken().'&status=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setproductionmode&token='.newToken().'&status=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '<td>&nbsp;</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("API_DISABLE_COMPRESSION").'</td>';
$disable_compression = getDolGlobalBool('API_DISABLE_COMPRESSION');
if ($disable_compression) {
	print '<td><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdisablecompression&token='.newToken().'&status=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdisablecompression&token='.newToken().'&status=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '<td>&nbsp;</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("RESTRICT_ON_IP"), $langs->trans("Example").': '.$langs->trans("IPListExample"));
print '</td>';
print '<td><input type="text" name="API_RESTRICT_ON_IP" value="'.dol_escape_htmltag(getDolGlobalString('API_RESTRICT_ON_IP')).'"></td>';
print '<td>';
print '<input type="submit" class="button button-save smallpaddingimp" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'"></td>';
print '</td>';
print '</tr>';

print '</table>';
print '<br><br>';

print '</form>';


// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// Show message
$message = '';
//$url = $urlwithroot.'/api/index.php/login?login=<strong>auserlogin</strong>&password=<strong>thepassword</strong>[&reset=1]';
$url = $urlwithroot.'/api/index.php/login?login=auserlogin&password=thepassword[&reset=1]';
$message .= '<span class="opacitymedium">'.$langs->trans("UrlToGetKeyToUseAPIs").':</span><br>';
$message .= '<div class="urllink soixantepercent">'.img_picto('', 'globe').' <input type="text" class="quatrevingtpercent" id="urltogettoken" value="'.$url.'"></div>';
print $message;
print ajax_autoselect("urltogettoken");
print '<br>';
print '<br>';

// Explorer
print '<span class="opacitymedium">'.$langs->trans("ApiExporerIs").':</span><br>';
if (dol_is_dir(DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/explorer')) {
	$url = DOL_MAIN_URL_ROOT.'/api/index.php/explorer';
	print '<div class="urllink soixantepercent">'.img_picto('', 'globe').' <a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$url."</a></div><br>\n";

	print '<div class="opacitymediumxxx"><br><span class="opacitymedium">'.$langs->trans("SwaggerDescriptionFile").':</span><br>';
	$urlswagger = DOL_MAIN_URL_ROOT.'/api/index.php/explorer/swagger.json?DOLAPIKEY=youruserapikey';
	//$urlswaggerreal = DOL_MAIN_URL_ROOT.'/api/index.php/explorer/swagger.json?DOLAPIKEY='.$user->api_key;
	print '<div class="urllink soixantepercent">'.img_picto('', 'globe').' <input type="text" class="quatrevingtpercent" id="urltogetapidesc" value="'.$urlswagger.'"></div>';
	print '</div>';
	print ajax_autoselect("urltogetapidesc");
} else {
	$langs->load("errors");
	print info_admin($langs->trans("ErrorNotAvailableWithThisDistribution"), 0, 0, 'error');
}

llxFooter();
$db->close();
