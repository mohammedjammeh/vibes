<?php
	require_once 'core/ini.php';

	if (Session::exists('user')) {
		Redirect::to('index.php');
	}

	//Register/Login 
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//register
		if (isset($_POST['submitR'])) {
			if(Token::check($_POST['tokenR'], 'tokenR')) {
				$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
				$email = trim($_POST['email']);
				$usernameR = trim(filter_input(INPUT_POST, 'usernameR', FILTER_SANITIZE_STRING));
				$password01R = $_POST['password01R'];
				$password02R = $_POST['password02R'];

				if(!empty($name) && !empty($usernameR) && !empty($password01R) && !empty($password02R)) {
					if (strlen($name) < 5 || strlen($name) > 25) {
						$errorR = 'Please enter a name that is longer than 4 characters and less than 25.';
					} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$errorR = "Please enter a valid email address.";
					} elseif (strlen($usernameR) < 2 || strlen($usernameR) > 13) {
						$errorR = 'Please enter a username that is longer than 2 characters and shorter than 13';
					} elseif ($password01R !== $password02R) {
						$errorR = 'Please enter matching passwords that you will remember.';
					} elseif (strlen($password01R) < 8) {
						$errorR = 'Please enter a password which has 8 characters or more.';
					} else {

						$usernameSql = 'SELECT username FROM uservibes WHERE username = ?';
						$usernameQuery = $handler->prepare($usernameSql);
						$usernameQuery->bindParam(1, $usernameR, PDO::PARAM_STR);
						$usernameQuery->execute();

						if ($usernameQuery->fetch(PDO::FETCH_ASSOC)) {
							$errorR = 'Your choosen username has been taken. Please try another one.';
						} else {
							$newUserSql = 'INSERT INTO user (userType, email, name) VALUES (?, ?, ?)';
							$newUserQuery = $handler->prepare($newUserSql);  
							$newUserQuery->execute(array('vibes', $email, $name));

							$lastInsertedID = $handler->lastInsertId();

							$salt = Hash::salt(32);
							$hashedPassword = Hash::make($password01R, $salt);
							$registerSql = 'INSERT INTO uservibes (username, password, salt, userID) VALUES (?, ?, ?, ?)';
							$registerQuery = $handler->prepare($registerSql);
							$registerQuery->execute(array($usernameR, $hashedPassword, $salt, $lastInsertedID));

							$successR = 'You are now registered. Please log in to access your account.';
						}

					}
				} else {
					$errorR = "Please fill all fields to register.";
				}

			}
		}

		//login
		if (isset($_POST['submitL'])) {
			if(Token::check($_POST['tokenL'], 'tokenL')) { 
				$usernameL = trim(filter_input(INPUT_POST, 'usernameL', FILTER_SANITIZE_STRING));
				$password01L = filter_input(INPUT_POST, 'password01L', FILTER_SANITIZE_SPECIAL_CHARS);

				if(!empty($usernameL) && !empty($password01L)) {
					$usernameLSql = "SELECT * FROM uservibes WHERE username = ?";
					$usernameLQuery = $handler->prepare($usernameLSql);
					$usernameLQuery->bindParam(1, $usernameL, PDO::PARAM_STR);
					$usernameLQuery->execute();

					while ($row = $usernameLQuery->fetch(PDO::FETCH_ASSOC)) {
						$db_userID = $row['userID'];
						$db_username = $row['username'];
						$db_password = $row['password'];
						$db_salt = $row['salt'];
					}

					if(isset($db_username)) {
						if($usernameL == $db_username && $db_password === Hash::make($password01L, $db_salt)) {
							Session::put('user', $db_userID);
							Session::put('access_token', $db_username);
							Redirect::to('index.php');
						} else {
							$errorL = "Please enter a valid username and password.";
						}
					} else {
						$errorL = "Please enter a valid username and password.";
					}
				} else {
					$errorL = "Please fill all fields to login.";
				}
			}
		}
	}

	//facebook
	$redirectURL = 'http://vibes.com/fbCallback.php';
	$permissions = ['email'];
	$loginURL = $helper->getLoginUrl($redirectURL, $permissions);


	//google 
	$gLoginURL = $gClient->createAuthUrl();

?>

<!DOCTYPE html>
<html>
	<head lang="en">
		<meta charset="utf-8">
		<title>Log Into Vibes</title>
		<meta name="viewport" content="width=device-width,initial-scale=1.0">
		<link href='https://fonts.googleapis.com/css?family=Droid+Serif' rel='stylesheet' type='text/css'>
		<link href="https://fonts.googleapis.com/css?family=Roboto+Slab:100,300,400" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="css/style.css">
	</head>

	<body id="login">
		<header>
			<h1>Vibes</h1>
		</header>

		<section class="cf">

			<div>
				<form method="POST" name="loginForm" class="cf">
					<p>USE YOUR VIBES ACCOUNT</p>
					<?php
						if (isset($errorL)) {
							echo '<p class="error">' . $errorL . '</p>';
						}
					?>
					<input type="text" name="usernameL" placeholder="Username">
					<input type="password" name="password01L" placeholder="Password">
					<input type="hidden" name="tokenL" value="<?php echo Token::generate('tokenL')?>">
					<input type="submit" name="submitL" value="Login">

					<p>SOCIAL MEDIA LOGINS</p>

					<a href="<?php echo $loginURL; ?>">Facebook</a>
					<a href="<?php echo $gLoginURL; ?>">Google</a>
				</form>
			</div>



			<div>
				<form method="POST" name="registrationForm">
					<p>REGISTRATION</p>
					<?php
						if (isset($errorR)) {
							echo '<p class="error">' . $errorR . '</p>';
						} elseif (isset($successR)) {
							echo '<p class="success">' . $successR . '</p>';
						}
					?>
					<input type="text" name="name" placeholder="Full name" value="<?php if(isset($errorR) && isset($name)) { echo escape($name); } ?>">
					<input type="text" name="email" placeholder="Email" value="<?php if(isset($errorR) && isset($email)) { echo escape($email); } ?>">
					<input type="text" name="usernameR" placeholder="Username" value="<?php if(isset($errorR) && isset($usernameR)) { echo escape($usernameR); } ?>">
					<input type="password" name="password01R" placeholder="Password">
					<input type="password" name="password02R" placeholder="Repeat Password">
					<input type="hidden" name="tokenR" value="<?php echo Token::generate('tokenR')?>">
					<input type="submit" name="submitR" value="Register">
				</form>
			</div>
		</section>

		<footer>
			<p>&copy; 2018 Vibes.</p>
		</footer>
	</body>
</html>