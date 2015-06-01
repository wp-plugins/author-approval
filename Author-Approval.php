<?php
   /*
   Plugin Name:Author Approval
   Plugin URI: http://www.jakerevans.com
   Description: A plugin that provides an automated way to approve site content.
   Version: 1.0
   Author: Jake Evans
   Author URI: http://www.jakerevans.com
   License: GPL2
   */   

// All script/style hooks/activations/registrations
register_activation_hook( __FILE__, 'author_approval_jre_create_tables' );
register_deactivation_hook( __FILE__, 'author_approval_jre_delete_tables' );
add_action( 'init', 'author_approval_jre_register_approved_pages_table', 1 );
add_action( 'switch_blog', 'author_approval_jre_register_approved_pages_table' );
add_action( 'wp_loaded', 'author_approval_jre_approval_rejection_creation_and_execution' );
add_action('admin_menu', 'author_approval_jre_plugin_menu');
// Adding the php function that handles the AJAX request for the approval link
add_action( 'wp_ajax_my_action_approval', 'author_approval_jre_page_approval_callback' );
// This will add the JavaScript for the approval link into the footer.
add_action( 'admin_footer', 'author_approval_jre_page_approval_javascript' );
// Adding the php function that handles the AJAX request for the rejection link
add_action( 'wp_ajax_my_action_rejection', 'author_approval_jre_page_rejection_callback' );
// This will add the JavaScript for the rejection link into the footer.
add_action( 'admin_footer', 'author_approval_jre_page_rejection_javascript' );


// The function to add the Settings page 
function author_approval_jre_plugin_menu() {
    add_options_page('Author Approval Options', 'Author Approval', 'manage_options', 'author_approval_jre-plugin', 'author_approval_jre_plugin_page_function');
}

// The function to give the Settings page a little functionality
    function author_approval_jre_plugin_page_function(){
        ?><div class="author_approval_jre_options_page_heading">
        <p style="font-size:18pt;">Author Approval</p>Written by Jake Evans</br></br></br>
	</div>
	<p>Enter the E-mail address you'd like page rejections to be forwarded to:</p>
	<form method="post" name="author_approval_jre_form_for_page_approvals" action="">
        <input type="text" name="rejection_email_address_text" value=""> 
	<input type="submit" name="rejection_email_address_button" value="Submit E-mail Address">
	</form>
	<?php

	if  (isset($_POST['rejection_email_address_text'])){
		 	$email = filter_var($_POST['rejection_email_address_text'], FILTER_SANITIZE_EMAIL);
			if (empty($email)) {
				?><p style="color:red;">You didn't enter an E-mail address! Enter an E-mail address and try again!</p><?php
			} else {
				
				if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
  					//store the e-mail address in the DB
					update_option( 'page_rejection_email', $email, '', 'yes' );
					?></br><?php
					echo 'All page rejection e-mails will be sent to '.$email.'.';
				} else {
					?><p style="color:red;">You didn't enter a valid e-mail address... try again!</p><?php
				}
			}	
	}
	


	?>
	</br></br>
	<p>Click below to view the pages that have been approved by the Authors:</p>
        <form method="post" name="author_approval_jre_form_for_page_approvals" action="">
        <input type="submit" name="approved_pages" value="View Approved Pages"> 
        </br></br>
        <?php

if  (isset($_POST['approved_pages'])){
    if( strrchr(htmlspecialchars($_POST['approved_pages']), '&')){
            return;
    }
    // Code that shows the pages that have already been approved
    global $wpdb;
    $approved_pages = $wpdb->get_results("SELECT * FROM $wpdb->author_approval_jre_approved_pages_log WHERE temp_storage_for_user_id is null ");
    if (!empty($approved_pages)) {
        
        // Create the list of approved pages
        foreach($approved_pages as $pages_approved){
            echo $pages_approved->username;
            echo ' approved ';
            ?><span style="font-style:italic; font-size:12pt;"><b><?php
            echo $pages_approved->page_title;
            ?></span></b><?php
            echo ' (';
            echo $pages_approved->approved_urls;
            echo ') ';
            echo 'on ';
            echo $pages_approved->date;
            ?> </br> <?php
        }

    // Create a 'Reset Approved Pages' button
    ?>
    </br>
    <input type="submit" name="reset_approved_pages" value="Reset Approved Pages"> (Warning, this will reset ALL currently approved pages, there is no backup!)
    </br></br>
    <?php
    } else{
        ?><p style="color: red; position: relative; bottom: 15px;">No one has approved any pages yet!</p><?php
    }

    

}


