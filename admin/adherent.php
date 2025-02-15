<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      J. Fernando Lagrange <fernando@demo-tic.org>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2017      Ari Elbaz (elarifr)	<github@accedinfo.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/adherents/admin/adherent.php
 *		\ingroup    adherentsplus
 *		\brief      Page to setup the module Foundation
 */

// require '../../main.inc.php';
// Dolibarr environment
$res = 0;
if (! $res && file_exists("../main.inc.php"))
{
	$res = @include "../main.inc.php";
}
if (! $res && file_exists("../../main.inc.php"))
{
	$res = @include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php"))
{
	$res = @include "../../../main.inc.php";
}
if (! $res)
{
	die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/adherentsplus/lib/member.lib.php');
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->loadLangs(array('admin', 'members', 'adherentsplus@adherentsplus'));

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');


/*
 * Actions
 */

//
if ($action == 'updateall')
{

    $db->begin();
    $res1=$res2=$res3=$res4=$res5=$res6=$res7=$res8=$res9=0;
    $res2=dolibarr_set_const($db, 'ADHERENT_LINKEDMEMBER', GETPOST('ADHERENT_LINKEDMEMBER', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res3=dolibarr_set_const($db, 'ADHERENT_SUBSCRIPTION_PRORATA', GETPOST('ADHERENT_SUBSCRIPTION_PRORATA', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res4=dolibarr_set_const($db, 'SOCIETE_SUBSCRIBE_MONTH_START', GETPOST('SOCIETE_SUBSCRIBE_MONTH_START', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res5=dolibarr_set_const($db, 'SOCIETE_SUBSCRIBE_MONTH_PRESTART', GETPOST('SOCIETE_SUBSCRIBE_MONTH_PRESTART', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res6=dolibarr_set_const($db, 'ADHERENT_WELCOME_MONTH', GETPOST('ADHERENT_WELCOME_MONTH', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res7=dolibarr_set_const($db, 'ADHERENT_MEMBER_CATEGORY', implode(",", GETPOST('ADHERENT_MEMBER_CATEGORY', 'array')), 'chaine', 0, '', $conf->entity);
    $res8=dolibarr_set_const($db, 'ADHERENT_CONSUMPTION', GETPOST('ADHERENT_CONSUMPTION', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res9=dolibarr_set_const($db, 'ADHERENT_FEDERAL_PART', GETPOST('ADHERENT_FEDERAL_PART', 'alpha'), 'chaine', 0, '', $conf->entity);
    if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0 || $res7 < 0 || $res8 < 0 || $res9 < 0)
    {
        setEventMessages('ErrorFailedToSaveDate', null, 'errors');
        $db->rollback();
    }
    else
    {
        setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
        $db->commit();
    }
}

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));

	if (($constname=='ADHERENT_CARD_TYPE' || $constname=='ADHERENT_ETIQUETTE_TYPE' || $constname=='ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && $constvalue == -1) $constvalue='';
	if ($constname=='ADHERENT_LOGIN_NOT_REQUIRED') // Invert choice
	{
		if ($constvalue) $constvalue=0;
		else $constvalue=1;
	}

	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote');
	$res=dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
    $result=dolibarr_set_const($db, GETPOST('name','alpha'),GETPOST('value'),'',0,'',$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
    $result=dolibarr_del_const($db,GETPOST('name','alpha'),$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}



/*
 * View
 */

$form = new Form($db);
$formother=new FormOther($db);

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('',$langs->trans("MembersSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MembersPlusSetup"),$linkback,'title_setup');


$head = member_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Members"), -1, 'user');  	

$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
print '<br><a href="'.$urlwithroot.'/adherents/admin/member.php">'.$langs->trans("AccessToMembersSetup").'</a>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="updateall">';

print load_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// multimembers for one thirdparty
print '<tr class="oddeven"><td>'.$langs->trans("AdherentLinkedMember").'</td><td>';
print $form->selectyesno('ADHERENT_LINKEDMEMBER',(! empty($conf->global->ADHERENT_LINKEDMEMBER)?$conf->global->ADHERENT_LINKEDMEMBER:0),1);
print "</td></tr>\n";

// Login/Pass required for members
print '<tr class="oddeven"><td>'.$langs->trans("FederalPart").'</td><td>';
print $form->select_company($conf->global->ADHERENT_FEDERAL_PART, 'ADHERENT_FEDERAL_PART', 's.fournisseur=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
print "</td></tr>\n";

// type of adhesion flow
print '<tr class="oddeven"><td>'.$langs->trans("BeginningFixedDate").'</td>';
print '<td>';
print $form->selectarray('ADHERENT_SUBSCRIPTION_PRORATA', array('0'=>$langs->trans("ADHERENT_PRORATA_SUBSCRIPTION_FREE"),'1'=>$langs->trans("ADHERENT_PRORATA_SUBSCRIPTION_BLOCK"),'2'=>$langs->trans("ADHERENT_PRORATA_SUBSCRIPTION_TYPE")), (empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA)?'0':$conf->global->ADHERENT_SUBSCRIPTION_PRORATA), 0);
print '</td>';
print "</tr>\n";

// Insert subscription into bank account
print '<tr class="oddeven"><td>'.$langs->trans("FiscalMonthStart").'</td>';
print '<td>';
print $formother->select_month($conf->global->SOCIETE_SUBSCRIBE_MONTH_START, 'SOCIETE_SUBSCRIBE_MONTH_START', 0, 1, 'maxwidth100');
print '</td>';
print "</tr>\n";

// presale for next membership
print '<tr class="oddeven"><td>'.$langs->trans("SOCIETE_SUBSCRIBE_MONTH_PRESTART").'</td>';
print '<td>';
print $form->selectarray('SOCIETE_SUBSCRIBE_MONTH_PRESTART', array('0'=>'0','1'=>'1','2'=>'2','3'=>'3','4'=>'4'), (empty($conf->global->SOCIETE_SUBSCRIBE_MONTH_PRESTART)?'0':$conf->global->SOCIETE_SUBSCRIBE_MONTH_PRESTART), 0);
print $langs->trans("monthbefore").'</td>';
print "</tr>\n";

// time before renewing welcome fee
print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_WELCOME_MONTH").'</td>';
print '<td>';
print $form->selectarray('ADHERENT_WELCOME_MONTH', array('-1'=>'Uniquement la première fois','0'=>'Exigés immédiatement après la fin d\'adhésion','1'=>'Exigés 1 mois après la fin d\'adhésion','2'=>'Exigés 2 mois après la fin d\'adhésion','3'=>'Exigés 3 mois après la fin d\'adhésion','4'=>'Exigés 4 mois après la fin d\'adhésion','5'=>'Exigés 5 mois après la fin d\'adhésion','6'=>'Exigés 6 mois après la fin d\'adhésion','12'=>'Exigés 12 mois après la fin d\'adhésion','18'=>'Exigés 18 mois après la fin d\'adhésion','24'=>'Exigés 24 mois après la fin d\'adhésion','36'=>'Exigés 36 mois après la fin d\'adhésion'), (empty($conf->global->ADHERENT_WELCOME_MONTH)?'0':$conf->global->ADHERENT_WELCOME_MONTH), 0);
print '</td>';
print "</tr>\n";

				// Customer
				if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire)) {
					print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_MEMBER_CATEGORY").'</td>';
					print '<td>';
					$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, null, null, null, 1);
					$c = new Categorie($db);
					$cats = $c->containing($conf->global->ADHERENT_MEMBER_CATEGORY, Categorie::TYPE_PRODUCT);
					foreach ($cats as $cat) {
						$arrayselected[] = $cat->id;
          //print $cat->id;
					}
					print $form->multiselectarray('ADHERENT_MEMBER_CATEGORY', $cate_arbo, array($conf->global->ADHERENT_MEMBER_CATEGORY), '', 0, '', 0, '90%');
					print "</td></tr>";
				}
        
// Consumption for members
print '<tr class="oddeven"><td>'.$langs->trans("AdherentConsumption").'</td><td>';
print $form->selectyesno('ADHERENT_CONSUMPTION', (! empty($conf->global->ADHERENT_CONSUMPTION)?$conf->global->ADHERENT_CONSUMPTION:0), 1);
print "</td></tr>\n";        

print '</table>';

print '<center>';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</center>';

print '</form>';

print '<br>';

dol_fiche_end();


llxFooter();

$db->close();
