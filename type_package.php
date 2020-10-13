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
 *      \file       htdocs/adherentsex/type_package.php
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


llxHeader('',$langs->trans("MembersTypeSetup"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form=new Form($db);
$formother=new FormOther($db);
$formproduct = new FormProduct($db);

$object = new AdherentTypePlus($db);
$object->fetch($rowid);
$object->fetch_optionals($rowid,$extralabels);

$head = memberplus_type_prepare_head($object);

dol_fiche_head($head, 'package', $langs->trans("MemberType"), -1, 'group');

$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
    
print '</div>';

dol_fiche_end();

if ($socid && $action == 'create' && $user->rights->wishlist->create)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforadd='add';
	print '<input type="hidden" name="action" value="'.$actionforadd.'">';
}

if ($socid && $action == 'edit' && $user->rights->wishlist->create)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforedit='update';
	print '<input type="hidden" name="action" value="'.$actionforedit.'">';
}
    
/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */
	if ($action != 'create' && $action != 'edit')
	{


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

  $morehtmlright= dolGetButtonTitle($langs->trans('AddAWish'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=create');

      print load_fiche_titre($langs->trans("ListOfProductsServices"), $morehtmlright, '');

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
  
// Create Card
if ($socid && $action == 'create' && $user->rights->wishlist->create)
{
	dol_fiche_head($head, 'wishlist', $langs->trans("ThirdParty"), 0, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PredefinedProductsAndServicesToSell").'</td>';
	print '<td>';
  			if (! empty($conf->global->ENTREPOT_EXTRA_STATUS))
			{
				// hide products in closed warehouse, but show products for internal transfer
				$form->select_produits(GETPOST('productid', 'int'), 'productid', $filtertype, $conf->product->limit_size, $object->price_level, 1, 2, '', 1, array(), $object->id, '1', 0, 'maxwidth300', 0, 'warehouseopen,warehouseinternal', GETPOST('combinations', 'array'));
			}
			else
			{
				$form->select_produits(GETPOST('productid', 'int'), 'productid', $filtertype, $conf->product->limit_size, $object->price_level, 1, 2, '', 1, array(), $object->id, '1', 0, 'maxwidth300', 0, '', GETPOST('combinations', 'array'));
			}
  print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Qty").'</td>';
	print '<td><input class="minwidth200" type="text" name="quantity" value="'.(GETPOST('quantity', 'int')?GETPOST('quantity', 'int'):1).'"></td></tr>';

	print '<tr><td>'.$langs->trans("Target").'</td>';
	print '<td><input class="minwidth200" type="text" name="target" value="'.GETPOST('target', 'int').'"></td></tr>';

	print '<tr><td>'.$langs->trans("Rank").'</td>';
	print '<td><input class="minwidth200" type="text" name="rank" value="'.(GETPOST('rank','int')?GETPOST('rank','int'):$wish->rang).'"></td></tr>';

  // Visibility
  print '<tr><td class="fieldrequired"><label for="priv">'.$langs->trans("ContactVisibility").'</label></td><td colspan="3">';
  $selectarray=array('0'=>$langs->trans("ContactPublic"),'1'=>$langs->trans("ContactPrivate"));
  print $form->selectarray('priv', $selectarray, $wish->priv, 0);
  print '</td></tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	dol_set_focus('#label');

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Add").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

// Create Card
if ($socid && $action == 'edit' && $user->rights->wishlist->create)
{
	dol_fiche_head($head, 'wishlist', $langs->trans("ThirdParty"), 0, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

  $wish->fetch($lineid);  

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PredefinedProductsAndServicesToSell").'</td>';
	  $product_static = new Product($db);
		$product_static->id = $wish->fk_product;
		$product_static->ref = $wish->ref;
    $product_static->label = $wish->label;
    $product_static->type = $wish->fk_type;
	print '<td>';
	print $product_static->getNomUrl(1)." - ".$wish->label;
	print "</td>";
  print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Qty").'</td>';
	print '<td><input class="minwidth200" type="text" name="quantity" value="'.(GETPOST('quantity','int')?GETPOST('quantity','int'):$wish->qty).'"></td></tr>';

	print '<tr><td>'.$langs->trans("Target").'</td>';
	print '<td><input class="minwidth200" type="text" name="target" value="'.(GETPOST('target','int')?GETPOST('target','int'):$wish->target).'"></td></tr>';
  
	print '<tr><td>'.$langs->trans("Rank").'</td>';
	print '<td><input class="minwidth200" type="text" name="rank" value="'.(GETPOST('rank','int')?GETPOST('rank','int'):$wish->rang).'"></td></tr>';
  
  // Visibility
  print '<tr><td class="fieldrequired"><label for="priv">'.$langs->trans("ContactVisibility").'</label></td><td colspan="3">';
  $selectarray=array('0'=>$langs->trans("ContactPublic"),'1'=>$langs->trans("ContactPrivate"));
  print $form->selectarray('priv', $selectarray, $wish->priv, 0);
  print '</td></tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	dol_set_focus('#label');

	print '<div class="center"><input type="hidden" name="lineid" value="'.$lineid.'">';
	print '<input class="button" value="'.$langs->trans("Edit").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

print '</form>';

llxFooter();

$db->close();
