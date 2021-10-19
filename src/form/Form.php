<?php
namespace XAF\form;

use ArrayAccess;

/**
 * A Form object represents structured data.
 *
 * Each form instance has a schema, consisting of a tree of field definitions including data types,
 * validations rules and default values.
 *
 * Data can be imported and exported as a nested hash. Imported data can be validated against the schema.
 *
 * Error information (from validation or added from the outside) is kept for every field as well as globally.
 * Error information consists of language-independent error codes plus optional parameters (as provided by
 * the validation subsystem).
 *
 * A Form object is meant to be passed around in an application.
 *
 * The primary use is for HTML forms:
 * - The form is populated with request data, domain model data or defaults.
 * - When populated from outside data, it is normally validated.
 * - Either its values are exported and passed to the domain model or it is passed to the view
 *   layer for display of an HTML form with error messages (if any).
 *
 * But it could as well be used to hold and validate data from a parsed XML document, decoded JSON string etc.
 *
 * A form object is NOT responsible or capable of generating a form representation (like HTML form elements).
 * It does not care about specific field types (like HTML form field types) associated with it's values.
 *
 * It implements the ArrayAccess interface to provide array-like access to its fields.
 */
interface Form extends ArrayAccess
{
	/**
	 * Set all fields to default values
	 */
	public function populateWithDefaults();

	/**
	 * populate form with request or model data
	 *
	 * @param array $values
	 * @return bool Whether the imported values contained at least one defined field key - when
	 *     importing HTTP request data, this can be used to detect whether fields for this form
	 *     were actually received from the client
	 */
	public function importValues( array $values );

	/**
	 * Validate all fields - failures are set as error keys of the fields or the form's global error key
	 *
	 * @return bool true if no validation error occurred
	 */
	public function validate();

	/**
	 * Export all field's values as an associative array
	 * Usually called to transfer received data to the model
	 *
	 * @return array
	 */
	public function exportValues();

	/**
	 * Set the received flag to indicate the form was populated from request data
	 */
	public function setReceived();

	/**
	 * @return bool Whether the form was populated from request data
	 */
	public function wasReceived();

	public function hasError();

	/**
	 * @param string|null $errorKey
	 * @param array|null $errorInfo
	 */
	public function setGlobalError( $errorKey, $errorInfo = null );

	public function hasGlobalError();

	public function getErrorKey();

	public function getErrorInfo();
}
