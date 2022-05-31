<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
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
 *      \file       htdocs/adherents/linkedmember.php
 *      \ingroup    member
 *      \brief      Tab for note of a member
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

dol_include_once('/adherentsplus/lib/member.lib.php');
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->loadLangs(array('products', 'companies', 'members', 'bills', 'other', 'adherentsplus@adherentsplus'));

$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');
$backtopage=GETPOST('backtopage','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('rowid','int');
$link=GETPOST('link','int');

// Security check
$result=restrictedArea($user,'adherent',$id);

$object = new Adherentplus($db);
$result=$object->fetch($id);
if ($result > 0)
{
    $adht = new AdherentTypePlus($db);
    $result=$adht->fetch($object->typeid);
}

/*
 *	Actions
 */

if ($cancel)
{
	$action='';
}

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$permissionnote=$user->rights->adherent->creer;  // Used by the include of actions_setnotes.inc.php


  if ($action == 'confirm_deletelinkedmember' && $confirm == 'yes' && $user->rights->adherent->creer)
	{
 		$result=$object->unlinkMember($link);
		if ($result > 0)
		{

				header("Location: ".$dolibarr_main_url_root.dol_buildpath('/adherentsplus/linkedmember.php?rowid='.$id, 1));
				exit;
		}
		else
		{
			$errmesg=$object->error;
		}
  }
  
    if ($action == 'confirm_addlinkedmember' && $confirm == 'yes' && $user->rights->adherent->creer)
	{
 		$result=$object->linkMember($link);
		if ($result > 0)
		{

				header("Location: ".$dolibarr_main_url_root.dol_buildpath('/adherentsplus/linkedmember.php?rowid='.$id, 1));
				exit;
		}
		else
		{
			$errmesg=$object->error;
		}
  }
  
  	if ($action == 'add' && $user->rights->societe->creer)
	{
		$error=0;

		if (! GETPOST('memberid', 'alpha'))
		{
			if (! GETPOST('memberid', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Member")), null, 'errors');
			//if (! GETPOST('qty', 'int')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
			$action='create';
			$error++;
		}

		if (! $error)
		{
		$db->begin();

		// Insert member
		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
    $sql.= " SET fk_parent = '".$id."'";
    $sql.= " WHERE rowid = '".GETPOST('memberid', 'alpha')."'";

		//dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $db->query($sql);

			if (! $error)
			{
				//$result = $companypaymentmode->create($user);
				if ($result < 0)
				{
					$error++;
					//setEventMessages($companypaymentmode->error, $companypaymentmode->errors, 'errors');
					$action='create';     // Force chargement page cr�ation
				}
			}

			if (! $error)
			{
				$db->commit();

				$url=$_SERVER["PHP_SELF"].'?rowid='.$object->id;
				header('Location: '.$url);
				exit;
			}
			else
			{
				$db->rollback();
			}
		}
	}

/*
 * View
 */
$title=$langs->trans("Member") . " - " . $langs->trans("LinkedMembers");
$helpurl="EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
llxHeader("",$title,$helpurl);

if ($id && $action == 'create' && $user->rights->societe->creer)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforadd='add';
	print '<input type="hidden" name="action" value="'.$actionforadd.'">';
}

$form = new Form($db);

// Create Card
if ($id && $action == 'create' && $user->rights->adherent->creer)
{

	$head = memberplus_prepare_head($object);

	dol_fiche_head($head, 'linkedmember', $langs->trans("Member"), -1, 'user');
 	
  print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

  $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

  dol_banner_tab($object, 'rowid', $linkback);

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Member").'</td>';
	print '<td>';
print '<SELECT name="memberid">';  
        
        $sql = "SELECT c.rowid, c.firstname, c.lastname, c.societe";               
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as c";
        $sql.= " WHERE c.entity IN (" . getEntity('adherentsplus') . ") AND c.rowid!=".$object->id." AND ISNULL(c.fk_parent)";
        $sql.= " ORDER BY c.firstname, c.lastname ASC";
        //$sql.= " LIMIT 0,5";
        
        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            $var=True;
            //print '<OPTION value="" disabled selected>'.$langs->trans('Members').'</OPTION>';  
            while ($i < $num)
            {            
                $objp = $db->fetch_object($result);
                $var=!$var;               
             
                print '<OPTION value="'.$objp->rowid.'">'.$objp->firstname.' '.$objp->lastname.' '.$objp->societe.'</OPTION>';   
                            
                $i++;
            }
        }
        else
        {
            dol_print_error($db);
        }

print '</SELECT>';
  print '</td></tr>';

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
		if ($user->rights->adherent->creer)
		{
			print $form->selectarray("typeid", $adht->liste_array(), (isset($_POST["typeid"])?$_POST["typeid"]:$object->typeid));
		}
		else
		{
			print $adht->getNomUrl(1);
			print '<input type="hidden" name="typeid" value="'.$object->typeid.'">';
		}
		print "</td></tr>";

	print '</table>';

	print '</div>';

	dol_fiche_end();

	dol_set_focus('#label');

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Add").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';

} elseif ($id) {

	$head = memberplus_prepare_head($object);

	dol_fiche_head($head, 'linkedmember', $langs->trans("Member"), -1, 'user');

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">";
	print '<input type="hidden" name="token" value="'.newToken().'">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'rowid', $linkback);
    
    print '<div class="fichecenter">';
    
  print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

    // Login
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
    {
        print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';
    }

		// Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans("LinkedToDolibarrThirdParty");
			print '</td>';
			if ($action != 'editthirdparty' && $user->rights->adherent->creer) print '<td align="right"></td>';
			print '</tr></table>';
			print '</td><td colspan="2" class="valeur">';
				if ($object->fk_soc)
				{
					$company=new Societe($db);
					$result=$company->fetch($object->fk_soc);
					print $company->getNomUrl(1);
				}
				else
				{
					print $langs->trans("NoThirdPartyAssociatedToMember");
				}
			print '</td></tr>';
		}

    // Type
    print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1).'</td></tr>';

    // Company
    print '<tr><td>'.$langs->trans("SubscriptionEndDate").'</td><td class="valeur">';
    if ($object->datefin)
		{
			print dol_print_date($object->datefin,'day');
			if ($object->hasDelay()) {
				print " ".img_warning($langs->trans("Late"));
			}
		}
		else
		{
			if (! $adht->subscription)
			{
				print $langs->trans("SubscriptionNotRecorded");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
			else
			{
				print $langs->trans("SubscriptionNotReceived");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
		}
    print'</td></tr>';

    // Civility
    print '<tr><td>'.$langs->trans("Commitment").'</td><td class="valeur">';
		if ($object->datecommitment)
		{
			print dol_print_date($object->datecommitment,'day');
			if ($object->hasDelay()) {
				print " ".img_warning($langs->trans("Late"));
			}
		}
		else
		{
			if (! $adht->subscription)
			{
				print $langs->trans("SubscriptionNotRecorded");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
			else
			{
				print $langs->trans("None");
			}
		}     
    print '</td>';
    print '</tr>';

    print "</table>";

    print '</div>';


    $cssclass='titlefield';
    $permission = $user->rights->adherent->creer;  // Used by the include of notes.tpl.php
    //include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

    dol_fiche_end();

    if (!empty ($object->fk_parent)) {
		        $adh=new Adherent($db);
            $adh->fetch($object->fk_parent);

		        // Lastname
		        print '<tr class="oddeven">';
            print '<td class="nowrap">link with ';
            print $adh->getNomUrl(1, 32).'</td>';
    } else {
    
    /*
    * List of linked members
    */   
    
if ($action=='deletelinkedmember' && $user->rights->adherent->creer) {
$form = new Form($db);
$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?rowid='.$object->id.'&link='.$link, $langs->trans('Confirm'), $langs->trans('ConfirmLinkedMember'), 'confirm_deletelinkedmember', '', 0, 1);
print $formconfirm;	
}

			print '<input class="flat" type="hidden" name="rowid" value="'.$socid.'" size="12">';
      
      print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

  $morehtmlright= dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=create');

      print load_fiche_titre($langs->trans("ListOfLinkedMembers"), $morehtmlright, '');

      print '<div class="div-table-responsive">';
      print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Name")." / ".$langs->trans("Company").'</td>';
            print '<td align="left">'.$langs->trans("Login").'</td>';
            print '<td align="left">'.$langs->trans("Type").'</td>';
            print '<td align="left">'.$langs->trans("Email").'</td>';
            print '<td align="left">'.$langs->trans("Status").'</td>';
            print '<td align="left">'.$langs->trans("EndSubscription").'</td>';
            print '<td align="right">'.$langs->trans('Action').'</td>';
            print "</tr>\n";
            
            foreach ($object->linkedmembers as $linkedmember)
            {

            $datefin=$db->jdate($linkedmember->datefin);

		        $adh=new Adherent($db);
            $adh->fetch($linkedmember->id);

		        // Lastname
		        print '<tr class="oddeven">';
            print '<td class="nowrap">';
            print $adh->getNomUrl(1, 32).'</td>';
                print '<td align="left">'.$linkedmember->login.'</td>';    
                print '<td align="left">'.$adh->getmorphylib($linkedmember->morphy).'</td>';        
                print '<td align="left">'.dol_print_email($linkedmember->email,0,0,1).'</td>';
                print '<td align="left">'.$adh->LibStatut($linkedmember->statut,$linkedmember->subscription,$datefin,2).'</td>';
		        // Date end subscription
		        if ($datefin)
		        {
			        print '<td align="center" class="nowrap">';
		            if ($datefin < dol_now() && $linkedmember->statut > 0)
		            {
		                print dol_print_date($datefin,'day')." ".img_warning($langs->trans("SubscriptionLate"));
		            }
		            else
		            {
		                print dol_print_date($datefin,'day');
		            }
		            print '</td>';
		        }
		        else
		        {
			        print '<td align="left" class="nowrap">';
			        if ($linkedmember->subscription == 'yes')
			        {
		                print $langs->trans("SubscriptionNotReceived");
		                if ($linkedmember->statut > 0) print " ".img_warning();
			        }
			        else
			        {
			            print $langs->trans("SubscriptionNotReceived");
			        }
		            print '</td>';
		        }                
                print '<td align="right"><a href="'. $_SERVER['PHP_SELF'] .'?action=deletelinkedmember&rowid=' . $object->id . '&link=' . $linkedmember->rowid . '" class="deletefilelink">';
                print img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink');
                print '</a></td>';
                print "</tr>";

            }
            print "</table></div>";
}
}

llxFooter();
$db->close();