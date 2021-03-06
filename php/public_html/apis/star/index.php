<?php

require_once dirname(__DIR__, 3) . "/vendor/autoload.php";
require_once dirname(__DIR__, 3) . "/Classes/autoload.php";
require_once("/etc/apache2/capstone-mysql/Secrets.php");
require_once dirname(__DIR__, 3) . "/lib/xsrf.php";
require_once dirname(__DIR__, 3) . "/lib/jwt.php";
require_once dirname(__DIR__, 3) . "/lib/uuid.php";

use GoGitters\ApciMap\Star;

/**
 * API for the Star class
 *
 * @author Lisa Lee
 */

//verify the session, start if not active
if(session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

//prepare an empty reply
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;

try {

	$secrets = new \Secrets("/etc/apache2/capstone-mysql/map.ini");
	$pdo = $secrets->getPdoObject();

	//determine which HTTP method was used
	$method = $_SERVER["HTTP_X_HTTP_METHOD"] ?? $_SERVER["REQUEST_METHOD"];

	//sanitize the search parameters
	$starUserId = filter_input(INPUT_GET, "starUserId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$starPropertyId = filter_input(INPUT_GET, "starPropertyId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();

		//gets a specific star associated based on its composite key
		if(($starPropertyId !== null) && ($starUserId !== null)) {
			$star = Star::getStarByStarPropertyIdAndStarUserId($pdo, $starPropertyId, $starUserId);

			if($star !== null) {
				$reply->data = $star;
			}
			//if star user id is present, get stars by user id
		} else if(empty($starUserId) === false) {
			$reply->data = Star::getStarByUserId($pdo, $starUserId)->toArray();

		} else {
			throw new InvalidArgumentException("incorrect search parameters ", 404);
		}

	} else if($method === "POST") {

		//decode the response from the front end
		$requestContent = file_get_contents("php://input");
		$requestObject = json_decode($requestContent);

		if(empty($requestObject->starUserId) === true) {
			throw (new \InvalidArgumentException("No user linked to the star", 405));
		}

		if(empty($requestObject->starPropertyId) === true) {
			throw (new \InvalidArgumentException("No property linked to the star", 405));
		}

		//enforce that the end user has a XSRF token.
		verifyXsrf();

		//enforce the end user has a JWT token
		validateJwtHeader();

		// enforce the user is signed in
		if(empty($_SESSION["user"]) === true) {
			throw(new \InvalidArgumentException("you must be logged in to star posts", 403));
		}

		$star = new Star($requestObject->starPropertyId, $_SESSION["user"]->getUserId());
		$star->insert($pdo);
		$reply->message = "starred property successful";

	} else if($method === "DELETE") {

		//enforce the end user has a XSRF token.
		verifyXsrf();

		//enforce the end user has a JWT token
		validateJwtHeader();

		//grab the star by its composite key
		$star = Star::getStarByStarPropertyIdAndStarUserId($pdo, $starPropertyId, $_SESSION["user"]->getUserId());
		if($star === null) {
			throw (new RuntimeException("Star does not exist"));
		}

		//enforce the user is signed in and only trying to edit their own star
		if(empty($_SESSION["user"]) === true || $_SESSION["user"]->getUserId()->toString() !== $star->getStarUserId()->toString()) {
			throw(new \InvalidArgumentException("You are not allowed to delete this property", 403));
		}

		//preform the actual delete
		$star->delete($pdo);

		//update the message
		$reply->message = "Star successfully deleted";
	}
	// if any other HTTP request is sent throw an exception
	 else {
	throw new \InvalidArgumentException("invalid http request", 400);
}
	//catch any exceptions that is thrown and update the reply status and message
} catch(\Exception | \TypeError $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
}

header("Content-type: application/json");
if($reply->data === null) {
	unset($reply->data);
}

// encode and return reply to front end caller
echo json_encode($reply);