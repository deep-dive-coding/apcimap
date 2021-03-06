<?php

namespace GoGitters\ApciMap;
require_once(dirname(__DIR__) . "/vendor/autoload.php");

use Ramsey\Uuid\Uuid;

/**
 * Cross Section of a Registered User
 * @author Lindsey Atencio
 * @version 0.0.1
 *
 **/
Class User implements \JsonSerializable {
	use ValidateUuid;
/**
 * id for this User; this is the primary key
 * @var Uuid $userId
 **/

private
$userId;
/**
 * token handed out to verify that the profile is valid and not malicious.
 * @var $userActivationToken
 **/
private
$userActivationToken;
/**
 * email for this user; this is a unique index
 * @var string $userEmail
 **/
private
$userEmail;
/**
 * hash for profile password
 * @var $userHash
 **/
private
$userHash;
/**
 * username for this account
 **/
private
$userUsername;
/**
 * constructor for this Profile
 *
 * @param string|Uuid $newUserId of this user or null if a new user
 * @param string $newUserActivationToken activation token to safe guard against malicious accounts
 * @param string $newUserEmail string containing email
 * @param string $newUserHash string containing password hash
 * @param string $newUserUsername string containing new username
 * @throws \InvalidArgumentException if data types are not valid
 * @throws \RangeException if data values are out of bounds (e.g., strings too long, negative integers)
 * @throws \TypeError if a data type violates a data hint
 * @throws \Exception if some other exception occurs
 * @Documentation https://php.net/manual/en/language.oop5.decon.php
 **/
public function __construct($newUserId, $newUserActivationToken, $newUserEmail, $newUserHash, $newUserUsername) {
	try {
		$this->setUserId($newUserId);
		$this->setUserActivationToken($newUserActivationToken);
		$this->setUserEmail($newUserEmail);
		$this->setUserHash($newUserHash);
		$this->setUserUsername($newUserUsername);
	} catch(\InvalidArgumentException | \RangeException |\TypeError | \Exception $exception) {
		//determine what exception type was thrown
		$exceptionType = get_class($exception);
		throw(new $exceptionType($exception->getMessage(), 0, $exception));
	}
}

/**
 * accessor method for user id
 *
 * @return Uuid value of user id (or null if new User)
 **/
public function getUserId(): Uuid {
	return ($this->userId);
}

/**
 * mutator method for user id
 *
 * @param Uuid| string $newuserId value of new profile id
 * @throws \RangeException if $newUserId is not positive
 * @throws \TypeError if the user Id is not a Uuid
 **/
public
function setUserId($newUserId): void {
	try {
		$uuid = self::validateUuid($newUserId);
	} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
		$exceptionType = get_class($exception);
		throw(new $exceptionType($exception->getMessage(), 0, $exception));
	}
	// convert and store the user id
	$this->userId = $uuid;
}

/**
 * accessor method for user activation token
 *
 * @return string value of the activation token
 */
public
function getUserActivationToken(): ?string {
	return ($this->userActivationToken);
}
/**
 * mutator method for account activation token
 *
 * @param string $newUserActivationToken
 * @throws \InvalidArgumentException  if the token is not a string or insecure
 * @throws \RangeException if the token is not exactly 32 characters
 * @throws \TypeError if the activation token is not a string
 */
public
function setUserActivationToken(?string $newUserActivationToken): void {
	if($newUserActivationToken === null) {
		$this->userActivationToken = null;
		return;
	}
	$newUserActivationToken = strtolower(trim($newUserActivationToken));
	if(ctype_xdigit($newUserActivationToken) === false) {
		throw(new\RangeException("user activation is not valid"));
	}
	//make sure user activation token is only 32 characters
	if(strlen($newUserActivationToken) !== 32) {
		throw(new\RangeException("user activation token has to be 32"));
	}
	$this->userActivationToken = $newUserActivationToken;
}

/**
 * accessor method for email
 *
 * @return string value of email
 **/
public
function getUserEmail(): string {
	return $this->userEmail;
}

/**
 * mutator method for email
 *
 * @param string $newUserEmail new value of email
 * @throws \InvalidArgumentException if $newEmail is not a valid email or insecure
 * @throws \RangeException if $newEmail is > 128 characters
 * @throws \TypeError if $newEmail is not a string
 **/