// Resets the pages that have already been approved
if ( isset($_POST['reset_approved_pages']) ) {
    if( strrchr(htmlspecialchars($_POST['reset_approved_pages']), '&')){
        return;
    }
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->author_approval_jre_approved_pages_log WHERE is_page = %s",'1'));
    echo 'All approved pages have been reset!';
}


?> </form> <?php
    // Creating the Drop-Down Menu of all users
    $users = get_users( );
    if ($users) { 
        ?>
        </br></br>
        <div class="initial_text"><p>Select a user from the drop-down menu below to assign pages to:</p></div>
        <form method="post" name="author_approval_jre_form_for_authors" action="">
        <select id="author_approval_jre_author_dropdown" name="my_dropdown" onchange="author_approval_jre_form_for_authors.submit()">
        <option  value="default">Select an Author</option>
            <?php foreach ($users as $user) {
                echo '<option  value="' .$user->ID .'">'.$user->user_nicename .'</option>';
            } ?>
            </select>
            <?php

        // When the drop-down menu is set, record the user id in the custom database table
        if  (isset($_POST['my_dropdown'])){

            if( strrchr(htmlspecialchars($_POST['my_dropdown']), '&')){
                return;
            }

            if(preg_match('/[A-Z]+[a-z]/', $_POST['my_dropdown'])){
                return; 
            }

            $path = $_SERVER['DOCUMENT_ROOT'];
            $user_id_for_authorship = $_POST['my_dropdown'];

            if( strrchr(htmlspecialchars($_POST['my_dropdown']), '&')){
                return;
            }

            if(preg_match('/[A-Z]+[a-z]/', $_POST['my_dropdown'])){
                return; 
            }

            $userdata = get_userdata( $user_id_for_authorship );
    
            global $wpdb;
            $table_name = $wpdb->prefix . 'author_approval_jre_approved_pages_log';
            $wpdb->insert( $table_name, array( 'temp_storage_for_user_id' => $user_id_for_authorship ) );
        }

        /* When the drop-down menu is set AND the "Add Checked Pages" button has NOT been clicked
        * create checkboxes next to a list of all pages the selected user is NOT an author of
        */
        if ((isset($_POST['my_dropdown'])) && (!isset($_POST['add_checked_pages']))) {
        
            if( strrchr(htmlspecialchars($_POST['my_dropdown']), '&')){
                return;
            }

            if(preg_match('/[A-Z]+[a-z]/', $_POST['my_dropdown'])){
                return; 
            }

            $user = get_userdata( $_POST['my_dropdown'] );
            $checkfield = array();
            $pages = get_pages(); ?>     
            <div class="author_approval_jre_all_pages_title" style="margin-top:30px; margin-bottom: 10px; "> <?php echo $user->display_name ?> can be set as the Author of these pages below:</div>
            <?php
            foreach ( $pages as $page ) {
            // If the userid from 'my_dropdown' is not the author of the page, then print the checkbox and page
                if(($page->post_author) != ($_POST['my_dropdown'])){
                    ?>
                    <input id="page_<?php echo $page->ID; ?>" type="checkbox" name="checkfield[]" value="<?php echo $page->ID; ?>" 
                    <?php 
                    if ( in_array($page->ID, (array) $checkfield) ) {
                        ?> checked  <?php 
                    } ?> /><label for="page_<?php echo $page->ID; ?>"><?php echo $page->post_title; ?></label> <br> 
                    <?php 
                }
            }

            $path = $_SERVER['DOCUMENT_ROOT'];
            $user_id_for_authorship = $_POST['my_dropdown'];
            $userdata = get_userdata( $user_id_for_authorship );
    
            global $wpdb;
            $table_name = $wpdb->prefix . 'author_approval_jre_approved_pages_log';
            $wpdb->insert( $table_name, array( 'temp_storage_for_user_id' => $user_id_for_authorship ) );
    
            ?>
            </br>
            <input type="submit" name="add_checked_pages" value="Add Checked Pages"> 
            <?php
        }


        // If the "Add Checked Pages" button has been clicked, make the selected user the author of the checked pages
        if ( isset($_POST['add_checked_pages']) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'author_approval_jre_approved_pages_log';
            $lastid = $wpdb->insert_id;
            $wpdb->delete( $table_name, array( 'ID' => $lastid ) );
            $lastid_for_reading = (($wpdb->insert_id)-1);
            $thepost = $wpdb->get_row("SELECT * FROM $wpdb->author_approval_jre_approved_pages_log WHERE ID = $lastid_for_reading");    
            $selected_users_id = $thepost->temp_storage_for_user_id;
	
	    if(isset($_POST['checkfield'])){
           	foreach($_POST['checkfield'] as $pages_checked){
    
                	$my_post = array(
                        	'ID'           => $pages_checked,
                        	'post_author'   => $selected_users_id
                	);
                	wp_update_post($my_post);
            	}
	   

            	$pages = get_pages('authors='.$selected_users_id); 
            	$user = get_userdata( $selected_users_id );
            	?> </br><p><?php echo $user->display_name ?> is now the Author of all the pages below:</p> <?php 
            	foreach($pages as $authors_pages){
                	echo $authors_pages->post_title;
                	?> <br> <?php
            	}

	    } else { 
		?> <p style="color:red;"> You didn't check any pages! Select an Author and try again!</p> <?php
	    }
        }
    }
