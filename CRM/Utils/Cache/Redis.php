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
 * $Id$
 *
 */
class CRM_Utils_Cache_Redis implements CRM_Utils_Cache_Interface {

  use CRM_Utils_Cache_NaiveMultipleTrait; // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveHasTrait; // TODO Native implementation

  const DEFAULT_HOST    = 'localhost';
  const DEFAULT_PORT    = 6379;
  const DEFAULT_TIMEOUT = 3600;
  const DEFAULT_PREFIX  = '';

  /**
   * The host name of the redisd server
   *
   * @var string
   */
  protected $_host = self::DEFAULT_HOST;

  /**
   * The port on which to connect on
   *
   * @var int
   */
  protected $_port = self::DEFAULT_PORT;

  /**
   * The default timeout to use
   *
   * @var int
   */
  protected $_timeout = self::DEFAULT_TIMEOUT;

  /**
   * The prefix prepended to cache keys.
   *
   * If we are using the same redis instance for multiple CiviCRM
   * installs, we must have a unique prefix for each install to prevent
   * the keys from clobbering each other.
   *
   * @var string
   */
  protected $_prefix = self::DEFAULT_PREFIX;

  /**
   * The actual redis object
   *
   * @var Redis
   */
  protected $_cache;

  /**
   * Constructor
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_Redis
   */
  public function __construct($config) {
    if (isset($config['host'])) {
      $this->_host = $config['host'];
    }
    if (isset($config['port'])) {
      $this->_port = $config['port'];
    }
    if (isset($config['timeout'])) {
      $this->_timeout = $config['timeout'];
    }
    if (isset($config['prefix'])) {
      $this->_prefix = $config['prefix'];
    }

    $this->_cache = new Redis();
    if (!$this->_cache->connect($this->_host, $this->_port)) {
      // dont use fatal here since we can go in an infinite loop
      echo 'Could not connect to redisd server';
      CRM_Utils_System::civiExit();
    }
    if (CRM_Utils_Constant::value('CIVICRM_DB_CACHE_PASSWORD')) {
      $this->_cache->auth(CIVICRM_DB_CACHE_PASSWORD);
    }
  }

  /**
   * @param $key
   * @param $value
   * @param null|int|\DateInterval $ttl
   *
   * @return bool
   * @throws Exception
   */
  public function set($key, $value, $ttl = NULL) {
    if ($ttl !== NULL) {
      throw new \RuntimeException("FIXME: " . __CLASS__ . "::set() should support non-NULL TTL");
    }
    if (!$this->_cache->set($this->_prefix . $key, serialize($value), $this->_timeout)) {
      if (PHP_SAPI === 'cli' || (Civi\Core\Container::isContainerBooted() && CRM_Core_Permission::check('view debug output'))) {
        CRM_Core_Error::fatal("Redis set ($key) failed: " . $this->_cache->getLastError());
      }
      else {
        Civi::log()->error("Redis set ($key) failed: " . $this->_cache->getLastError());
        CRM_Core_Error::fatal("Redis set ($key) failed");
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function get($key, $default = NULL) {
    $result = $this->_cache->get($this->_prefix . $key);
    return ($result === FALSE) ? $default : unserialize($result);
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public function delete($key) {
    $this->_cache->delete($this->_prefix . $key);
    return TRUE;
  }

  /**
   * @return bool
   */
  public function flush() {
    // FIXME: Ideally, we'd map each prefix to a different 'hash' object in Redis,
    // and this would be simpler. However, that needs to go in tandem with a
    // more general rethink of cache expiration/TTL.

    $keys = $this->_cache->keys($this->_prefix . '*');
    $this->_cache->del($keys);
    return TRUE;
  }

  public function clear() {
    return $this->flush();
  }

}
