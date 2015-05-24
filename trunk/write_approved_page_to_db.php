<?php

// The url of the page the user is approving.
if( strrchr(htmlspecialchars($_GET['url']), '&')){
	return;
}
$url = $_GET['url'];
include 'Author-Approval.php';

// Code to insert data into the author_approval_jre_approved_pages_log table
global $wpdb;
$table_name = $wpdb->prefix . 'author_approval_jre_approved_pages_log';
global $current_user;
get_currentuserinfo();

// Getting page ID from URL, then using that to get page title
$urltemp = strstr($url, '=');
$urltemp = substr($urltemp, 1);
$urltemp = (int)$urltemp;
$title_of_page_to_be_duplicated = get_the_title( $urltemp );

$wpdb->insert( $table_name, array( 'approved_urls' => $url, 'page_title' => $title_of_page_to_be_duplicated, 'username' => $current_user -> display_name, 'is_page' => '1' ) );
echo 'Thanks for approving your page! The time, date, page url, and your username have been recorded. You will no longer see the approval or rejection links in your admin bar.'

?>
