<p style="font-size:12pt; font-style:italic;">Please enter your reasons for rejecting the page below:</p>
<form method="post" name="author_approval_jre_form_for_page_rejection_emails" action="">
<textarea style="width:350px; height:200px;" id="rejected_pages_text_id" name="rejected_pages_text"></textarea>
</br>
<input type="submit" name="rejected_pages_submit" value="Submit"> 
</form>
</br>
</br>
<?php


if  (isset($_POST['rejected_pages_submit'])){

	// Get the message the author typed in the textarea
	$msg = filter_var($_POST['rejected_pages_text'], FILTER_SANITIZE_STRING);

	if  (!trim($msg) == ''){
		
		// use wordwrap() if lines are longer than 70 characters
		$msg = wordwrap($msg,70);

		// send email with URL of page being rejected
		$email = filter_var($_GET['rejectionemail'], FILTER_SANITIZE_EMAIL);
		$url = $_GET['rejectionurl'];
		$msg = $msg." - on ".$url;
		mail($email,"A Page Rejection",$msg);
		echo 'Your message has been sent. Thank you!';
		

	} else {

		echo 'Please enter a reason for rejecting the page!';

	}
} 
		
?>