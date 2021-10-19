<?php
namespace XAF\web\exception;

use XAF\exception\RequestError;

/**
 * Thrown when query parameters or the POST body does not meet formal requirements.
 *
 * NOT applicable for unknown request paths (use PageNotFound instead) or invalid data entered by the user
 * (display error in user space instead).
 *
 * Example use cases:
 * - Recived POST form data is missing required fields
 * - A GET parameter from a link (i. e. not entered by the user) is not expected data type - e. g.
 *   a page number parameter coded into forward/back links is not numeric
 * - A request is made which does not make sense in relation the the current state of the user session -
 *   e. g. a shop's checkout page is called with an empty shopping cart
 */
class BadRequest extends RequestError
{
}
