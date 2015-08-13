<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

class BlueAcorn_AddressValidation_Model_Result extends Varien_Object
{
    /**
     * Validated addresses returned from API
     * Possible Keys: 'street', 'postcode', 'city', 'zip4' and 'state' (as 2 letter code)
     * Required keys: 'street', 'postcode', and street value must be an array of at least 2 elements
     * @var array
     */
    protected $_addresses = array();

    /**
     * Messages
     * @var array
     */
    protected $_messages = array();

    /**
     * Getter for addresses
     *
     * @return array
     */
    public function getAddresses()
    {
        return $this->_addresses;
    }

    /**
     * Getter for messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }
    /**
     * Add validated address to result
     * Return false if address does not have the required keys
     *
     * @param array $address
     * @return $this|bool
     */
    public function addAddress(array $address = array())
    {
        if (!empty($address['postcode']) && !empty($address['street1'])) {
            $this->_addresses[] = $address;
            return $this;
        }

        return false;
    }

    /**
     * Add message to this result while ensuring uniqueness
     *
     * @param array|string $messages
     * @return $this
     */
    public function addMessage($messages = array())
    {
        if (!is_array($messages)) {
            $messages = array($messages);
        }

        $this->_messages = array_unique(array_merge($this->_messages, $messages));

        return $this;
    }

    /**
     * Check if there are any messages
     *
     * @return bool
     */
    public function hasMessage()
    {
        return !empty($this->_messages);
    }

    /**
     * Check if there are any addresses
     *
     * @return bool
     */
    public function hasAddress()
    {
        foreach ($this->_addresses as $_address) {
            if (!empty($_address['street1']) && !empty($_address['postcode'])) {
                return true;
            }
        }
        return false;
    }


    /**
     * Merges this Result with another Result argument. This object
     * takes precedence over the argument object (duplicates are removed from the
     * argument, not from this object).
     *
     * @param BlueAcorn_AddressValidation_Model_Result $otherResult
     * @return $this
     */
    public function merge(BlueAcorn_AddressValidation_Model_Result $otherResult)
    {
        // Cycle through the both Results, and log keys to ignore from other Result
        $otherAddresses = $otherResult->getAddresses();
        $keysToIgnore = array();
        foreach($this->getAddresses() as $thisAddress) {
            foreach($otherAddresses as $key => $otherAddress) {
                if (Mage::helper('blueacorn_addressvalidation')->compareAddresses($thisAddress, $otherAddress)) {
                    $keysToIgnore[] = $key;
                }
            }
        }
        // Add the other addresses that have not been flagged to ignore
        foreach($otherAddresses as $key => $otherAddress) {
            if (!in_array($key, $keysToIgnore)) {
                $this->addAddress($otherAddress);
            }
        }

        $otherMessages = $otherResult->getMessages();
        foreach($otherMessages as $message) {
            $this->addMessage($message);
        }

        return $this;
    }
}