public
function setUserEmail(string $newUserEmail): void {
	// verify the email is secure
	$newUserEmail = trim($newUserEmail);
	$newUserEmail = filter_var($newUserEmail, FILTER_VALIDATE_EMAIL);
	if(empty($newUserEmail) === true) {
		throw(new \InvalidArgumentException("user email is empty or insecure"));
	}
	// verify the email will fit in the database
	if(strlen($newUserEmail) > 128) {
		throw(new \RangeException("user email is too large"));
	}
	// store the email
	$this->userEmail = $newUserEmail;
}

/**
 * accessor method for userHash
 *
 * @return string value of hash
 */
public
function getUserHash(): string {
	return $this->userHash;
}

/**
 * mutator method for user hash password
 * @param string $newUserHash
 * @throws \InvalidArgumentException if the hash is not secure
 * @throws \RangeException if the hash is not 97 characters
 * @throws \TypeError if profile hash is not a string
 */
public
function setUserHash(string $newUserHash): void {
	//enforce that the hash is properly formatted
	$newUserHash = trim($newUserHash);
	if(empty($newUserHash) === true) {
		throw(new \InvalidArgumentException("user password hash empty or insecure"));
	}
	//enforce the hash is really an Argon hash
	$userHashInfo = password_get_info($newUserHash);
	if($userHashInfo["algoName"] !== "argon2i") {
		throw(new \InvalidArgumentException("user hash is not a valid hash"));
	}
	//enforce that the hash is exactly 97 characters.
	if(strlen($newUserHash) !== 97) {
		throw(new \RangeException("user hash must be 97 characters"));
	}
	//store the hash
	$this->userHash = $newUserHash;
}

/**Accessor method for userUsername
 * @return string value of username
 **/
Public
function getUserUsername(): string {
	return $this->userUsername;
}

/**
 * mutator method for user username
 *
 * @param string $newUserUsername
 * @throws \InvalidArgumentException  if the username is not a string or insecure
 * @throws \RangeException if the username is more than 32 characters
 * @throws \TypeError if the username is not a string
 */
