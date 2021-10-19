<?php
namespace XAF\web\session;

interface Session
{
	/**
	 * create new session
	 */
	public function start();

	/**
	 * open an existing session with the specified token
	 *
	 * @param string $token alphanumeric session token
	 * @return bool whether the session was found and is active
	 */
	public function continueIfExists( $token );

	/**
	 * Open a session in "passive mode" from an outside context, i.e. not for persistence of the
	 * current client state in a web app.
	 *
	 * Does not increase access count or last access timestamp.
	 * 
	 * Does *not* wait for the session lock but return fale if the session is locked!
	 *
	 * Used to e.g. access an expired session for logging before the session is garbage-collected.
	 *
	 * @param string $token alphanumeric session token
	 * @return bool whether the session was opened (session found and not locked)
	 */
	public function openPassive( $token );

	/**
	 * @return string the alphanumeric session token to be set as a cookie or query string parameter
	 */
	public function getToken();

	/**
	 * @return bool
	 */
	public function isOpen();

	/**
	 * @return bool whether the session was created in this request
	 */
	public function isNew();

	/**
	 * @return int number of times the session has been opened (session creation counts, too)
	 */
	public function getRequestCount();

	/**
	 * @return int timestamp of last client access to the session
	 */
	public function getLastAccessTs();

	/**
	 * set session data element
	 *
	 * @param string $key
	 * @param mixed $data
	 */
	public function setData( $key, $data );

	/**
	 * delete element from session data
	 *
	 * @param string $key
	 */
	public function unsetData( $key );

	/**
	 * @param string $key
	 * @return mixed value of session data element or null if $key does not exist
	 */
	public function getData( $key );

	/**
	 * get complete session data for debugging purposes
	 *
	 * @return array
	 */
	public function exportData();

	/**
	 * Set a flash element - a value which will only be available in the next request
	 * and then discarded automatically
	 *
	 * @param string $key
	 * @param mixed $data
	 */
	public function setFlash( $key, $data );

	/**
	 * Get a flash element - a value set during the last request
	 *
	 * @param string $key
	 * @return mixed null if $key does not exist
	 */
	public function getFlash( $key );

	/**
	 * close the session and store the session data
	 */
	public function close();

	/**
	 * utimately terminate the session - usually called when a user logs out
	 * or when session garbage collection cleans up the session
	 */
	public function end();
}
