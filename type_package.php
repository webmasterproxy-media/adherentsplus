<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
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
 *      \file       htdocs/adherentsex/type.php
 *      \ingroup    member
 *      \brief      Member's type setup
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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';    
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("adherentsplus@adherentsplus");

$rowid  = GETPOST('rowid','int');
$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');

$search_ref	= GETPOST('search_ref','alpha');
$search_label		= GETPOST('search_label','alpha');
$search_qty		= GETPOST('search_qty','int');
$type				= GETPOST('type','alpha');
$status				= GETPOST('status','alpha');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
//if (! $sortfield) {  $sortfield="d.lastname"; }

$label=GETPOST("label","alpha");
$statut=GETPOST("statut","int");
$morphy=GETPOST("morphy","alpha");
$subscription=GETPOST("subscription","int");
$family=GETPOST("family","int");
$vote=GETPOST("vote","int");
$comment=GETPOST("comment");
$mail_valid=GETPOST("mail_valid");
$welcome=GETPOST("welcome","alpha");
$price=GETPOST("price","alpha");
$price_level=GETPOST("price_level","int");
$duration_value = GETPOST('duration_value', 'int');
$duration_unit = GETPOST('duration_unit', 'alpha');
$automatic=GETPOST("automatic","int");
$automatic_renew=GETPOST("automatic_renew","int");
// Security check
$result=restrictedArea($user,'adherent',$rowid,'adherent_type');

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('adherent_type');

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_lastname="";
    $search_login="";
    $search_email="";
    $type="";
    $sall="";
}


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membertypecard','globalcard'));


/*
 *	Actions
 */

if ($action == 'add' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{
		$object = new AdherentTypePlus($db);

    $object->welcome     = price2num($welcome);
    $object->price       = price2num($price);
    $object->price_level       = trim($price_level?$price_level:'1');
    $object->automatic   = (boolean) trim($automatic);
    $object->automatic_renew   = (boolean) trim($automatic_renew);
    $object->family   = (boolean) trim($family);
		$object->label			= trim($label);
    $object->statut         = trim($statut);
    $object->morphy         = trim($morphy);
		$object->subscription	= (int) trim($subscription);
    $object->duration_value     	 = $duration_value;
    $object->duration_unit      	 = $duration_unit;
		$object->note			= trim($comment);
		$object->mail_valid		= (boolean) trim($mail_valid);
		$object->vote			= (boolean) trim($vote);

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		if ($object->label)
		{
			$id=$object->create($user);
			if ($id > 0)
			{
				header("Location: ".$_SERVER["PHP_SELF"]);
				exit;
			}
			else
			{
				$mesg=$object->error;
				$action = 'create';
			}
		}
		else
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			$action = 'create';
		}
	}
}

if ($action == 'update' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{
		$object = new AdherentTypePlus($db);
		$object->id             = $rowid;
		$object->label        = trim($label);
    $object->statut         = trim($statut);
    $object->morphy         = trim($morphy);
		$object->subscription   = (int) trim($subscription);
		$object->note           = trim($comment);
		$object->mail_valid     = (boolean) trim($mail_valid);
		$object->vote           = (boolean) trim($vote);
    $object->family           = (boolean) trim($family);
    $object->welcome     = price2num($welcome);
    $object->price       = price2num($price);
    $object->price_level       = trim($price_level?$price_level:'1');
    $object->duration_value     	 = $duration_value;
    $object->duration_unit      	 = $duration_unit;
    $object->automatic   = (boolean) trim($automatic);
    $object->automatic_renew   = (boolean) trim($automatic_renew);
		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		$object->update($user);

		header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$_POST["rowid"]);
		exit;
	}
}