?>
</form>
<?php
}


// Function to add table name to the global $wpdb
function author_approval_jre_register_approved_pages_table() {
    global $wpdb;
    $wpdb->author_approval_jre_approved_pages_log = "{$wpdb->prefix}author_approval_jre_approved_pages_log";
}

// Code for creating the database table for page approvals. This is created once upon plugin activation.
function author_approval_jre_create_tables() {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    global $wpdb;
    global $charset_collate;
    // Call this manually as we may have missed the init hook
    author_approval_jre_register_approved_pages_table();

    // Creating the table
    $sql_create_table = "CREATE TABLE {$wpdb->author_approval_jre_approved_pages_log} (
        username varchar(255),
        ID bigint(255) auto_increment,
        page_title varchar(255) NOT NULL default 'updated',
        approved_urls varchar(255) NOT NULL default 'updated',
        date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        temp_storage_for_user_id bigint(255),
        is_page int(1),
            PRIMARY KEY  (ID),
            KEY approved_urls (approved_urls)
        ) $charset_collate; ";
    dbDelta( $sql_create_table );
}

// Code for deleting author_approval_jre_approved_pages_log table upon deactivation of plugin
function author_approval_jre_delete_tables() {
    global $wpdb;
        $table = $wpdb->prefix."author_approval_jre_approved_pages_log";
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

function author_approval_jre_approval_rejection_creation_and_execution(){
    $plugin_url = plugins_url( '/', __FILE__ );
    // Getting user's role
    global $current_user;
    get_currentuserinfo();
    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);

    // If user's role is "Author", write logic to create APPROVAL and REJECTION admin bar links
    if($user_role == 'author'){
        
        $role = get_role( 'author' );
        $role->add_cap( 'edit_pages' );

        // If user is currently editing a page, create and display APPROVAL and REJECTION admin bar links
        $current_url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        if(strstr($current_url, 'post.php')){ 
            $pos = strpos($current_url, '&a');
            $pos = substr($current_url, 0, $pos);

            //query the database for this URL
            global $wpdb;
            $table = $wpdb->prefix."author_approval_jre_approved_pages_log";
            $url_column = $wpdb->get_results( "SELECT approved_urls FROM $table");
            
            // Checking to see if URL is already in database. If so, page has been approved already. Quit function, never create approve/reject links in admin bar. 
            foreach($url_column as $stored_url){
                $stored_url = serialize($stored_url);
                if(strstr($stored_url, $pos)){
                    echo 'this page has already been approved';
                    return;
                }
            }

                // This adds the Approval link in the admin bar
                add_action( 'admin_bar_menu', 'author_approval_jre_toolbar_link_to_approve', 999 );
                function author_approval_jre_toolbar_link_to_approve( $wp_admin_bar ) {
                    $args = array(
                        'id'    => 'approve_this_page',
                        'title' => 'Approve This Page',
                        'href'  => '#'
                        );
                    $wp_admin_bar->add_node( $args );
                }

                // This adds the Rejection link in the admin bar
                add_action( 'admin_bar_menu', 'author_approval_jre_toolbar_link_to_reject', 999 );
                function author_approval_jre_toolbar_link_to_reject( $wp_admin_bar ) {
                    $args = array(
                        'id'    => 'reject_this_page',
                        'title' => 'Reject This Page',
                        'href'  => '#',
                        'meta'  => array( 'class' => 'author_approval_jre_toolbar_link_to_reject' )
                    );
                    $wp_admin_bar->add_node( $args );
                }
            }
    }
}

