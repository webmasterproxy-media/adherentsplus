<?php
/* Copyright (C) 2006-2015  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2016  Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/member.lib.php
 *		\brief      Functions for module members
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Adherent	$object		Member
 *  @return array					head
 */
function memberplus_prepare_head(AdherentPlus $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/card.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("Member");
	$head[$h][2] = 'general';
	$h++;

	if ((! empty($conf->ldap->enabled) && ! empty($conf->global->LDAP_MEMBER_ACTIVE))
		&& (empty($conf->global->MAIN_DISABLE_LDAP_TAB) || ! empty($user->admin)))
	{
		$langs->load("ldap");

		$head[$h][0] = DOL_URL_ROOT.'/adherents/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	if (!empty($user->rights->adherent->cotisation->lire)) {
		$nbSubscription = is_array($object->subscriptions) ?count($object->subscriptions) : 0;
		$head[$h][0] = DOL_URL_ROOT.'/adherents/subscription.php?rowid='.$object->id;
		$head[$h][1] = $langs->trans("Subscriptions");
		$head[$h][2] = 'subscription';
		if ($nbSubscription > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbSubscription.'</span>';
		}
		$h++;
	}

	$tabtoadd = (!empty(getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR')) && getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') ? 'member' : 'thirdparty';

	if ($tabtoadd == 'member') {
		if (!empty($user->rights->partnership->read)) {
			$nbPartnership = is_array($object->partnerships) ? count($object->partnerships) : 0;
			$head[$h][0] = DOL_URL_ROOT.'/adherents/partnership.php?rowid='.$object->id;
			$head[$h][1] = $langs->trans("Partnership");
			$head[$h][2] = 'partnership';
			if ($nbPartnership > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbPartnership.'</span>';
			}
			$h++;
		}
	} else {
		if (!empty($user->rights->partnership->read)) {
			$nbPartnership = is_array($object->partnerships) ? count($object->partnerships) : 0;
			$head[$h][0] = DOL_URL_ROOT.'/societe/partnership.php?socid='.$object->id;
			$head[$h][1] = $langs->trans("Partnership");
			$head[$h][2] = 'partnership';
			if ($nbPartnership > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbPartnership.'</span>';
			}
			$h++;
		}
	}


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'member');

    $nbNote = 0;
    if(!empty($object->note)) $nbNote++;
    if(!empty($object->note_private)) $nbNote++;
    if(!empty($object->note_public)) $nbNote++;
    $head[$h][0] = DOL_URL_ROOT.'/adherents/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Note");
	$head[$h][2] = 'note';
    if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
	$h++;

    // Attachments
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->adherent->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, 'member');
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks=Link::count($db, $object->element, $object->id);
    $head[$h][0] = DOL_URL_ROOT.'/adherents/document.php?id='.$object->id;
    $head[$h][1] = $langs->trans('Documents');
    if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
    $head[$h][2] = 'document';
    $h++;

	// Show agenda tab
	if (! empty($conf->agenda->enabled))
	{
	    $head[$h][0] = DOL_URL_ROOT."/adherents/agenda.php?id=".$object->id;
	    $head[$h][1] = $langs->trans("Events");
	    if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
	    {
	        $head[$h][1].= '/';
	        $head[$h][1].= $langs->trans("Agenda");
	    }
	    $head[$h][2] = 'agenda';
	    $h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'member', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	AdherentType	$object         Member
 *  @return array           		head
 */
function memberplus_type_prepare_head(AdherentTypePlus $object)
{
	global $langs, $conf, $user;

	$h=0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/type.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("MemberType");
	$head[$h][2] = 'card';
	$h++;
  
	// Multilangs
	if ((float) DOL_VERSION >= 11.0 && ! empty($conf->global->MAIN_MULTILANGS))
	{
		$head[$h][0] = DOL_URL_ROOT."/adherents/type_translation.php?rowid=".$object->id;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}

	if ((! empty($conf->ldap->enabled) && ! empty($conf->global->LDAP_MEMBER_TYPE_ACTIVE))
		&& (empty($conf->global->MAIN_DISABLE_LDAP_TAB) || ! empty($user->admin)))
	{
		$langs->load("ldap");

		$head[$h][0] = DOL_URL_ROOT.'/adherents/type_ldap.php?rowid='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'membertype');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'membertype', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @return	array		head
 */
function member_admin_prepare_head()
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/adherentsplus/admin/adherent.php', 1);
    $head[$h][1] = $langs->trans("Miscellaneous");
    $head[$h][2] = 'general';
    $h++;
    
    $head[$h][0] = dol_buildpath('/adherentsplus/admin/about.php', 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    return $head;
}


/**
 *  Return array head with list of tabs to view object stats informations
 *
 *  @param	Adherent	$object         Member or null
 *  @return	array           		head
 */
function member_stats_prepare_head($object)
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/adherentsplus/stats/index.php', 1);
    $head[$h][1] = $langs->trans("Subscriptions");
    $head[$h][2] = 'statssubscription';
    $h++;

    $head[$h][0] = dol_buildpath('/adherentsplus/stats/geo.php?mode=memberbycountry', 1);
    $head[$h][1] = $langs->trans("Country");
    $head[$h][2] = 'statscountry';
    $h++;

    $head[$h][0] = dol_buildpath('/adherentsplus/stats/geo.php?mode=memberbyregion', 1);
    $head[$h][1] = $langs->trans("Region");
    $head[$h][2] = 'statsregion';
    $h++;

    $head[$h][0] = dol_buildpath('/adherentsplus/stats/geo.php?mode=memberbystate', 1);
    $head[$h][1] = $langs->trans("State");
    $head[$h][2] = 'statsstate';
    $h++;

    $head[$h][0] = dol_buildpath('/adherentsplus/stats/geo.php?mode=memberbytown', 1);
    $head[$h][1] = $langs->trans('Town');
    $head[$h][2] = 'statstown';
    $h++;

    $head[$h][0] = dol_buildpath('/adherentsplus/stats/byproperties.php', 1);
    $head[$h][1] = $langs->trans('ByProperties');
    $head[$h][2] = 'statsbyproperties';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'member_stats');

    complete_head_from_modules($conf,$langs,$object,$head,$h,'member_stats','remove');

    return $head;
}

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Subscription	$object		Subscription
 *  @return array						head
 */
function subscription_prepare_head(SubscriptionPlus $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/adherentsplus/subscription/card.php?rowid='.$object->id.'', 1);
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = dol_buildpath('/adherentsplus/subscription/info.php?rowid='.$object->id.'', 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'subscription');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'subscription','remove');

	return $head;
}
