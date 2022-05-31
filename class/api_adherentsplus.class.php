<?php
/* Copyright (C) 2018-2020   Thibault FOUCART           <support@ptibogxiv.net>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/adherentsplus/class/adherent.class.php');	
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
dol_include_once('/adherentsplus/class/consumption.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');

/**
 * API class for members
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class AdherentsPlus extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'morphy',
        'typeid'
    );

    /**
     * Constructor
     */
    function __construct()
    {
        global $db, $conf;
        $this->db = $db;
    }

    /**
     * Get properties of a member object by linked thirdparty
     *
     * Return an array with member informations
     *
     * @param     int     $thirdparty ID of third party
     * 
     * @return array|mixed Data without useless information
     *
     * @url GET thirdparty/{thirdparty}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    function getByThirdparty($thirdparty)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $member = new AdherentPlus($this->db);
        $result = $member->fetch('', '', $thirdparty);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($member);
    }
    
    /**
     * Get properties of a member object by linked thirdparty email
     *
     * Return an array with member informations
     *
     * @param  string $email            Email of third party
     * 
     * @return array|mixed Data without useless information
     *
     * @url GET thirdparty/email/{email}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    function getByThirdpartyEmail($email)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $thirdparty = new Societe($this->db);
        $result = $thirdparty->fetch('', '', '', '', '', '', '', '', '', '', $email);
        if( ! $result ) {
            throw new RestException(404, 'thirdparty not found');
        }
        
        $member = new AdherentPlus($this->db);
        $result = $member->fetch('', '', $thirdparty->id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($member);
    } 
    
    /**
     * Get properties of a member object by linked thirdparty barcode
     *
     * Return an array with member informations
     *
     * @param  string $barcode            Barcode of third party
     * 
     * @return array|mixed Data without useless information
     *
     * @url GET thirdparty/barcode/{barcode}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    function getByThirdpartyBarcode($barcode)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $thirdparty = new Societe($this->db);
        $result = $thirdparty->fetch('', '', '', $barcode);
        if( ! $result ) {
            throw new RestException(404, 'thirdparty not found');
        }
        
        $member = new AdherentPlus($this->db);
        $result = $member->fetch('', '', $thirdparty->id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($member);
    }
    
    /**
     * Get properties of a member object by id
     *
     * Return an array with member informations
     *
     * @param     int     $id ID of member
     * @return 	array|mixed data without useless information
     *
     * @throws    RestException
     */
    function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($member);
    }     

    /**
     * List members
     *
     * Get a list of members
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Limit for list
     * @param int       $page       Page number
     * @param string    $typeid     ID of the type of member
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return array                Array of member objects
     *
     * @throws RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $typeid = '', $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as t";
        $sql.= ' WHERE t.entity IN ('.getEntity('adherent').')';
        if (!empty($typeid))
        {
            $sql.= ' AND t.fk_adherent_type='.$typeid;
        }
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)    {
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $i=0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
            	$obj = $db->fetch_object($result);
                $member = new AdherentPlus($this->db);
                if($member->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($member);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve member list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No member found');
        }

        return $obj_ret;
    }
    
     /**
     * List members types
     *
     * Get a list of members types
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param string    $nature     Nature of type phy, mor or both (for only both not mor or phy only)
     * @param int       $limit      Limit for list
     * @param int       $page       Page number
	   * @param string   	$member_id	Member id to filter type lists of.
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.libelle:like:'SO-%') and (t.subscription:=:'1')"
     * @return array                Array of member type objects
     *
     * @throws RestException
     */
    function type($sortfield = "t.rowid", $sortorder = 'ASC', $nature = 'all', $limit = 0, $page = 0, $member_id = '', $sqlfilters = '') {
        global $db, $conf;
        
        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t";
        $sql.= ' WHERE t.entity IN ('.getEntity('adherent').')';

        // Nature 
        if ($nature != 'all') {
        if ($nature == 'both') {
        $sql.= ' AND t.morphy IS NULL ';
        } else {
        $sql.= ' AND (t.morphy IS NULL OR t.morphy = "'.$nature.'")'; 
        }      
        }

        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)    {
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }
       
        $result = $db->query($sql);
        if ($result)
        {
            $i=0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
            	$obj = $db->fetch_object($result);
                $membertype = new AdherentTypePlus($this->db);
                if ($membertype->fetch($obj->rowid)) {
                    $membertype->subscription_calculator();
                    $obj_ret[] = $this->_cleanObjectDatas($membertype);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve member type list : '.$db->lasterror());
        }
        if ( ! count($obj_ret)) {
            throw new RestException(404, 'No member type found');
        }

        return $obj_ret;
    }
    
     /**
     * Get properties of a member type object
     *
     * Return an array with member type informations
     *
     * @param     int     $id ID of member type
     * @return    array|mixed data without useless information
     *
     * @throws    RestException
     */
    function gettype($id)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $membertype = new AdherentTypePlus($this->db);
        $result = $membertype->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member type not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('member',$membertype->id,'adherent_type')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $membertype->subscription_calculator();
        return $this->_cleanObjectDatas($membertype);
    }

    /**
     * Create member object
     *
     * @param array $request_data   Request data
     * @return int  ID of member
     */
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->creer) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        $member = new AdherentPlus($this->db);
        foreach($request_data as $field => $value) {
            $member->$field = $value;
        }
        if ($member->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error creating member', array_merge(array($member->error), $member->errors));
        }
        return $member->id;
    }
    
    /**
     * Create member type object
     *
     * @param array $request_data   Request data
     * @return int  ID of member type
     */
    function posttype($request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->adherent->configurer) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        $membertype = new AdherentTypePlus($this->db);
        foreach($request_data as $field => $value) {
            $membertype->$field = $value;
        }
        if ($membertype->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error creating member type', array_merge(array($membertype->error), $membertype->errors));
        }
        return $membertype->id;
    }

    /**
     * Update member
     *
     * @param int   $id             ID of member to update
     * @param array $request_data   Datas
     * @return int
     */
    function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->creer) {
            throw new RestException(401);
        }

        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('member',$member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            // Process the status separately because it must be updated using
            // the validate() and resiliate() methods of the class Adherent.
            if ($field == 'statut') {
                if ($value == '0') {
                    $result = $member->resiliate(DolibarrApiAccess::$user);
                    if ($result < 0) {
                        throw new RestException(500, 'Error when resiliating member: '.$member->error);
                    }
                } else if ($value == '1') {
                    $result = $member->validate(DolibarrApiAccess::$user);
                    if ($result < 0) {
                        throw new RestException(500, 'Error when validating member: '.$member->error);
                    }
                }
                else if ($value == '-1') {
                    $result = $member->revalidate(DolibarrApiAccess::$user);
                    if ($result < 0) {
                        throw new RestException(500, 'Error when validating member: '.$member->error);
                    }
                }
            } else {
                $member->$field = $value;
            }
        }

        // If there is no error, update() returns the number of affected rows
        // so if the update is a no op, the return value is zero.
        if($member->update(DolibarrApiAccess::$user) >= 0)
            return $this->get($id);

        return false;
    }
    
        /**
     * Update member type
     *
     * @param int   $id             ID of member type to update
     * @param array $request_data   Datas
     * @return int
     */
    function puttype($id, $request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->adherent->configurer) {
            throw new RestException(401);
        }

        $membertype = new AdherentTypePlus($this->db);
        $result = $membertype->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member type not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('member',$membertype->id,'adherent_type')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            // Process the status separately because it must be updated using
            // the validate() and resiliate() methods of the class AdherentType.
            $membertype->$field = $value;
        }

        // If there is no error, update() returns the number of affected rows
        // so if the update is a no op, the return value is zero.
        if ($membertype->update(DolibarrApiAccess::$user) >= 0)
            return $this->get($id);

        return false;
    }

    /**
     * Delete member
     *
     * @param int $id   member ID
     * @return array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->supprimer) {
            throw new RestException(401);
        }
        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('member',$member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $member->delete($member->id, DolibarrApiAccess::$user)) {
            throw new RestException(401,'error when deleting member');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'member deleted'
            )
        );
    }
    
    /**
     * Delete member type
     *
     * @param int $id   member type ID
     * @return array
     */
    function deletetype($id)
    {
        if (! DolibarrApiAccess::$user->rights->adherent->configurer) {
            throw new RestException(401);
        }
        $membertype = new AdherentTypePlus($this->db);
        $result = $membertype->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member type not found');
        }

        if ( ! DolibarrApi::_checkAccessToResource('member',$membertype->id,'adherent_type')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $membertype->delete($membertype->id)) {
            throw new RestException(401,'error when deleting member type');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'member type deleted'
            )
        );
    }    

    /**
     * List subscriptions of a member
     *
     * Get a list of subscriptions
     *
     * @param int $id ID of member
     * @return array Array of subscription objects
     *
     * @throws RestException
     *
     * @url GET {id}/subscriptions
     */
    function getSubscriptions($id)
    {
        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        $obj_ret = array();
        foreach ($member->subscriptions as $subscription) {
            $obj_ret[] = $this->_cleanObjectDatas($subscription);
        }
        return $obj_ret;
    }

    /**
     * Add a subscription for a member
     *
     * @param int $id               ID of member
     * @param int $start_date       Start date {@from body} {@type timestamp}
     * @param int $end_date         End date {@from body} {@type timestamp}
     * @param float $amount         Amount (may be 0) {@from body}
     * @param string $label         Label {@from body}
     * @return int  ID of subscription
     *
     * @url POST {id}/subscriptions
     */
    function createSubscription($id, $start_date, $end_date, $amount, $label='')
    {
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->creer) {
            throw new RestException(401);
        }

        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        return $member->subscription($start_date, $amount, 0, '', $label, '', '', '', $end_date);
    }

    /**
     * List of linked members
     *
     * Get a list of linked members
     *
     * @param int $id ID of member
     * @return array Array of linked members
     *
     * @throws RestException
     *
     * @url GET {id}/linkedmembers
     */
    function getLinkedmember($id)
    {
        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id, '', '', '', '', '', '', 1);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        $obj_ret = array();
        foreach ($member->linkedmembers as $linkedmembers) {
            $obj_ret[] = $this->_cleanObjectDatas($linkedmembers);
        }
        return $obj_ret;
    } 
    
    /**
     * Delete linked member
     *
     * Detach linked member of a parent member
     *
     * @param int $id ID of member
     * @param int $linkedmember ID of linked member
     * @return array Array of consumption objects
     *
     * @throws RestException
     *
     * @url DELETE {id}/linkedmembers/{linkedmemberid}
     */
    function deleteLinkedmember($id, $linkedmemberid)
    {
        if (! DolibarrApiAccess::$user->rights->adherent->configurer) {
            throw new RestException(401);
        }
        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member type not found');
        }

        if ( ! DolibarrApi::_checkAccessToResource('member',$member->id,'adherent')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $member->unlinkMember($linkedmemberid)) {
            throw new RestException(401,'error when deleting member type');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'member unlink'
            )
        );
    } 
    
    /**
     * Get properties of an consumption
     *
     * Return an array with wish informations
     *
     * @param  int    $id               Id of member
     * @param  int    $consumptionid      Id of consumption line
     * @return array|mixed                 Data without useless information
     *
     * @throws 401
     * @throws 404
     *
     * @url GET {id}/consumptions/{consumptionid}
     */
    public function getConsumption($id, $consumptionid)
    {
        if(! DolibarrApiAccess::$user->rights->societe->lire) {
            throw new RestException(401);
        }
        
        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        } 
        
        if( ! DolibarrApi::_checkAccessToResource('member', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $member->fetch_consumptions($consumptionid);
        if( ! $result ) {
            throw new RestException(404, 'consumption not found');
        }

        return $this->_cleanObjectDatas($member);
    }      
 
    /**
     * List consumptions of a member
     *
     * Get a list of consumptions
     *
     * @param int $id ID of member
     * @return array Array of consumption objects
     *
     * @throws 401
     * @throws 404
     *
     * @url GET {id}/consumptions
     */
    function getListConsumptions($id)
    {
        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id, '', '', '', '', '', 1);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        $obj_ret = array();
        foreach ($member->consumptions as $linkedmembers) {
            $obj_ret[] = $this->_cleanObjectDatas($linkedmembers);
        }
        return $obj_ret;
    } 
    
    /**
     * Create consumption object
     *
     * @param  int    $id               Id of member
     * @param array $request_data   Request data
     * @return int  ID of consumption
     *
     * @throws 401
     * @throws 500
     *
     * @url POST {id}/consumptions
     */
    public function postConsumption($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->societe->creer) {
            throw new RestException(401);
        }
        
        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }
        
        // Check mandatory fields
        //$result = $this->_validate($request_data);

        $consumptions = new Consumption($this->db);
        foreach($request_data as $field => $value) {
            $wish->$field = $value;
        }
        if ($consumptions->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error creating consumption', array_merge(array($consumptions->error), $consumptions->errors));
        }
        return $consumptions->id;
    }
    
    /**
     * Update consumption of a member
     *
     * @param  int    $id               Id of member
     * @param  int    $consumptionid      Id of consumption line
     * @param array $request_data   Datas
     * @return array|mixed                 Data without useless information
     *
     * @throws 401
     * @throws 404
     *
     * @url PUT {id}/consumptions/{consumptionid}
     */
    public function putConsumption($id, $consumptionid, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->societe->creer) {
            throw new RestException(401);
        }
        
        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        $consumption = new Consumption($this->db);
        $result = $consumption->fetch($consumption);
        if( ! $result ) {
            throw new RestException(404, 'consumption not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('consumption', $consumption->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
                $member->$field = $value;
        }

        // If there is no error, update() returns the number of affected rows
        // so if the update is a no op, the return value is zero.
        if ($consumption->update(DolibarrApiAccess::$user) >= 0)
        {
            return $this->get($id);
        }
        else
        {
        	throw new RestException(500, $consumption->error);
        }
    } 
    
    /**
     * Delete consumption of a member
     *
     * @param  int    $id               Id of member
     * @param  int    $consumptionid      Id of consumption line
     * @return array
     * 
     * @throws 401
     * @throws 404
     * @throws 500
     *
     * @url DELETE {id}/consumptions/{consumptionid}
     */
    public function deleteConsumption($id, $consumptionid)
    {
        if (! DolibarrApiAccess::$user->rights->adherent->configurer) {
            throw new RestException(401);
        }
        $member = new AdherentPlus($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member type not found');
        }

        if ( ! DolibarrApi::_checkAccessToResource('member',$member->id,'adherent')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        
        $consumption = new Consumption($this->db);
        if (! $consumption->delete($consumptionid)) {
            throw new RestException(401,'error when deleting member type');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'member unlink'
            )
        );
    } 
    
    /**
     * Validate fields before creating an object
     *
     * @param array|null    $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $member = array();
        foreach (AdherentsPlus::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $member[$field] = $data[$field];
        }
        return $member;
    }

    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object) {

    	$object = parent::_cleanObjectDatas($object);

        // Remove the subscriptions because they are handled as a subresource.
        unset($object->subscriptions);

        return $object;
    }     

}