public function setUserUsername(string $newUserUsername): void {
	$newUserUsername = trim($newUserUsername);
	$newUserUsername = filter_var($newUserUsername, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	if(empty($newUserUsername) === true) {
		throw(new \InvalidArgumentException("username is empty or insecure"));
	}
// verify the username will fit in the database
	if(strlen($newUserUsername) > 32) {
		throw(new \RangeException("username has too many characters"));
	}
// store the username
	$this->userUsername = $newUserUsername;
}
	/**
	 * gets the User by uuid
	 * @param \PDO $pdo $pdo PDO connection object
	 * @param  Uuid|string $userId user id to search for (the data type should be mixed/not specified)
	 * @return User|null User or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when a variable are not the correct data type
	 **/
	public
	static function getUserByUserId(\PDO $pdo, $userId): ?User {
		// sanitize the profile id before searching
		try {
			$userId = self::validateUuid($userId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT userId, userActivationToken, userEmail, userHash, userUsername FROM `user` WHERE userId = :userId";
		$statement = $pdo->prepare($query);
		// bind the user id to the place holder in the template
		$parameters = ["userId" => $userId->getBytes()];
		$statement->execute($parameters);
		// grab the user from mySQL
		try {
			$user = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$user = new User($row["userId"], $row["userActivationToken"], $row["userEmail"], $row["userHash"], $row["userUsername"]);
			}
		} catch(\Exception $exception) {
			// if the row couldn't be converted, rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return ($user);
	}

	/**
	 * get the user by user activation token
	 *
	 * @param string $userActivationToken
	 * @param \PDO object $pdo
	 * @return User|null User or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public
	static function getUserByUserActivationToken(\PDO $pdo, string $userActivationToken): ?User {
		//make sure activation token is in the right format and that it is a string representation of a hexadecimal
		$userActivationToken = trim($userActivationToken);
		if(ctype_xdigit($userActivationToken) === false) {
			throw(new \InvalidArgumentException("user activation token is empty or in the wrong format"));
		}
		//create the query template
		$query = "SELECT  userId, userActivationToken,userEmail, userHash, userUsername FROM `user` WHERE userActivationToken = :userActivationToken";
		$statement = $pdo->prepare($query);
		// bind the user activation token to the placeholder in the template
		$parameters = ["userActivationToken" => $userActivationToken];
		$statement->execute($parameters);
		// grab the user from mySQL
		try {
			$user = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$user = new User($row["userId"], $row["userActivationToken"], $row["userEmail"], $row["userHash"], $row["userUsername"]);
			}
		} catch(\Exception $exception) {
			// if the row couldn't be converted, rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return ($user);
	}

	/**
	 * gets the user by email
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param string $userEmail email to search for
	 * @return User|null User or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public
	static function getUserByUserEmail(\PDO $pdo, string $userEmail): ?User {
		// sanitize the email before searching
		$userEmail = trim($userEmail);
		$userEmail = filter_var($userEmail, FILTER_VALIDATE_EMAIL);
		if(empty($userEmail) === true) {
			throw(new \PDOException("not a valid email"));
		}
		// create query template
		$query = "SELECT userId, userActivationToken, userEmail, userHash, userUsername FROM `user` WHERE userEmail = :userEmail";
		$statement = $pdo->prepare($query);
		// bind the user id to the place holder in the template
		$parameters = ["userEmail" => $userEmail];
		$statement->execute($parameters);
		// grab the User from mySQL
		try {
			$user = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$user = new User($row["userId"], $row["userActivationToken"], $row["userEmail"], $row["userHash"], $row["userUsername"]);
			}
		} catch(\Exception $exception) {
			// if the row couldn't be converted, rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return ($user);
	}

	/**
	 * gets the user by userUsername
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param string $userUsername to search for
	 * @return User|null User or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getUserByUsername(\PDO $pdo, string $userUsername): \SPLFixedArray {
		// sanitize the username before searching
		$userUsername = trim($userUsername);
		$userUsername = filter_var($userUsername, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($userUsername) === true) {
			throw(new \PDOException("not a valid username"));
		}
		// create query template
		$query = "SELECT userId, userActivationToken, userEmail, userHash, userUsername FROM `user` WHERE userUsername = :userUsername";
		$statement = $pdo->prepare($query);
		// bind the user id to the place holder in the template
		$parameters = ["userUsername" => $userUsername];
		$statement->execute($parameters);

		// grab the User from mySQL
		$users = new \SPLFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);

		while (($row = $statement->fetch()) !== false) {
			try {
				$user = new User($row["userId"], $row["userActivationToken"], $row["userEmail"], $row["userHash"], $row["userUsername"]);
				$users[$users->key()] = $user;
				$users->next();
			} catch(\Exception $exception) {
				// if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return ($users);
	}
	/**
	 * inserts this user into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 **/
	public function insert(\PDO $pdo): void {
		// create query template
		$query = "INSERT INTO `user` (userId, userActivationToken, userEmail, userHash, userUsername) VALUES(:userId, :userActivationToken, :userEmail, :userHash, :userUsername)";
		$statement = $pdo->prepare($query);
		// bind the member variables to the place holders in the template
		$parameters = ["userId" => $this->userId->getBytes(), "userActivationToken"=>$this->userActivationToken, "userEmail" => $this->userEmail, "userHash"=> $this->userHash, "userUsername"=>$this->userUsername];
		$statement->execute($parameters);
	}

	/**
	 * deletes this user from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 **/
	public function delete(\PDO $pdo): void {
		// create query template
		$query = "DELETE FROM user WHERE userId = :userId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the placeholders in the template
		$parameters = ["userId" => $this->userId->getBytes(),];
		$statement->execute($parameters);
	}
	/**
	 * updates this User from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 **/
	public function update(\PDO $pdo): void {
		// create query
		$query = "UPDATE user SET userActivationToken = :userActivationToken, userEmail = :userEmail, userHash = :userHash, userUsername = :userUsername WHERE userId = :userId";
		$statement = $pdo->prepare($query);
		$parameters = ["userId"=> $this->userId->getBytes(), "userActivationToken"=> $this->userActivationToken, "userEmail"=> $this->userEmail, "userHash"=> $this->userHash, "userUsername"=> $this->userUsername];
		$statement->execute($parameters);
	}

	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array resulting state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);
		$fields["userId"] = $this->userId->toString();
		unset($fields["userActivationToken"]);
		unset($fields["userHash"]);
		return ($fields);
	}
}