if ($action == 'delete' && $user->rights->adherent->configurer)
{
	$object = new AdherentTypePlus($db);
	$object->delete($rowid);
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * View
 */

llxHeader('',$langs->trans("MembersTypeSetup"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form=new Form($db);
$formother=new FormOther($db);
$formproduct = new FormProduct($db);

// List of members type
if (! $rowid && $action != 'create' && $action != 'edit')
{
	//dol_fiche_head('');

	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.vote, d.welcome, d.price, d.vote, d.automatic, d.automatic_renew, d.family, d.statut, d.morphy";
	$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql.= " WHERE d.entity IN (".getEntity('adherent').")";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	    print_barre_liste($langs->trans("MembersTypes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

		$moreforfilter = '';

		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Label").'</th>';
    print '<th class="center">'.$langs->trans("Nature").'</th>';
    print '<th class="center">'.$langs->trans("GroupSubscription").'</th>';
		print '<th class="center">'.$langs->trans("SubscriptionRequired").'</th>';
		print '<th class="center">'.$langs->trans("VoteAllowed").'</th>';
    print '<th class="center">'.$langs->trans("Validation").'</th>';
    print '<th class="center">'.$langs->trans("Renewal").'</th>';
    print '<th class="center">'.$langs->trans("Status").'</th>';
		print '<th>&nbsp;</th>';
		print "</tr>\n";

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			print '<tr class="oddeven">';
			print '<td><a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a></td>';
			print '<td><a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.dol_escape_htmltag($objp->label).'</a></td>';
      print '<td align="center">';
		if ($objp->morphy == 'phy') { print $langs->trans("Physical"); }
		elseif ($objp->morphy == 'mor') { print $langs->trans("Moral"); } 
    else print $langs->trans("Physical & Morale");    
      print '</td>'; //'.$objp->getmorphylib($objp->morphy).'
      print '<td class="center">'.yn($objp->family).'</td>';
			print '<td class="center">'.yn($objp->subscription).'</td>';
			print '<td class="center">'.yn($objp->vote).'</td>';
      print '<td class="center">'.autoOrManual($objp->automatic).'</td>';
      print '<td class="center">'.autoOrManual($objp->automatic_renew).'</td>';
      print '<td class="center">';
if ( !empty($objp->statut) ) print img_picto($langs->trans("InActivity"),'statut4');
else print img_picto($langs->trans("ActivityCeased"),'statut5');     
      print '</td>';
			if ($user->rights->adherent->configurer)
				print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
			else
				print '<td class="right">&nbsp;</td>';
			print "</tr>";
			$i++;
		}
		print "</table>";
		print '</div>';

		print '</form>';
	}
	else
	{
		dol_print_error($db);
	}
}


/* ************************************************************************** */
/*                                                                            */
/* Creation mode                                                              */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'create')
{
	$object = new AdherentTypePlus($db);

	print load_fiche_titre($langs->trans("NewMemberType"));

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

    dol_fiche_head('');

	print '<table class="border" width="100%">';
	print '<tbody>';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" size="40"></td></tr>';

  print '<tr><td>'.$langs->trans("Status").'</td><td>';
  print $form->selectarray('statut', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),1);
  print '</td></tr>';
  
  // Morphy
  $morphys[] = $langs->trans("Physical & Morale");
  $morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Morale");
	print '<tr><td><span>'.$langs->trans("Nature").'</span></td><td>';
	print $form->selectarray("morphy", $morphys, isset($_POST["morphy"])?$_POST["morphy"]:$object->morphy);
	print "</td></tr>";
  
  print '<tr><td>'.$langs->trans("GroupSubscription").'</td><td>';
	print $form->selectyesno("family",0,1);
  print '</td></tr>';
	
	print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
	print $form->selectyesno("subscription",1,1);
	print '</td></tr>';
  
  print '<tr ><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
	print '<input size="10" type="text" value="' . price($object->welcome) . '" name="welcome">';
  print ' '.$langs->trans("Currency".$conf->currency);    
	print '</td></tr>';
    
  print '<tr ><td>'.$langs->trans("SubscriptionPrice").'</td><td>';
	print '<input size="10" type="text" value="' . price($object->price) . '" name="price">';   
  print ' '.$langs->trans("Currency".$conf->currency);    
	print '</td></tr>';
if (! empty($conf->global->PRODUIT_MULTIPRICES)){
  print '<tr><td>';
	print $langs->trans("PriceLevel").'</td><td colspan="2">';
	print '<select name="price_level" class="flat">';
	for($i=1;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
	{
		print '<option value="'.$i.'"' ;
		if($i == $object->price_level)
		print 'selected';
		print '>'.$i;
		$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
		if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
		print '</option>';
	}
	print '</select>';
	print '</td></tr>';
}

  print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
  print '<input name="surface" size="4" value="1">';
  print $formproduct->selectMeasuringUnits("duration_unit", "time", "y", 0, 1);
  print '</td></tr>';

	print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	print $form->selectyesno("vote",0,1);
	print '</td></tr>';
  
  print '<tr><td>'.$langs->trans("Validation").'</td><td>';
	print $formother->selectAutoManual("automatic",$object->automatic,1);
	print '</td></tr>';
    
  print '<tr><td>'.$langs->trans("Renewal").'</td><td>';
	print $formother->selectAutoManual("automatic_renew",$object->automatic_renew,1);
	print '</td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="comment" wrap="soft" class="centpercent" rows="3"></textarea></td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,'90%');
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields,'edit');
	}
	print '<tbody>';
	print "</table>\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" name="button" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'" onclick="history.go(-1)" />';
	print '</div>';

	print "</form>\n";
}

