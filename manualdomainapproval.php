<?php

//include("../../autoload.php");
include("../../../../autoload.php");
use Zendesk\API\HttpClient as ZendeskAPI;

$PROD = 0;

// $testPost->ticketId = 10583;
// $testPost->ticketBody = "----------------------------------------------
// \n\nJose Sanchez, Nov 24, 2020, 20:30\n\nLogin User Id: UuserID123\nLogin User Display Name: Jose Sanchez\nAccount Id: AccountID123\nAccount Name: Phony Acc Name\nAccount Alias: Acme\nAccount PhoneNumber: 123-123\nManaged Domain: phony.net\nLogin User Email: jose@phony.net\nOwner User Email: joseowner@phony.net";

//require 'vendor/autoload.php';


if($PROD){
	$subdomain = 'zoom';
	$token = 'x';
} else {

}

$username = "jose.sanchez@zoom.us";
$client = new ZendeskAPI($subdomain);
$client->setAuth('basic', ['username' => $username, 'token' => $token]);
$n = "\n";

function parseComment($comment) {
	$bodyArray = [];
	$assArray = [];
	$splitComment = explode("\n", $comment);
	foreach($splitComment as $key => $line) {
		
		if( strpos( $line, ': ' ) !== false) {
		    array_push($bodyArray, $line);
		}
	}

	foreach($bodyArray as $line) {
		$tempLine = explode(": ", $line);
		$assArray[ strtolower($tempLine[0]) ] = $tempLine[1];
	}

	return $assArray;
}

if(isset($_POST))  {

	echo "Found: " . json_encode($_POST) . "\n";
	$ticketId =  $_POST['ticketId'];
	$ticketBody = $_POST['ticketBody'];

	if($ticketId && $ticketId != "" && isset($ticketId) && isset($ticketBody) && $ticketBody != "") {

		//grab body and generate variables
		$parsedBody = parseComment($ticketBody);
		$userId = $parsedBody['login user id'];
		$userName = $parsedBody['login user display name'];
		$accountId = $parsedBody['account id'];
		$requestedDomain = $parsedBody['managed domain'];
		$userEmail = $parsedBody['login user email'];
		$ownerEmail = $parsedBody['owner user email'];

		//if any variables are not generated this will fail and will  not do comment
		if($userId && $accountId && $requestedDomain && $userEmail && $ownerEmail) {

			try{

				$ticketArray = [];
				$ticketArray['comment']->html_body = 'Login User Email: ' . $userEmail . '<br>Login User ID: <a href="https://op.zoom.us:8443/user/admin/info?id='. $userId .'">' . $userId . '</a><br>
					Login User Name: ' . $userName . '<br>
					Account ID: <a href="https://op.zoom.us:8443/account/admin?id=' . $accountId . '">' . $accountId . '</a><br>
					Account Owner Email: <a href="https://op.zoom.us:8443/user/admin/search?id=&email=' . $ownerEmail . '&username=&domain=&accountId=">' . $ownerEmail . '</a><br><br>
					Requested domain: <a href="https://op.zoom.us:8443/user/admin/search?id=&email=&username=&domain=' . $requestedDomain . '&accountId=">' . $requestedDomain . '</a> (<a href="https://op.zoom.us:8443/account/admin/feature?id=' . $accountId . '">Approve/Deny</a>)';
				$ticketArray['comment']->public = 'false' ;

				$query = $client()->users()->search(['query' => $userEmail]);
				if($query->count == 1){
					$ticketArray['requester_id'] = $query->users[0]->id;
				}

				if($userDomain == $requestedDomain || $ownerDomain == $requestedDomain) {
					$ticketArray['comment']->html_body = $ticketArray['comment']->html_body + 
					"<br><p style='color:green'>MATCH";
				} else {
					$ticketArray['comment']->html_body = $ticketArray['comment']->html_body + 
					"<br><p style='color:red'>MISMATCH";
				}

				$client->tickets()->update($ticketId, $ticketArray);

			} catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
				echo $e->getMessage();
			}

			$userDomain = explode("@", $userEmail)[1];
			$ownerDomain = explode("@", $ownerEmail)[1];

			$responseArray = [];
			$responseArray['comment']->public = "true";	
			
			//if the requested domain matches the owners or requesters send email 1
			if($userDomain == $requestedDomain || $ownerDomain == $requestedDomain) {
				$responseArray['comment']->html_body = "Hi there,\n\nPrior to approving this managed domain request, we want to confirm with you the desired results. \nWith the managed domain feature, any individual that signs up for a Zoom account with a matching e-mail domain (@" . $requestedDomain.") will automatically be added to your account under your management. In addition, existing users with this email domain will be presented with the option to be added to your account or change their email upon signing in. \n\nTherefore, all management of licenses, billing, etc for those who have chosen to be added as a user would fall under your account.\n\nWe like to confirm the desired results when we receive these requests since it can have an organization-wide impact.\n\nWe'll be standing by to hear how you'd like to proceed.\n\nBest,\nZoom Support Team";



			} else {
				$responseArray['comment']->html_body = "Hi There,\n\nThank you for contacting Zoom Technical Support!\n\nBefore we proceed with your associated domain request, could you please provide us domain ownership for (@" . $requestedDomain . ")? Anything that can be proof that you own that specific domain. You can do this a multitude of ways but a few examples would be(you can provide one of them):\n\n- An invoice showing when the domain/s was purchased\n- A screenshot inside the management portal of your domain host\n- Adding a .txt record to the domain with some information relating to your ticket with them (such as the ticket number).\n\nPlease confirm by replying to this e-mail so it can be approved.\n\nBest regards,\nZoom Support Team";
			}

			$client->tickets()->update($ticketId, $responseArray);
		}
		
		if(!$PROD){
			$myfile = fopen("post.txt", "w") or die("Unable to open file!");
			$txt = json_encode($parsedBody);
			fwrite($myfile, $txt);
			fclose($myfile);
		}

	}

}

else {
	echo "NOT FOUND!";
	if(!$PROD){
		$i =  file_get_contents('php://input');
		$myfile = fopen("post.txt", "w") or die("Unable to open file!");
		$txt = json_encode($i);
		fwrite($myfile, $txt);
		fclose($myfile);
	} else {
		echo "Script Failed";
	}

}


/*

{"ticketId":"10583","ticketBody"
:"----------------------------------------------
\n\nJose Sanchez, Nov 24, 2020, 20:30\n\nLogin User Id: UuserID123\nLogin User D
isplay Name: Jose Sanchez\nAccount Id: AccountID123\nAccount Name: Phony Acc Nam
e\nAccount Alias: Acme\nAccount PhoneNumber: 123-123\nManaged Domain: phony.net\
nLogin User Email: jose@phony.net\nOwner User Email: joseowner@phony.net"}
                                  
*/

//$i =  file_get_contents('php://input');


?>