// The JavaScript to be added to the footer for the approval link
function author_approval_jre_page_approval_javascript() { ?>
    <script type="text/javascript" >
    jQuery(document).ready(function($) {

        $(document).on("click","#wp-admin-bar-approve_this_page",function() {
            var data = {
                'action': 'my_action_approval', // This is required, not sure why, investigate.
                'url': document.URL
            };

            // This gives the php function below access to the javascript data variable and handles the response of the php callback function below.
            $.post(ajaxurl, data, function(response) {
                alert(response);
                location.reload();
            });
        });
    });
    </script> <?php
}

// The php function itself that handles the AJAX request for the approval link
function author_approval_jre_page_approval_callback() {

    // The url of the page the user is approving.
    $url = filter_var($_POST['url'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

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

    // Inserting the approval info into the database
    $wpdb->insert( $table_name, array( 'approved_urls' => $url, 'page_title' => $title_of_page_to_be_duplicated, 'username' => $current_user -> display_name, 'is_page' => '1' ) );

    echo 'Thanks for approving your page! The time, date, page url, and your username have been recorded. You will no longer see the approval or rejection links in your admin bar.';

    wp_die(); // this is required to terminate immediately and return a proper response
}







// The JavaScript to be added to the footer for the rejection link
function author_approval_jre_page_rejection_javascript() { ?>
    <script type="text/javascript" >
    jQuery(document).ready(function($) {

        $(document).on("click","#wp-admin-bar-reject_this_page",function() {
            var data2 = {
                'action': 'my_action_rejection', // This is required, not sure why, investigate.
                'url': document.URL
            };

            // This gives the php function below access to the javascript data variable and handles the response of the php callback function below.
            $.post(ajaxurl, data2, function(response) {

		  var number = response.search("plugins") + 7;
		  var page_rejection_url_number_begin = response.search("thisistoseperatetherejectionpage");
		  var page_rejection_url_number_end = response.search("thisistoseperatetherejectionpage")+32;
		  var email = response.slice(number, page_rejection_url_number_begin);

		  var path_to_php_file = response.slice(0, number)+"/author-approval/rejection-email-popup.php";
		  var page_rejection_url = response.slice(page_rejection_url_number_end);
		  window.open(path_to_php_file+"?rejectionemail="+email+"&rejectionurl="+page_rejection_url, "", "width=400, height=400");
	
            });
        });
    });
    </script> <?php
}





// The php function itself that handles the AJAX request for the rejection link
function author_approval_jre_page_rejection_callback() {

  	// The url of the page the user is approving.
    $url = filter_var($_POST['url'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   
	// Getting page ID from URL, then using that to get page title
   	$urltemp = strstr($url, '=');
    $urltemp = substr($urltemp, 1);
    $urltemp = (int)$urltemp;
    $link_to_rejected_page = get_permalink( $urltemp );



	global $wpdb;
	$stored_rejection_email = get_option( 'page_rejection_email');
	echo plugins_url().$stored_rejection_email.'thisistoseperatetherejectionpage'.$link_to_rejected_page;

    	wp_die(); // this is required to terminate immediately and return a proper response
}






?>