/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0)
{
	if ($action != 'edit')
	{
		$object = new AdherentTypePlus($db);
		$object->fetch($rowid);
		$object->fetch_optionals($rowid,$extralabels);

		$head = memberplus_type_prepare_head($object);

		dol_fiche_head($head, 'package', $langs->trans("MemberType"), -1, 'group');

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'rowid', $linkback);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans("Duration").'</td><td colspan="2">'.$object->duration_value.'&nbsp;';
    if ($object->duration_value > 1)
    {
    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
    }
    elseif ($object->duration_value > 0)
    {
    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    }
    print (! empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? $langs->trans($dur[$object->duration_unit]) : '')."&nbsp;";
    print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($object->note)."</td></tr>";

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';
    
    print '</div>';

		dol_fiche_end();


		/*
		 * Buttons
		 */

		print '<div class="tabsAction">';

		// Add
    if ( $user->rights->adherent->configurer && !empty($object->statut) )
		{
		print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=create&typeid='.$object->id.'">'.$langs->trans("AddProductOrService").'</a></div>';
    } else {
		print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoAddProductOrService")).'">'.$langs->trans("AddProductOrService").'</a></div>';    
    }

		print "</div>";


		// Show list of members (nearly same code than in page list.php)

		$membertypestatic=new AdherentTypePlus($db);

		$now=dol_now();

		$sql = "SELECT t.rowid, t.fk_type as type, t.fk_product as product, t.qty as qty";
    $sql.= " , p.label, p.ref as ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent_package as t";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = t.fk_product";
		$sql.= " WHERE t.entity IN (".getEntity('adherent').")";
		$sql.= " AND t.fk_type = ".$object->id;
		if ($sall)
		{
			//$sql.=natural_search(array("f.firstname","d.lastname","d.societe","d.email","d.login","d.address","d.town","d.note_public","d.note_private"), $sall);
		}
		if ($status != '')
		{
		    $sql.= " AND t.statut IN (".$db->escape($status).")";     // Peut valoir un nombre ou liste de nombre separes par virgules
		}
		if ($action == 'search')
		{
			if (GETPOST('search'))
			{
		  		//$sql.= natural_search(array("d.firstname","d.lastname"), GETPOST('search','alpha'));
		  	}
		}
		if (! empty($search_ref))
		{
			$sql.= natural_search("p.ref", $search_ref);
		}
		if (! empty($search_label))
		{
			$sql.= natural_search("p.label", $search_label);
		}
		if (! empty($search_qty))
		{
			$sql.= natural_search("t.qty", $search_qty);
		}
		if ($filter == 'uptodate')
		{
		    //$sql.=" AND datefin >= '".$db->idate($now)."'";
		}
		if ($filter == 'outofdate')
		{
		    //$sql.=" AND datefin < '".$db->idate($now)."'";
		}
		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$resql = $db->query($sql);
		    if ($resql) $nbtotalofrecords = $db->num_rows($result);
		    else dol_print_error($db);
		}
		// Add order and limit
		$sql.= " ".$db->order($sortfield,$sortorder);
		$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);

		$resql = $db->query($sql);
		if ($resql)
		{
		    $num = $db->num_rows($resql);
		    $i = 0;

		    $titre=$langs->trans("ProductsList");
		    if ($status != '')
		    {
		        if ($status == '-1,1')								{ $titre=$langs->trans("MembersListQualified"); }
		        else if ($status == '-1')							{ $titre=$langs->trans("MembersListToValid"); }
		        else if ($status == '1' && ! $filter)				{ $titre=$langs->trans("MembersListValid"); }
		        else if ($status == '1' && $filter=='uptodate')		{ $titre=$langs->trans("MembersListUpToDate"); }
		        else if ($status == '1' && $filter=='outofdate')	{ $titre=$langs->trans("MembersListNotUpToDate"); }
		        else if ($status == '0')							{ $titre=$langs->trans("MembersListResiliated"); }
		    }
		    elseif ($action == 'search')
		    {
		        $titre=$langs->trans("MembersListQualified");
		    }

		    if ($type > 0)
		    {
				$membertype=new AdherentTypePLus($db);
		        $result=$membertype->fetch($type);
				$titre.=" (".$membertype->label.")";
		    }

		    $param="&rowid=".$rowid;
		    if (! empty($status))			$param.="&status=".$status;
		    if (! empty($search_ref))	$param.="&search_ref=".$search_ref;
		    if (! empty($search_label))		$param.="&search_label=".$search_label;
		    if (! empty($search_email))		$param.="&search_email=".$search_email;
		    if (! empty($filter))			$param.="&filter=".$filter;

		    if ($sall)
		    {
		        print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
		    }

			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input class="flat" type="hidden" name="rowid" value="'.$rowid.'" size="12"></td>';

			print '<br>';
            print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

            $moreforfilter = '';

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

			// Lignes des champs de filtre
			print '<tr class="liste_titre_filter">';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="7"></td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_label" value="'.dol_escape_htmltag($search_label).'" size="12"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_qty" value="'.dol_escape_htmltag($search_qty).'" size="5"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td align="right" colspan="2" class="liste_titre">';
			print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		    print '&nbsp; ';
		    print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
			print '</td>';

			print "</tr>";

			print '<tr class="liste_titre">';
		    print_liste_field_titre( $langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Label",$_SERVER["PHP_SELF"],"p.label",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Description",$_SERVER["PHP_SELF"],"",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Qty",$_SERVER["PHP_SELF"],"t.qty",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("DateStart",$_SERVER["PHP_SELF"],"d.statut,d.datefin",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("DateEnd",$_SERVER["PHP_SELF"],"d.datefin",$param,"",'align="center"',$sortfield,$sortorder);
		    print_liste_field_titre("Action",$_SERVER["PHP_SELF"],"",$param,"",'width="60" align="center"',$sortfield,$sortorder);
		    print "</tr>\n";

		    while ($i < $num && $i < $conf->liste_limit)
		    {
		        $objp = $db->fetch_object($resql);

		        $datefin=$db->jdate($objp->datefin);

	$product_static = new Product($db);
		$product_static->id = $objp->product;
		$product_static->ref = $objp->ref;
		        // Lastname
		        print '<tr class="oddeven">';
			print '<td class="tdoverflowmax200">';
			print $product_static->getNomUrl(1);
			print "</td>";

		        // Login
		        print '<td class="tdoverflowmax200">'.dol_trunc($objp->label, 80).'</td>';

		        // Type
		        /*print '<td class="nowrap">';
		        $membertypestatic->id=$objp->type_id;
		        $membertypestatic->label=$objp->type;
		        print $membertypestatic->getNomUrl(1,12);
		        print '</td>';
				*/

		        // Moral/Physique
		        print "<td>".dol_trunc($objp->label, 80)."</td>";

		        // Qty
            if (!empty($objp->qty)) {
 		        print "<td>".$objp->qty."</td>";           
            } else {
		        print "<td>".$langs->trans("unlimited")."</td>";
            }

		        // Statut
		        print '<td class="nowrap">';
		        //print $adh->LibStatut($objp->statut,$objp->subscription,$datefin,2);
		        print "</td>";

		        // Date end subscription
		        if ($datefin)
		        {
			        print '<td align="center" class="nowrap">';
		            if ($datefin < dol_now() && $objp->statut > 0)
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
			        if ($objp->subscription == 'yes')
			        {
		                print $langs->trans("SubscriptionNotReceived");
		                if ($objp->statut > 0) print " ".img_warning();
			        }
			        else
			        {
			            print '&nbsp;';
			        }
		            print '</td>';
		        }

		        // Actions
		        print '<td align="center">';
				if ($user->rights->adherent->creer)
				{
					print '<a href="card.php?rowid='.$objp->rowid.'&action=edit&return=list.php">'.img_edit().'</a>';
				}
				print '&nbsp;';
				if ($user->rights->adherent->supprimer)
				{
					print '<a href="type_package.php?rowid='.$objp->rowid.'&action=resign&return=list.php">'.img_picto($langs->trans("Resiliate"),'disable.png').'</a>';
		        }
				print "</td>";

		        print "</tr>\n";
		        $i++;
		    }

		    print "</table>\n";
            print '</div>';
            print '</form>';

			if ($num > $conf->liste_limit)
			{
			    print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'');
			}
		}
		else
		{
		    dol_print_error($db);
		}

	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Edition mode                                                               */
	/*                                                                            */
	/* ************************************************************************** */

	if ($action == 'edit')
	{
		$object = new AdherentTypePlus($db);
		$object->id = $rowid;
		$object->fetch($rowid);
		$object->fetch_optionals($rowid,$extralabels);

		$head = memberplus_type_prepare_head($object);

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="action" value="update">';

		dol_fiche_head($head, 'package', $langs->trans("MemberType"), 0, 'group');

		print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" size="40" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

    print '<tr><td>'.$langs->trans("Status").'</td><td>';
    print $form->selectarray('statut', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')), $object->statut);
    print '</td></tr>';
    
    // Morphy
    $morphys[null] = $langs->trans("Physical & Morale");
    $morphys["phy"] = $langs->trans("Physical");
    $morphys["mor"] = $langs->trans("Morale");
    print '<tr><td><span>'.$langs->trans("Nature").'</span></td><td>';
    print $form->selectarray("morphy", $morphys, isset($_POST["morphy"])?$_POST["morphy"]:$object->morphy);
    print "</td></tr>";
  
    print '<tr><td>'.$langs->trans("GroupSubscription").'</td><td>';
		print $form->selectyesno("family",$object->family,1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print $form->selectyesno("subscription",$object->subscription,1);
		print '</td></tr>';

    print '<tr ><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
		print '<input size="10" type="text" value="' . price($object->welcome) . '" name="welcome">';
    print ' '.$langs->trans("Currency".$conf->currency);    
		print '</td></tr>';
    
    print '<tr ><td>'.$langs->trans("SubscriptionPrice").'</td><td>';
		print '<input size="10" type="text" value="' . price($object->price) . '" name="price">';   
    print ' '.$langs->trans("Currency".$conf->currency);    
		print '</td></tr>';
if (! empty($conf->global->PRODUIT_MULTIPRICES)){    
    print '<tr><td>';
	  print $langs->trans("PriceLevel").'</td><td colspan="2">';
	  print '<select name="price_level" class="flat">';
	  for($i=1;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
  	{
		print '<option value="'.$i.'"' ;
		if($i == $object->price_level)
		print 'selected';
		print '>'.$i;
		$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
		if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
		print '</option>';
	  }
	  print '</select>';
	  print '</td></tr>';
 }
 
    print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
    print '<input name="duration_value" size="5" value="'.$object->duration_value.'"> ';
    print $formproduct->selectMeasuringUnits("duration_unit", "time", $object->duration_unit, 0, 1);
    print '</td></tr>';
                 
		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print $form->selectyesno("vote",$object->vote,1);
		print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("Validation").'</td><td>';
		print $formother->selectAutoManual("automatic",$object->automatic,1);
		print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("Renewal").'</td><td>';
		print $formother->selectAutoManual("automatic_renew",$object->automatic_renew,1);
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print '<textarea name="comment" wrap="soft" class="centpercent" rows="3">'.$object->note.'</textarea></td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,'90%');
		$doleditor->Create();
		print "</td></tr>";

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
		    print $object->showOptionals($extrafields,'edit');
		}

		print '</table>';

		// Extra field
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print '<br><br><table class="border" width="100%">';
			foreach($extrafields->attribute_label as $key=>$label)
			{
				if (isset($_POST["options_" . $key])) {
					if (is_array($_POST["options_" . $key])) {
						// $_POST["options"] is an array but following code expects a comma separated string
						$value = implode(",", $_POST["options_" . $key]);
					} else {
						$value = $_POST["options_" . $key];
					}
				} else {
					$value = $adht->array_options["options_" . $key];
				}
				print '<tr><td width="30%">'.$label.'</td><td>';
				print $extrafields->showInputField($key,$value);
				print "</td></tr>\n";
			}
			print '</table><br><br>';
		}

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print "</form>";
	}
}


llxFooter();

$db->close();
