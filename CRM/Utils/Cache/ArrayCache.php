<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class CRM_Utils_Cache_Arraycache
 */
class CRM_Utils_Cache_Arraycache implements CRM_Utils_Cache_Interface {

  use CRM_Utils_Cache_NaiveMultipleTrait;
  use CRM_Utils_Cache_NaiveHasTrait; // TODO Native implementation

  /**
   * The cache storage container, an in memory array by default
   */
  protected $_cache;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_Arraycache
   */
  public function __construct($config) {
    $this->_cache = array();
  }

  /**
   * @param string $key
   * @param mixed $value
   * @param null|int|\DateInterval $ttl
   * @return bool
   */
  public function set($key, $value, $ttl = NULL) {
    if ($ttl !== NULL) {
      throw new \RuntimeException("FIXME: " . __CLASS__ . "::set() should support non-NULL TTL");
    }
    $this->_cache[$key] = $value;
    return TRUE;
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function get($key, $default = NULL) {
    return CRM_Utils_Array::value($key, $this->_cache, $default);
  }

  /**
   * @param string $key
   * @return bool
   */
  public function delete($key) {
    unset($this->_cache[$key]);
    return TRUE;
  }

  public function flush() {
    unset($this->_cache);
    $this->_cache = array();
    return TRUE;
  }

  public function clear() {
    return $this->flush();
  }

}
