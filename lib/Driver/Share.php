<?php
/**
 * The Turba_Driver:: class provides a common abstracted interface to the
 * various directory search drivers.  It includes functions for searching,
 * adding, removing, and modifying directory entries.
 *
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@csh.rit.edu>
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Driver_Share extends Turba_Driver
{
    /**
     * Horde_Share object for this source.
     *
     * @var Horde_Share
     */
    protected $_share;

    /**
     * Underlying driver object for this source.
     *
     * @var Turba_Driver
     */
    protected $_driver;

    /**
     * Constructor
     *
     * @param string $name   The source name
     * @param array $params  The parameter array describing the source
     *
     * @return Turba_Driver
     */
    public function __construct($name = '', array $params = array())
    {
        parent::__construct($name, $params);
        $this->_share = $this->_params['config']['params']['share'];
        $this->_driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->createTrusted($this->_params['config'], $name);
        $this->_driver->setContactOwner($this->_getContactOwner());
        $this->_driver->setSourceName($name);
    }

    /**
     * Synchronize, if needed.
     *
     * @param mixed  $token  A value indicating the last synchronization point,
     *                       if available.
     */
    public function synchronize($token = false)
    {
        $this->_driver->synchronize($token);
    }

    /**
     * Proxy to decorated base driver.
     *
     * @param string $method  Method name.
     * @param array $args     Method arguments.
     *
     * @return mixed  Method result.
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_driver, $method), $args);
    }

    /**
     * Checks if this backend has a certain capability.
     *
     * @param string $capability  The capability to check for.
     *
     * @return boolean  Supported or not.
     */
    public function hasCapability($capability)
    {
        return $this->_driver->hasCapability($capability);
    }

    /**
     * Translates the keys of the first hash from the generalized Turba
     * attributes to the driver-specific fields. The translation is based on
     * the contents of $this->map.
     *
     * @param array $hash  Hash using Turba keys.
     *
     * @return array  Translated version of $hash.
     */
    public function toDriverKeys(array $hash)
    {
        return $this->_driver->toDriverKeys($hash);
    }

    /**
     * Translates a hash from being keyed on driver-specific fields to being
     * keyed on the generalized Turba attributes. The translation is based on
     * the contents of $this->map.
     *
     * @param array $entry  A hash using driver-specific keys.
     *
     * @return array  Translated version of $entry.
     */
    public function toTurbaKeys(array $entry)
    {
        return $this->_driver->toTurbaKeys($entry);
    }

    /**
     * Searches the current address book for duplicate entries.
     *
     * Duplicates are determined by comparing email and name or last name and
     * first name values.
     *
     * @return array  A hash with the following format:
     * <code>
     * array('name' => array('John Doe' => Turba_List, ...), ...)
     * </code>
     * @throws Turba_Exception
     */
    public function searchDuplicates()
    {
        return $this->_driver->searchDuplicates();
    }

    /**
     * Obtains a Turba_List to get TimeObjects out of.
     *
     * @param Horde_Date $start  The starting date.
     * @param Horde_Date $end    The ending date.
     * @param string $field      The address book field containing the
     *                           timeObject information (birthday,
     *                           anniversary).
     *
     * @return Turba_List  A list of objects.
     * @throws Turba_Exception
     */
    public function getTimeObjectTurbaList(Horde_Date $start, Horde_Date $end, $field)
    {
        return $this->_driver->getTimeObjectTurbaList($start, $end, $field);
    }

    /**
     * Checks if the current user has the requested permissions on this
     * address book.
     *
     * @param integer $perm  The permission to check for.
     *
     * @return boolean  True if the user has permission, otherwise false.
     */
    public function hasPermission($perm)
    {
        return $this->_share->hasPermission($GLOBALS['registry']->getAuth(), $perm);
    }

    /**
     * Return the name of this address book.
     *
     * @string Address book name
     */
    public function getName()
    {
        $share_parts = explode(':', $this->_share->getName());
        return array_pop($share_parts);
    }

    /**
     * Return the owner to use when searching or creating contacts in
     * this address book.
     *
     * @return string  TODO
     * @throws Turba_Exception
     */
    protected  function _getContactOwner()
    {
        $params = @unserialize($this->_share->get('params'));
        if (!empty($params['name'])) {
            return $params['name'];
        }

        throw new Turba_Exception(_("Unable to find contact owner."));
    }

    /**
     * Runs any actions after setting a new default tasklist.
     *
     * @param string $share  The default share ID.
     */
    public function setDefaultShare($share)
    {
        $this->_driver->setDefaultShare($share);
    }

    /**
     * Creates an object key for a new object.
     *
     * @param array $attributes  The attributes (in driver keys) of the
     *                           object being added.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _makeKey(array $attributes)
    {
        return $this->_driver->_makeKey($attributes);
    }

    /**
     * Creates an object UID for a new object.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _makeUid()
    {
        return $this->_driver->_makeUid();
    }

    /**
     * Searches the address book with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty array,
     * all records will be returned.
     *
     * @param array $criteria       Array containing the search criteria.
     * @param array $fields         List of fields to return.
     * @param array $blobFields     Array of fields containing binary data.
     * @param boolean $count_only   Only return the count of matching entries,
     *                              not the entries themselves.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search(array $criteria, array $fields, array $blobFields = array(), $count_only = false)
    {
        return $this->_driver->_search($criteria, $fields, $blobFields, $count_only);
    }

    /**
     * Reads the given data from the address book and returns the results.
     *
     * @param string $key        The primary key field to use.
     * @param mixed $ids         The ids of the contacts to load.
     * @param string $owner      Only return contacts owned by this user.
     * @param array $fields      List of fields to return.
     * @param array $blobFields  Array of fields containing binary data.
     * @param array $dateFields  Array of fields containing date data.
     *                           @since 4.2.0
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($key, $ids, $owner, array $fields,
                             array $blobFields = array(),
                             array $dateFields = array())
    {
        return $this->_driver->_read($key, $ids, $owner, $fields, $blobFields, $dateFields);
    }

    /**
     * Adds the specified contact to the addressbook.
     *
     * @param array $attributes   The attribute values of the contact.
     * @param array $blob_fields  Fields that represent binary data.
     * @param array $date_fields  Fields that represent dates. @since 4.2.0
     *
     * @throws Turba_Exception
     */
    protected function _add(array $attributes, array $blob_fields = array(), array $date_fields = array())
    {
        return $this->_driver->_add($attributes, $blob_fields, $date_fields);
    }

    /**
     * Returns ability of the backend to add new contacts.
     *
     * @return boolean  Can backend add?
     */
    protected function _canAdd()
    {
        return $this->_driver->_canAdd();
    }

    /**
     * Deletes the specified contact from the addressbook.
     *
     * @param string $object_key TODO
     * @param string $object_id  TODO
     *
     * @throws Turba_Exception
     */
    protected function _delete($object_key, $object_id)
    {
        return $this->_driver->_delete($object_key, $object_id);
    }

    /**
     * Deletes all contacts from a specific address book.
     *
     * @param string $sourceName  The source to remove all contacts from.
     *
     * @return array  An array of UIDs
     * @throws Turba_Exception
     */
    protected function _deleteAll($sourceName = null)
    {
        if (is_null($sourceName)) {
            $sourceName = $this->getContactOwner();
        }
        return $this->_driver->_deleteAll($sourceName);
    }

    /**
     * Saves the specified object in the SQL database.
     *
     * @param Turba_Object $object  The object to save
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    protected function _save(Turba_Object $object)
    {
        return $this->_driver->_save($object);
    }

    /**
     * Remove all data for a specific user.
     *
     * @param string $user  The user to remove all data for.
     */
    public function removeUserData($user)
    {
        // Make sure we are being called by an admin.
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception_PermissionDenied(_("Permission denied"));
        }
        $this->_deleteAll();
        $GLOBALS['injector']
            ->getInstance('Turba_Shares')
            ->removeShare($this->_share);
        unset($this->_share);
    }

}
