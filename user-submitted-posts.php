<?php 
/*
	Plugin Name: User Submitted Posts
	Plugin URI: https://perishablepress.com/user-submitted-posts/
	Description: Enables your visitors to submit posts and images from anywhere on your site.
	Tags: submit, public, share, upload, images, posts, users, user-submit, community, front-end, submissions
	Author: Jeff Starr
	Author URI: http://monzilla.biz/
	Donate link: http://m0n.co/donate
	Contributors: specialk
	Requires at least: 3.9
	Tested up to: 4.2
	Stable tag: trunk
	Version: 20150507
	Text Domain: usp
	Domain Path: /languages/
	License: GPL v2 or later
*/

if (!defined('ABSPATH')) die();

$usp_wp_vers = '3.9';
$usp_version = '20150507';
$usp_plugin  = __('User Submitted Posts', 'usp');
$usp_options = get_option('usp_options');
$usp_path    = plugin_basename(__FILE__); // '/user-submitted-posts/user-submitted-posts.php';
$usp_logo    = plugins_url() . '/user-submitted-posts/images/usp-logo.png';
$usp_pro     = plugins_url() . '/user-submitted-posts/images/usp-pro.png';
$usp_homeurl = 'https://perishablepress.com/user-submitted-posts/';

$usp_post_meta_IsSubmission   = 'is_submission';
$usp_post_meta_SubmitterIp    = 'user_submit_ip';
$usp_post_meta_Submitter      = 'user_submit_name';
$usp_post_meta_SubmitterUrl   = 'user_submit_url';
$usp_post_meta_SubmitterEmail = 'user_submit_email';
$usp_post_meta_Image          = 'user_submit_image';

// include template functions
include ('library/template-tags.php');

// i18n
function usp_i18n_init() {
	load_plugin_textdomain('usp', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'usp_i18n_init');

// require minimum version of WordPress
function usp_require_wp_version() {
	global $wp_version, $usp_path, $usp_plugin, $usp_wp_vers;
	if (version_compare($wp_version, $usp_wp_vers, '<')) {
		if (is_plugin_active($usp_path)) {
			deactivate_plugins($usp_path);
			$msg =  '<strong>' . $usp_plugin . '</strong> ' . __('requires WordPress ', 'usp') . $usp_wp_vers . __(' or higher, and has been deactivated!', 'usp') . '<br />';
			$msg .= __('Please return to the ', 'usp') . '<a href="' . admin_url() . '">' . __('WordPress Admin area', 'usp') . '</a> ' . __('to upgrade WordPress and try again.', 'usp');
			wp_die($msg);
		}
	}
}
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
	add_action('admin_init', 'usp_require_wp_version');
}

// add new post status
add_filter ('post_stati', 'usp_addNewPostStatus');
function usp_addNewPostStatus($postStati) {
	$postStati['submitted'] = array(__('Submitted', 'usp'), __('User Submitted Posts', 'usp'), _n_noop('Submitted', 'Submitted'));
	return apply_filters('usp_post_status', $postStati);
}

// add submitted status clause
add_action ('parse_query', 'usp_addSubmittedStatusClause');
function usp_addSubmittedStatusClause($wp_query) {
	global $pagenow, $usp_post_meta_IsSubmission;
	if (isset($_GET['user_submitted']) && $_GET['user_submitted'] == '1') {
		if (is_admin() && $pagenow == 'edit.php') {
			set_query_var('meta_key', $usp_post_meta_IsSubmission);
			set_query_var('meta_value', 1);
			set_query_var('post_status', 'pending');
		}
	}
}

// check for submitted post
add_action ('parse_request', 'usp_checkForPublicSubmission');
function usp_checkForPublicSubmission() {
	global $usp_options;
	if (isset($_POST['user-submitted-post'], $_POST['usp-nonce']) && !empty($_POST['user-submitted-post']) && wp_verify_nonce($_POST['usp-nonce'], 'usp-nonce')) {
		
		$title = 'User Submitted Post';
		if (isset($_POST['user-submitted-title']) && $usp_options['usp_title'] == 'show') 
			$title = sanitize_text_field($_POST['user-submitted-title']);
		
		$files = array();
		if (isset($_FILES['user-submitted-image'])) $files = $_FILES['user-submitted-image'];
		
		$ip = 'undefined';
		if (isset($_SERVER['REMOTE_ADDR'])) $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
		
		$author = ''; $url = ''; $email = ''; $tags = ''; $captcha = ''; $verify = ''; $content = ''; $category = '';
		
		if (isset($_POST['user-submitted-name']))     $author   = sanitize_text_field($_POST['user-submitted-name']);
		if (isset($_POST['user-submitted-url']))      $url      = esc_url($_POST['user-submitted-url']);
		if (isset($_POST['user-submitted-email']))    $email    = sanitize_email($_POST['user-submitted-email']);
		if (isset($_POST['user-submitted-tags']))     $tags     = sanitize_text_field($_POST['user-submitted-tags']);
		if (isset($_POST['user-submitted-captcha']))  $captcha  = sanitize_text_field($_POST['user-submitted-captcha']);
		if (isset($_POST['user-submitted-verify']))   $verify   = sanitize_text_field($_POST['user-submitted-verify']);
		if (isset($_POST['user-submitted-content']))  $content  = stripslashes($_POST['user-submitted-content']);
		if (isset($_POST['user-submitted-category'])) $category = intval($_POST['user-submitted-category']);
		
		$result = usp_createPublicSubmission($title, $files, $ip, $author, $url, $email, $tags, $captcha, $verify, $content, $category);
		
		$post_id = false; 
		if (isset($result['id'])) $post_id = $result['id'];
		
		$error = false;
		if (isset($result['error'])) $error = array_filter(array_unique($result['error']));
		
		if ($post_id) {
			$redirect = empty($usp_options['redirect-url']) ? $_SERVER['REQUEST_URI'] : $usp_options['redirect-url'];
			if (!empty($_POST['redirect-override'])) $redirect = sanitize_text_field($_POST['redirect-override']);
			$redirect = remove_query_arg(array('usp-error'), $redirect);
			$redirect = add_query_arg(array('success' => 1, 'post_id' => $post_id), $redirect);
			do_action('usp_submit_success', $redirect);
		} else {
			if ($error) {
				$e = implode(',', $error);
				$e = trim($e, ',');
			} else {
				$e = 'error';
			}
			if (!empty($_POST['redirect-override'])) {
				$redirect = sanitize_text_field($_POST['redirect-override']);
				$redirect = remove_query_arg(array('success', 'post_id'), $redirect);
				$redirect = add_query_arg(array('usp-error' => $e), $redirect);
			} else {
				$redirect = sanitize_text_field($_SERVER['REQUEST_URI']);
				$redirect = remove_query_arg(array('success', 'post_id'), $redirect);
				$redirect = add_query_arg(array('usp-error' => $e), $redirect);
			}
			do_action('usp_submit_error', $redirect);
		}
		wp_redirect(esc_url_raw($redirect));
		exit();
	}
}

// set attachment as featured image
if (!current_theme_supports('post-thumbnails')) {
	add_theme_support('post-thumbnails');
	// set_post_thumbnail_size(130, 100, true); // width, height, hard crop
}
function usp_display_featured_image() {
	global $post, $usp_options;
	if (is_object($post) && usp_is_public_submission($post->ID)) {
		if ((!has_post_thumbnail()) && ($usp_options['usp_featured_images'] == 1)) {
			$attachments = get_posts(array(
				'post_type' => 'attachment', 
				'post_mime_type'=>'image', 
				'posts_per_page' => 0, 
				'post_parent' => $post->ID, 
				'order'=>'ASC'
			));
			if ($attachments) {
				foreach ($attachments as $attachment) {
					set_post_thumbnail($post->ID, $attachment->ID);
					break;
				}
			}
		}
	}
}
add_action('wp', 'usp_display_featured_image');

// display meta box with user info
function usp_add_meta_box() {
	if (usp_is_public_submission()) {
		$screens = array('post', 'page');
		foreach ($screens as $screen) {
			add_meta_box('usp_section_id', __('User Submitted Post Info', 'usp'), 'usp_meta_box_callback', $screen);
		}
	}
}
add_action('add_meta_boxes', 'usp_add_meta_box');

function usp_meta_box_callback($post) {
	if (usp_is_public_submission()) {
		wp_nonce_field('usp_meta_box_nonce', 'usp_meta_box_nonce');
		
		$name  = get_post_meta($post->ID, 'user_submit_name', true);
		$email = get_post_meta($post->ID, 'user_submit_email', true);
		$url   = get_post_meta($post->ID, 'user_submit_url', true);
		$ip    = get_post_meta($post->ID, 'user_submit_ip', true); 
		
		if (!empty($name) || !empty($email) || !empty($url) || !empty($ip)) {
			echo '<ul style="margin-left:24px;list-style:square outside;">';
			if (!empty($name))  echo '<li>'. __('Submitter Name: ', 'usp')  . $name  .'</li>';
			if (!empty($email)) echo '<li>'. __('Submitter Email: ', 'usp') . $email .'</li>';
			if (!empty($url))   echo '<li>'. __('Submitter URL: ', 'usp')   . $url   .'</li>';
			if (!empty($ip))    echo '<li>'. __('Submitter IP: ', 'usp')    . $ip    .'</li>';
			echo '</ul>';
		}
	}
}

// js vars
function usp_js_vars() { 
	global $usp_options; 
	
	$usp_response = $usp_options['usp_response']; 
	$include_js   = $usp_options['usp_include_js']; 
	$display_url  = $usp_options['usp_display_url'];
	$usp_casing   = $usp_options['usp_casing'];
	
	$protocol = 'http://';
	if (is_ssl()) $protocol = 'https://';
	
	$current_url = sanitize_text_field(trailingslashit($protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
	$current_url = remove_query_arg(array('submission-error', 'error', 'success', 'post_id'), $current_url);
	
	$print_casing = 'false';
	if ($usp_casing) $print_casing = 'true';
	
	$display = false;
	if ($display_url !== '') {
		if (($display_url == $current_url) && ($include_js == true)) $display = true;
	} else {
		if ($include_js == true) $display = true;
	}
	if (!is_admin()) {
		if ($display) : ?>
		
		<script type="text/javascript">
			window.ParsleyConfig = { excluded: ".exclude" };
			var usp_case_sensitivity = <?php echo json_encode($print_casing); ?>;
			var usp_challenge_response = <?php echo json_encode($usp_response); ?>;
		</script>
<?php endif;
	}
}
add_action('wp_print_scripts','usp_js_vars');

// enqueue script and style
if (!function_exists('usp_enqueueResources')) {
	function usp_enqueueResources() {
		global $usp_options, $usp_version;
		
		$min_images  = $usp_options['min-images'];
		$include_js  = $usp_options['usp_include_js'];
		$form_type   = $usp_options['usp_form_version'];
		$display_url = $usp_options['usp_display_url'];
		
		$protocol = 'http://';
		if (is_ssl()) $protocol = 'https://';
		
		$current_url = sanitize_text_field(trailingslashit($protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
		$current_url = remove_query_arg(array('submission-error', 'error', 'success', 'post_id'), $current_url);
		
		$base_url = plugins_url() .'/'. basename(dirname(__FILE__));
		$dir_path = plugin_dir_path(__FILE__);
		
		$custom_css  = '/custom/usp.css';
		$default_css = '/resources/usp.css';
		$usp_css     = $base_url . $default_css;
		
		if ($form_type == 'custom' && file_exists($dir_path . $custom_css)) $usp_css = $base_url . $custom_css;
		
		$display = false;
		if ($display_url !== '') {
			if (($display_url == $current_url) && ($include_js == true)) $display = true;
		} else {
			if ($include_js == true) $display = true;
		}
		if (!is_admin()) {
			// style
			if ($form_type !== 'disable') wp_enqueue_style('usp_style', $usp_css, false, null, 'all');
			// script
			if ($display) {
				wp_enqueue_script('usp_cookie',  $base_url .'/resources/jquery.cookie.js',      array('jquery'), null);
				wp_enqueue_script('usp_parsley', $base_url .'/resources/jquery.parsley.min.js', array('jquery'), null);
				wp_enqueue_script('usp_core',    $base_url .'/resources/jquery.usp.core.js',    array('jquery'), null);
				if ($min_images > 0) {
					wp_enqueue_script('usp_files', $base_url .'/resources/jquery.usp.files.js', array('jquery'), null);
				}
			}
		}
	}
	add_action('wp_enqueue_scripts', 'usp_enqueueResources');
}

// add styles to admin Edit page
add_action('admin_print_styles', 'load_custom_admin_css');
function load_custom_admin_css() {
	global $usp_version, $pagenow;
	if (is_admin() && $pagenow == 'edit.php') {
		wp_enqueue_style('usp_style_admin', plugins_url() . '/' . basename(dirname(__FILE__)) . '/resources/usp-admin.css', false, $usp_version, 'all');
	}
}

// add styles for WP rich text editor
function usp_editor_style($mce_css){
    $mce_css .= ', ' . plugins_url('resources/editor-style.css', __FILE__);
    return $mce_css;
}
add_filter('mce_css', 'usp_editor_style');

// shortcode
function usp_display_form($atts = array(), $content = null) {
	global $usp_options;
	
	$default = WP_PLUGIN_DIR .'/'. basename(dirname(__FILE__)) .'/views/submission-form.php';
	$custom  = WP_PLUGIN_DIR .'/'. basename(dirname(__FILE__)) .'/custom/submission-form.php';
	
	if ($atts === true) $redirect = usp_currentPageURL();
	
	ob_start();
	if ($usp_options['usp_form_version'] == 'custom' && file_exists($custom)) include($custom);
	else include($default);
	return apply_filters('usp_form_shortcode', ob_get_clean());
}
add_shortcode ('user-submitted-posts', 'usp_display_form');

// template tag
function user_submitted_posts() {
	echo usp_display_form();
}

// add usp link
add_action ('restrict_manage_posts', 'usp_outputUserSubmissionLink');
function usp_outputUserSubmissionLink() {
	global $pagenow;
	if ($pagenow == 'edit.php') {
		echo '<a id="usp_admin_filter_posts" class="button" href="' . admin_url('edit.php?post_status=pending&amp;user_submitted=1') . '">' . __('USP', 'usp') . '</a>';
	}
}

// replace author
add_filter ('the_author', 'usp_replaceAuthor');
function usp_replaceAuthor($author) {
	global $post, $usp_options, $usp_post_meta_IsSubmission, $usp_post_meta_Submitter;

	$isSubmission     = get_post_meta($post->ID, $usp_post_meta_IsSubmission, true);
	$submissionAuthor = get_post_meta($post->ID, $usp_post_meta_Submitter, true);

	if ($isSubmission && !empty($submissionAuthor)) $author = $submissionAuthor;
	
	return apply_filters('usp_post_author', $author);
}

// get author
function usp_get_author($author) {
	global $usp_options;
	$error = false;
	$author_id = $usp_options['author'];
	if (!empty($author)) {
		if ($usp_options['usp_use_author'] == true) {
			$author_info = get_user_by('login', $author);
			if ($author_info) {
				$author_id = $author_info->ID;
				$author = get_the_author_meta('display_name', $author_id);
			} else {
				$error = 'required-login';
			}
		}
	} else {
		if ($usp_options['usp_use_author'] == true) {
			$error = 'required-login';
		} else {
			if ($usp_options['usp_name'] == 'show') {
				$error = 'required-name';
			}
		}
	}
	$author_data = array('author' => $author, 'author_id' => $author_id, 'error' => $error);
	return $author_data;
}

// exif_imagetype support
if (!function_exists('exif_imagetype')) {
	function exif_imagetype($filename) {
		if ((list($width, $height, $type, $attr) = getimagesize($filename)) !== false) { 
			return $type;
		} 
		return false; 
	} 
} 

function usp_check_images($files) {
	global $usp_options;
	
	$temp = false; $errr = false; $error = array();
	
	if (isset($files['tmp_name'])) $temp = array_filter($files['tmp_name']);
	if (isset($files['error']))    $errr = array_filter($files['error']);
	
	$file_count = 0;
	if (!empty($temp)) {
		foreach ($temp as $key => $value) if (is_uploaded_file($value)) $file_count++;
	}
	if ($usp_options['usp_images'] == 'show') {
		
		if ($file_count < $usp_options['min-images']) $error[] = 'file-min';
		if ($file_count > $usp_options['max-images']) $error[] = 'file-max';
		
		for ($i = 0; $i < $file_count; $i++) {
			
			$image = @getimagesize($temp[$i]);
			
			if (false === $image) {
				$error[] = 'file-type';
				break;
			} else {
				if (isset($temp[$i]) && !exif_imagetype($temp[$i])) {
					$error[] = 'file-type';
					break;
				}
				if (isset($image[0]) && !usp_width_min($image[0])) {
					$error[] = 'width-min';
					break;
				}
				if (isset($image[0]) && !usp_width_max($image[0])) {
					$error[] = 'width-max';
					break;
				}
				if (isset($image[1]) && !usp_height_min($image[1])) {
					$error[] = 'height-min';
					break;
				}
				if (isset($image[1]) && !usp_height_max($image[1])) {
					$error[] = 'height-max';
					break;
				}
				if (isset($errr[$i]) && $errr[$i] == 4) {
					$error[] = 'file-error';
					break;
				}
			}
		}
	} else {
		$files = false;
	}
	$file_data = array('error' => $error, 'file_count' => $file_count);
	return $file_data;
}

// prepare submitted post
function usp_prepare_post($title, $content, $author_id, $author, $ip) {
	global $usp_options, $usp_post_meta_Submitter, $usp_post_meta_SubmitterIp;
	
	$postData = array();
	$postData['post_title']   = $title;
	$postData['post_content'] = $content;
	$postData['post_author']  = $author_id;
	$postData['post_status']  = apply_filters('usp_post_status', 'pending');
	
	$numberApproved = $usp_options['number-approved'];
	
	if ($numberApproved == 0) {
		$postData['post_status'] = apply_filters('usp_post_publish', 'publish');
	} elseif ($numberApproved == -1) {
		$postData['post_status']  = apply_filters('usp_post_moderate', 'pending');
	} else {
		$posts = get_posts(array('post_status' => 'publish', 'meta_key' => $usp_post_meta_Submitter, 'meta_value' => $author));
		$counter = 0;
		foreach ($posts as $post) {
			$submitterName = get_post_meta($post->ID, $usp_post_meta_Submitter, true);
			$submitterIp   = get_post_meta($post->ID, $usp_post_meta_SubmitterIp, true);
			if ($submitterName == $author && $submitterIp == $ip) $counter++;
		}
		if ($counter >= $numberApproved) $postData['post_status'] = apply_filters('usp_post_approve', 'publish');
	}
	return apply_filters('usp_post_data', $postData);
}

// check for duplicate posts
function usp_check_duplicates($title) {
	global $usp_options;
	if ($usp_options['titles_unique']) {
		$check_post = get_page_by_title($title, OBJECT, 'post');
		if ($check_post && $check_post->ID) return false;
	}
	return true;
}

// process submission
function usp_createPublicSubmission($title, $files, $ip, $author, $url, $email, $tags, $captcha, $verify, $content, $category) {
	global $usp_options, $usp_post_meta_IsSubmission, $usp_post_meta_SubmitterIp, $usp_post_meta_Submitter, $usp_post_meta_SubmitterUrl, $usp_post_meta_SubmitterEmail, $usp_post_meta_Image;
	
	// check errors
	$newPost = array('id' => false, 'error' => false);
	
	$author_data        = usp_get_author($author);
	$author             = $author_data['author'];
	$author_id          = $author_data['author_id'];
	$newPost['error'][] = $author_data['error'];
	
	$file_data = usp_check_images($files, $newPost);
	$file_count       = $file_data['file_count'];
	$newPost['error'] = array_unique(array_merge($file_data['error'], $newPost['error']));
	
	if (isset($usp_options['usp_title'])    && ($usp_options['usp_title']    == 'show') && empty($title))    $newPost['error'][] = 'required-title';
	if (isset($usp_options['usp_url'])      && ($usp_options['usp_url']      == 'show') && empty($url))      $newPost['error'][] = 'required-url';
	if (isset($usp_options['usp_tags'])     && ($usp_options['usp_tags']     == 'show') && empty($tags))     $newPost['error'][] = 'required-tags';
	if (isset($usp_options['usp_category']) && ($usp_options['usp_category'] == 'show') && empty($category)) $newPost['error'][] = 'required-category';
	if (isset($usp_options['usp_content'])  && ($usp_options['usp_content']  == 'show') && empty($content))  $newPost['error'][] = 'required-content';
	
	if (isset($usp_options['usp_captcha']) && ($usp_options['usp_captcha'] == 'show') && !usp_spamQuestion($captcha)) $newPost['error'][] = 'required-captcha';
	if (isset($usp_options['usp_email'])   && ($usp_options['usp_email']   == 'show') && !usp_validateEmail($email))  $newPost['error'][] = 'required-email';
	
	if (isset($usp_options['titles_unique']) && $usp_options['titles_unique'] && !usp_check_duplicates($title)) $newPost['error'][] = 'duplicate-title';
	if (!empty($verify)) $newPost['error'][] = 'spam-verify';
	
	foreach ($newPost['error'] as $e) {
		if (!empty($e)) {
			unset($newPost['id']);
			return $newPost;
		}
	}
	
	// submit post
	$postData = usp_prepare_post($title, $content, $author_id, $author, $ip);
	
	do_action('usp_insert_before', $postData);
	$newPost['id'] = wp_insert_post($postData);
	do_action('usp_insert_after', $newPost);
	
	if ($newPost['id']) {
		$post_id = $newPost['id'];
		wp_set_post_tags($post_id, $tags);
		wp_set_post_categories($post_id, array($category));
		usp_send_mail_alert($post_id, $title);
		do_action('usp_files_before', $files);
		
		$attach_ids = array();
		if ($files && $file_count > 0) {
			usp_include_deps();
			for ($i = 0; $i < $file_count; $i++) {
				
				$key = apply_filters('usp_file_key', 'user-submitted-image-{$i}');
				
				$_FILES[$key] = array();
				$_FILES[$key]['name']     = $files['name'][$i];
				$_FILES[$key]['tmp_name'] = $files['tmp_name'][$i];
				$_FILES[$key]['type']     = $files['type'][$i];
				$_FILES[$key]['error']    = $files['error'][$i];
				$_FILES[$key]['size']     = $files['size'][$i];
				
				$attach_id = media_handle_upload($key, $post_id);
				
				if (!is_wp_error($attach_id) && wp_attachment_is_image($attach_id)) {
					$attach_ids[] = $attach_id;
					add_post_meta($post_id, $usp_post_meta_Image, wp_get_attachment_url($attach_id));
				} else {
					wp_delete_attachment($attach_id);
					wp_delete_post($post_id, true);
					$newPost['error'][] = 'file-upload';
					unset($newPost['id']);
					return $newPost;
				}
			}
		}
		do_action('usp_files_after', $attach_ids);
		update_post_meta($post_id, $usp_post_meta_IsSubmission, true);
		
		if (!empty($author)) update_post_meta($post_id, $usp_post_meta_Submitter,      $author);
		if (!empty($url))    update_post_meta($post_id, $usp_post_meta_SubmitterUrl,   $url);
		if (!empty($email))  update_post_meta($post_id, $usp_post_meta_SubmitterEmail, $email);
		if (!empty($ip))     update_post_meta($post_id, $usp_post_meta_SubmitterIp,    $ip);
	} else {
		$newPost['error'][] = 'post-fail';
	}
	return apply_filters('usp_new_post', $newPost);
}

// include wp media files
function usp_include_deps() {
	if (!function_exists('media_handle_upload')) {
		require_once (ABSPATH .'/wp-admin/includes/media.php');
		require_once (ABSPATH .'/wp-admin/includes/file.php');
		require_once (ABSPATH .'/wp-admin/includes/image.php');
	}
}

// image min/max width & height
function usp_width_min($width) {
	global $usp_options;
	if (intval($width) < intval($usp_options['min-image-width'])) return false;
	else return true;
}
function usp_width_max($width) {
	global $usp_options;
	if (intval($width) > intval($usp_options['max-image-width'])) return false;
	else return true;
}
function usp_height_min($height) {
	global $usp_options;
	if (intval($height) < intval($usp_options['min-image-height'])) return false;
	else return true;
}
function usp_height_max($height) {
	global $usp_options;
	if (intval($height) > intval($usp_options['max-image-height'])) return false;
	else return true;
}

// validate email
function usp_validateEmail($email) {
	if (!is_email($email)) return false;
	$bad_stuff = array("\r", "\n", "mime-version", "content-type", "cc:", "to:");
	foreach ($bad_stuff as $bad) {
		if (strpos(strtolower($email), strtolower($bad)) !== false) {
			return false;
		}
	}
	return true;
}

// send email alert
function usp_send_mail_alert($post_id, $title) {
	global $usp_options;
	
	if ($usp_options['usp_email_alerts'] == true) {
		
		$blog = get_bloginfo('name');
		
		$to = $usp_options['usp_email_address'];
		if (empty($to)) return false;
		
		$subject =  $blog .': New user-submitted post!';
		
		$message  = 'Hello, there is a new user-submitted post:'. "\r\n\n";
		$message .= 'Title: '. $title . "\r\n\n" .'Visit Admin Area: '. admin_url();
		
		$subject = apply_filters('usp_mail_subject', $subject);
		$message = apply_filters('usp_mail_message', $message);
		
		$headers  = "MIME-Version: 1.0\n";
		$headers .= "From: $blog <$to>\n";
		$headers .= "Reply-To: $blog <$to>\n";
		$headers .= "Return-Path: $to\n";
		$headers .= "Content-Type: text/plain; charset=\"". get_option('blog_charset') ."\"\n"; 
		
		if (wp_mail($to, $subject, $message, $headers)) return true;
	}
	return false;
}

// challenge question
function usp_spamQuestion($input) {
	global $usp_options;
	$response = $usp_options['usp_response'];
	$response = sanitize_text_field($response);
	if ($usp_options['usp_casing'] == false) {
		return (strtoupper($input) == strtoupper($response));
	} else {
		return ($input == $response);
	}
}

// current url
function usp_currentPageURL() {
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	do_action('usp_current_page', $pageURL);
	return sanitize_text_field($pageURL);
}

// error messages
function usp_error_message() {
	global $usp_options;
	
	$min = $usp_options['min-images'];
	$max = $usp_options['max-images'];
	
	if ((int) $min > 1) $min = ' ('. $min . __(' files required', 'usp') .')';
	else $min = ' ('. $min . __(' file required', 'usp') .')';
	
	if ((int) $max > 1) $max = ' (limit: '. $max . __(' files', 'usp') .')';
	else $max = ' (limit: '. $max . __(' file', 'usp') .')';
	
	$min_width  = ' ('. $usp_options['min-image-width']  . __(' pixels', 'usp') .')';
	$max_width  = ' ('. $usp_options['max-image-width']  . __(' pixels', 'usp') .')';
	$min_height = ' ('. $usp_options['min-image-height'] . __(' pixels', 'usp') .')';
	$max_height = ' ('. $usp_options['max-image-height'] . __(' pixels', 'usp') .')';
	
	if (!empty($usp_options['error-message'])) $general_error = $usp_options['error-message'];
	else $general_error = __('An error occurred. Please go back and try again.', 'usp');
	
	if (isset($_GET['usp-error']) && !empty($_GET['usp-error'])) {
		$error_string = sanitize_text_field($_GET['usp-error']);
		$error_array = explode(',', $error_string);
		$error = array();
		foreach ($error_array as $e) {
			if     ($e == 'required-login')    $error[] = __('User login required', 'usp');
			elseif ($e == 'required-name')     $error[] = __('User name required', 'usp');
			elseif ($e == 'required-title')    $error[] = __('Post title required', 'usp');
			elseif ($e == 'required-url')      $error[] = __('Post url required', 'usp');
			elseif ($e == 'required-tags')     $error[] = __('Post tags required', 'usp');
			elseif ($e == 'required-category') $error[] = __('Post category required', 'usp');
			elseif ($e == 'required-content')  $error[] = __('Post content required', 'usp');
			elseif ($e == 'required-captcha')  $error[] = __('Correct captcha required', 'usp');
			elseif ($e == 'required-email')    $error[] = __('User email required', 'usp');
			elseif ($e == 'spam-verify')       $error[] = __('Non-empty value for hidden field', 'usp');
			elseif ($e == 'file-min')          $error[] = __('Minimum number of images not met', 'usp') . $min;
			elseif ($e == 'file-max')          $error[] = __('Maximum number of images exceeded ', 'usp') . $max;
			elseif ($e == 'width-min')         $error[] = __('Minimum image width not met', 'usp') . $min_width;
			elseif ($e == 'width-max')         $error[] = __('Image width exceeds maximum', 'usp') . $max_width;
			elseif ($e == 'height-min')        $error[] = __('Minimum image height not met', 'usp') . $min_height;
			elseif ($e == 'height-max')        $error[] = __('Image height exceeds maximum', 'usp') . $max_height;
			elseif ($e == 'file-type')         $error[] = __('File type not allowed (please upload images only)', 'usp');
			elseif ($e == 'file-error')        $error[] = __('The selected files could not be uploaded to the server', 'usp'); // general file(s) error
			elseif ($e == 'file-upload')       $error[] = __('The file(s) could not be uploaded', 'usp'); // check permissions on /uploads/ directory, check error log for the following error:
																					    // PHP Warning: mysql_real_escape_string() expects parameter 1 to be string, object given in /wp-includes/wp-db.php
			
			elseif ($e == 'post-fail')         $error[] = __('Post not created. Please contact the site administrator for help.', 'usp');
			elseif ($e == 'duplicate-title')   $error[] = __('Duplicate post title. Please try again.', 'usp');
			
			elseif ($e == 'error')             $error[] = $general_error;
		}
		$output = '';
		foreach ($error as $e) {
			$output .= "\t\t\t".'<div class="usp-error">'. __('Error: ', 'usp') . $e .'</div>'."\n";
		}
		$return = '<div id="usp-error-message">'."\n". $output ."\t\t".'</div>'."\n";
		return apply_filters('usp_error_message', $return);
	}
	return false;
}

// display settings link on plugin page
add_filter('plugin_action_links', 'usp_plugin_action_links', 10, 2);
function usp_plugin_action_links($links, $file) {
	global $usp_path;
	if ($file == $usp_path) {
		$usp_links = '<a href="' . get_admin_url() . 'options-general.php?page=' . $usp_path . '">' . __('Settings', 'usp') .'</a>';
		array_unshift($links, $usp_links);
	}
	return $links;
}

// rate plugin link
function add_usp_links($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$links[] = '<a target="_blank" href="' . $rate_url . '" title="Click here to rate and review this plugin on WordPress.org">Rate this plugin</a>';
		$links[] = '<strong><a target="_blank" href="https://plugin-planet.com/usp-pro/" title="Get USP Pro">Go Pro &raquo;</a></strong>';
	}
	return $links;
}
add_filter('plugin_row_meta', 'add_usp_links', 10, 2);

// delete plugin settings
function usp_delete_plugin_options() {
	delete_option('usp_options');
}
if ($usp_options['default_options'] == 1) {
	register_uninstall_hook (__FILE__, 'usp_delete_plugin_options');
}

// define default settings
register_activation_hook (__FILE__, 'usp_add_defaults');
function usp_add_defaults() {
	$currentUser = wp_get_current_user();
	$admin_mail = get_bloginfo('admin_email');
	$tmp = get_option('usp_options');
	if(($tmp['default_options'] == '1') || (!is_array($tmp))) {
		$arr = array(
			'version_alert'       => 0,
			'default_options'     => 0,
			'author'              => $currentUser->ID,
			'categories'          => array(get_option('default_category')),
			'number-approved'     => -1,
			'redirect-url'        => '',
			'error-message'       => __('There was an error. Please ensure that you have added a title, some content, and that you have uploaded only images.', 'usp'),
			'min-images'          => 0,
			'max-images'          => 1,
			'min-image-height'    => 0,
			'min-image-width'     => 0,
			'max-image-height'    => 1500,
			'max-image-width'     => 1500,
			'usp_name'            => __('show', 'usp'),
			'usp_url'             => __('show', 'usp'),
			'usp_email'           => __('hide', 'usp'),
			'usp_title'           => __('show', 'usp'),
			'usp_tags'            => __('show', 'usp'),
			'usp_category'        => __('show', 'usp'),
			'usp_images'          => __('hide', 'usp'),
			'upload-message'      => __('Please select your image(s) to upload.', 'usp'),
			'usp_form_width'      => '300', // in pixels (not used anywhere)
			'usp_question'        => '1 + 1 =',
			'usp_response'        => '2',
			'usp_casing'          => 0,
			'usp_captcha'         => __('show', 'usp'),
			'usp_content'         => __('show', 'usp'),
			'success-message'     => __('Success! Thank you for your submission.', 'usp'),
			'usp_form_version'    => 'current',
			'usp_email_alerts'    => 1,
			'usp_email_address'   => $admin_mail,
			'usp_use_author'      => 0,
			'usp_use_url'         => 0,
			'usp_use_cat'         => 0,
			'usp_use_cat_id'      => '',
			'usp_include_js'      => 1,
			'usp_display_url'     => '',
			'usp_form_content'    => '',
			'usp_richtext_editor' => 0,
			'usp_featured_images' => 0,
			'usp_add_another'     => '',
			'disable_required'    => 0,
			'titles_unique'       => 0,
		);
		update_option('usp_options', $arr);
	}
}

// define style options
$usp_form_version = array(
	'current' => array(
		'value' => 'current',
		'label' => __('HTML5 form + CSS (<small><em>Recommended</em></small>)', 'usp')
	),
	'custom' => array(
		'value' => 'custom',
		'label' => __('Custom form + CSS (<small><em>You must provide the template for this option*</em></small>)', 'usp')
	),
	'disable' => array(
		'value' => 'disable',
		'label' => __('Disable stylesheet', 'usp')
	),
);

// whitelist settings
add_action ('admin_init', 'usp_init');
function usp_init() {
	register_setting('usp_plugin_options', 'usp_options', 'usp_validate_options');
}

// sanitize and validate input
function usp_validate_options($input) {
	global $usp_options, $usp_form_version;
	
	if (!isset($input['version_alert'])) $input['version_alert'] = null;
	$input['version_alert'] = ($input['version_alert'] == 1 ? 1 : 0);
	
	if (!isset($input['default_options'])) $input['default_options'] = null;
	$input['default_options'] = ($input['default_options'] == 1 ? 1 : 0);

	$input['categories']       = is_array($input['categories']) && !empty($input['categories']) ? array_unique($input['categories']) : array(get_option('default_category'));
	$input['number-approved']  = is_numeric($input['number-approved']) ? intval($input['number-approved']) : -1;

	$input['min-images']       = is_numeric($input['min-images']) ? intval($input['min-images']) : $input['max-images'];
	$input['max-images']       = (is_numeric($input['max-images']) && ($usp_options['min-images'] <= abs($input['max-images']))) ? intval($input['max-images']) : $usp_options['max-images'];
	
	$input['min-image-height'] = is_numeric($input['min-image-height']) ? intval($input['min-image-height']) : $usp_options['min-image-height'];
	$input['min-image-width']  = is_numeric($input['min-image-width'])  ? intval($input['min-image-width'])  : $usp_options['min-image-width'];
	
	$input['max-image-height'] = (is_numeric($input['max-image-height']) && ($usp_options['min-image-height'] <= $input['max-image-height'])) ? intval($input['max-image-height']) : $usp_options['max-image-height'];
	$input['max-image-width']  = (is_numeric($input['max-image-width'])  && ($usp_options['min-image-width']  <= $input['max-image-width']))  ? intval($input['max-image-width'])  : $usp_options['max-image-width'];

	$input['author']            = wp_filter_nohtml_kses($input['author']);
	$input['usp_name']          = wp_filter_nohtml_kses($input['usp_name']);
	$input['usp_url']           = wp_filter_nohtml_kses($input['usp_url']);
	$input['usp_email']         = wp_filter_nohtml_kses($input['usp_email']);
	$input['usp_title']         = wp_filter_nohtml_kses($input['usp_title']);
	$input['usp_tags']          = wp_filter_nohtml_kses($input['usp_tags']);
	$input['usp_category']      = wp_filter_nohtml_kses($input['usp_category']);
	$input['usp_images']        = wp_filter_nohtml_kses($input['usp_images']);
	//$input['usp_form_width']    = wp_filter_nohtml_kses($input['usp_form_width']);
	$input['usp_question']      = wp_filter_nohtml_kses($input['usp_question']);
	//$input['usp_answer']        = wp_filter_nohtml_kses($input['usp_answer']);
	$input['usp_captcha']       = wp_filter_nohtml_kses($input['usp_captcha']);
	$input['usp_content']       = wp_filter_nohtml_kses($input['usp_content']);
	$input['usp_email_address'] = wp_filter_nohtml_kses($input['usp_email_address']);
	$input['usp_use_cat_id']    = wp_filter_nohtml_kses($input['usp_use_cat_id']);
	$input['usp_display_url']   = wp_filter_nohtml_kses($input['usp_display_url']);
	$input['redirect-url']      = wp_filter_nohtml_kses($input['redirect-url']);

	// dealing with kses
	global $allowedposttags;
	$allowed_atts = array(
		'align'=>array(), 
		'class'=>array(), 
		'type'=>array(), 
		'id'=>array(), 
		'dir'=>array(), 
		'lang'=>array(), 
		'style'=>array(), 
		'xml:lang'=>array(), 
		'src'=>array(), 
		'alt'=>array(), 
		'href'=>array(), 
		'rel'=>array(), 
		'target'=>array());

	$allowedposttags['script'] = $allowed_atts;
	$allowedposttags['strong'] = $allowed_atts;
	$allowedposttags['small'] = $allowed_atts;
	$allowedposttags['span'] = $allowed_atts;
	$allowedposttags['abbr'] = $allowed_atts;
	$allowedposttags['code'] = $allowed_atts;
	$allowedposttags['div'] = $allowed_atts;
	$allowedposttags['img'] = $allowed_atts;
	$allowedposttags['h1'] = $allowed_atts;
	$allowedposttags['h2'] = $allowed_atts;
	$allowedposttags['h3'] = $allowed_atts;
	$allowedposttags['h4'] = $allowed_atts;
	$allowedposttags['h5'] = $allowed_atts;
	$allowedposttags['ol'] = $allowed_atts;
	$allowedposttags['ul'] = $allowed_atts;
	$allowedposttags['li'] = $allowed_atts;
	$allowedposttags['em'] = $allowed_atts;
	$allowedposttags['p'] = $allowed_atts;
	$allowedposttags['a'] = $allowed_atts;

	$input['usp_form_content'] = wp_kses_post($input['usp_form_content'], $allowedposttags);
	$input['error-message']    = wp_kses_post($input['error-message'], $allowedposttags);
	$input['upload-message']   = wp_kses_post($input['upload-message'], $allowedposttags);
	$input['success-message']  = wp_kses_post($input['success-message'], $allowedposttags);
	$input['usp_add_another']  = wp_kses_post($input['usp_add_another'], $allowedposttags);

	if (!isset($input['usp_casing'])) $input['usp_casing'] = null;
	$input['usp_casing'] = ($input['usp_casing'] == 1 ? 1 : 0);

	if (!isset($input['usp_form_version'])) $input['usp_form_version'] = null;
	if (!array_key_exists($input['usp_form_version'], $usp_form_version)) $input['usp_form_version'] = null;
	
	if (!isset($input['usp_email_alerts'])) $input['usp_email_alerts'] = null;
	$input['usp_email_alerts'] = ($input['usp_email_alerts'] == 1 ? 1 : 0);

	if (!isset($input['usp_use_author'])) $input['usp_use_author'] = null;
	$input['usp_use_author'] = ($input['usp_use_author'] == 1 ? 1 : 0);

	if (!isset($input['usp_use_url'])) $input['usp_use_url'] = null;
	$input['usp_use_url'] = ($input['usp_use_url'] == 1 ? 1 : 0);
	
	if (!isset($input['usp_use_cat'])) $input['usp_use_cat'] = null;
	$input['usp_use_cat'] = ($input['usp_use_cat'] == 1 ? 1 : 0);

	if (!isset($input['usp_include_js'])) $input['usp_include_js'] = null;
	$input['usp_include_js'] = ($input['usp_include_js'] == 1 ? 1 : 0);

	if (!isset($input['usp_richtext_editor'])) $input['usp_richtext_editor'] = null;
	$input['usp_richtext_editor'] = ($input['usp_richtext_editor'] == 1 ? 1 : 0);

	if (!isset($input['usp_featured_images'])) $input['usp_featured_images'] = null;
	$input['usp_featured_images'] = ($input['usp_featured_images'] == 1 ? 1 : 0);
	
	if (!isset($input['disable_required'])) $input['disable_required'] = null;
	$input['disable_required'] = ($input['disable_required'] == 1 ? 1 : 0);
	
	if (!isset($input['titles_unique'])) $input['titles_unique'] = null;
	$input['titles_unique'] = ($input['titles_unique'] == 1 ? 1 : 0);
	
	return apply_filters('usp_input_validate', $input);
}

// add the options page
add_action ('admin_menu', 'usp_add_options_page');
function usp_add_options_page() {
	global $usp_plugin;
	add_options_page($usp_plugin, $usp_plugin, 'manage_options', __FILE__, 'usp_render_form');
}

// create the options page
function usp_render_form() {
	global $usp_plugin, $usp_options, $usp_path, $usp_homeurl, $usp_version, $usp_logo, $usp_pro, $usp_form_version; 
	
	$display_alert = ' style="display:block;"';
	if (isset($usp_options['version_alert']) && $usp_options['version_alert']) $display_alert = ' style="display:none;"'; ?>

	<style type="text/css">
		.dismiss-alert { margin: 15px; }
		.dismiss-alert-wrap { display: inline-block; padding: 7px 0 10px 0; }
		.dismiss-alert .description { display: inline-block; margin: -2px 15px 0 0; }
		
		.mm-panel-overview { padding: 0 0 0 200px; background: url(<?php echo $usp_logo; ?>) no-repeat 15px 0; }
		.mm-left-div { float: left; margin-bottom: 25px; }
		.mm-right-div { float: left; margin: -5px 15px 25px 15px; }
		.mm-pro-blurb {
			text-decoration: none; text-align: center; font-weight: bold; text-indent: -9999em;
			display: block; width: 100px; height: 100px; line-height: 100px; position: relative; 
			border: none; -webkit-border-radius: 100px; -moz-border-radius: 100px; border-radius: 100px; 
			color: #fff; background: url(<?php echo $usp_pro; ?>) no-repeat center center; 
			}
			.mm-pro-blurb:hover { color: #fff; text-decoration: none; }
			.mm-pro-blurb:active { top: 1px; }

		#mm-plugin-options h2 small { font-size: 60%; }
		#mm-plugin-options h3 { cursor: pointer; }
		#mm-plugin-options h4, 
		#mm-plugin-options p { margin: 15px; line-height: 18px; }
		#mm-plugin-options ul { margin: 15px 15px 25px 40px; line-height: 18px; font-size: 13px; }
		#mm-plugin-options li { margin: 10px 0; list-style-type: disc; }
		#mm-plugin-options ul.mm-overview-list { margin: 0 15px 0 40px; }
		#mm-plugin-options ul.mm-overview-list li { margin: 5px 0; }
		#mm-plugin-options abbr { cursor: help; border-bottom: 1px dotted #dfdfdf; }

		.mm-table-wrap { margin: 15px; }
		.mm-table-wrap td, 
		.mm-table-wrap th { padding: 15px; line-height: 18px; vertical-align: middle; }
		.mm-table-wrap th { width: 25%; }
		.mm-item-caption { margin: 3px 0 0 3px; font-size: 11px; color: #777; }
		.mm-item-caption code { font-size: 10px; }
		.inline { display: inline; }
		
		.mm-table-wrap input[type="text"] { width: 80%; font-size: 13px; }
		.mm-table-wrap textarea { width: 90%; font-size: 13px; }
		.mm-table-wrap .input-short[type="text"] { width: 77px; }
		.mm-radio-inputs { margin: 7px 0; }
		.mm-radio-inputs span { padding-left: 5px; }
		.mm-code { background-color: #fafae0; color: #333; font-size: 14px; }
		#mm-plugin-options .mm-plain-list li { list-style-type: none; }
		.mm-plain-list li span { padding-left: 5px; }

		#setting-error-settings_updated { margin: 10px 0; }
		#setting-error-settings_updated p { margin: 5px; }
		#mm-plugin-options .button-primary { margin: 0 0 15px 15px; }

		#mm-panel-toggle { margin: 5px 0; }
		#mm-credit-info { margin-top: -5px; }
		#mm-iframe-wrap { width: 100%; height: 250px; overflow: hidden; }
		#mm-iframe-wrap iframe { width: 100%; height: 100%; overflow: hidden; margin: 0; padding: 0; }
		
		.clear:before,
		.clear:after { content: ""; display: table; }
		.clear:after { clear: both; }
	</style>

	<div id="mm-plugin-options" class="wrap">
		<h2><?php echo $usp_plugin; ?> <small><?php echo 'v' . $usp_version; ?></small></h2>
		<div id="mm-panel-toggle"><a href="<?php get_admin_url() . 'options-general.php?page=' . $usp_path; ?>"><?php _e('Toggle all panels', 'usp'); ?></a></div>

		<form method="post" action="options.php">
			<?php $usp_options = get_option('usp_options'); settings_fields('usp_plugin_options'); ?>

			<div class="metabox-holder">
				<div class="meta-box-sortables ui-sortable">
					
					<div id="mm-panel-alert"<?php echo $display_alert; ?> class="postbox">
						<h3><?php _e('User Submitted Posts needs your support!', 'usp'); ?></h3>
						<div class="toggle">
							<div class="mm-panel-alert">
								<p>
									<?php _e('Please', 'usp'); ?> <a target="_blank" href="http://m0n.co/donate" title="<?php _e('Make a donation via PayPal', 'usp'); ?>"><?php _e('make a donation', 'usp'); ?></a> <?php _e('and/or', 'usp'); ?> 
									<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/<?php echo basename(dirname(__FILE__)); ?>?rate=5#postform" title="<?php _e('Rate and review at the Plugin Directory', 'usp'); ?>">
										<?php _e('give it a 5-star rating', 'usp'); ?>&nbsp;&raquo;
									</a>
								</p>
								<p>
									<?php _e('Your generous support enables continued development of this free plugin. Thank you!', 'usp'); ?>
								</p>
								<div class="dismiss-alert">
									<div class="dismiss-alert-wrap">
										<input class="input-alert" name="usp_options[version_alert]" type="checkbox" value="1" <?php if (isset($usp_options['version_alert'])) checked('1', $usp_options['version_alert']); ?> />  
										<label class="description" for="usp_options[version_alert]"><?php _e('Check this box if you have shown support', 'usp') ?></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div id="mm-panel-overview" class="postbox">
						<h3><?php _e('Overview', 'usp'); ?></h3>
						<div class="toggle">
							<div class="mm-panel-overview clear">
								<p class="mm-overview-intro">
									<strong><?php echo $usp_plugin; ?></strong> <?php _e('(USP) enables your visitors to submit posts and upload images from anywhere on your site.', 'usp'); ?> 
									<?php _e('To implement, configure the plugin settings and include the USP form in any post or page via shortcode or anywhere in your theme via template tag.', 'usp'); ?> 
									<?php _e('For more functionality check out', 'usp'); ?> <strong><a href="https://plugin-planet.com/usp-pro/" target="_blank">USP Pro</a></strong> 
									<?php _e('&mdash; the ultimate solution for unlimited front-end forms.', 'usp'); ?>
								</p>
								<div class="mm-left-div">
									<ul class="mm-overview-list">
										<li><a id="mm-panel-primary-link" href="#mm-panel-primary"><?php _e('Configure settings', 'usp'); ?></a></li>
										<li><a id="mm-panel-secondary-link" href="#mm-panel-secondary"><?php _e('Get the shortcode &amp; template tag', 'usp'); ?></a></li>
										<li>
											<?php _e('More info:', 'usp'); ?> <a target="_blank" href="<?php echo plugins_url('/user-submitted-posts/readme.txt', dirname(__FILE__)); ?>">readme.txt</a> 
											<?php _e('and', 'usp'); ?> <a target="_blank" href="<?php echo $usp_homeurl; ?>"><?php _e('homepage', 'usp'); ?></a>
										</li>
										<li><?php _e('If you like USP, please', 'usp'); ?> 
											<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/<?php echo basename(dirname(__FILE__)); ?>?rate=5#postform" title="<?php _e('Rate and review this plugin at the WP Plugin Directory', 'usp'); ?>">
												<?php _e('rate it at WordPress.org', 'usp'); ?>&nbsp;&raquo;
											</a>
										</li>
									</ul>
								</div>
								<div class="mm-right-div">
									<a class="mm-pro-blurb" target="_blank" href="https://plugin-planet.com/usp-pro/" title="Unlimited front-end forms">Get USP Pro</a>
								</div>
							</div>
						</div>
					</div>
					<div id="mm-panel-primary" class="postbox">
						<h3><?php _e('Options', 'usp'); ?></h3>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<p><?php _e('Here you may configure options for USP. See the <code>readme.txt</code> for more information.', 'usp'); ?></p>
							<h4><?php _e('Show/Hide Form Fields', 'usp'); ?></h4>
							<ul class="mm-plain-list">
								<li>
									<select name="usp_options[usp_name]">
										<option <?php if ($usp_options['usp_name'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_name'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('User Name', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_email]">
										<option <?php if ($usp_options['usp_email'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_email'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('User Email', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_url]">
										<option <?php if ($usp_options['usp_url'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_url'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('Post URL', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_title]">
										<option <?php if ($usp_options['usp_title'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_title'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('Post Title', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_tags]">
										<option <?php if ($usp_options['usp_tags'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_tags'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('Post Tags', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_category]">
										<option <?php if ($usp_options['usp_category'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_category'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('Post Category', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_content]">
										<option <?php if ($usp_options['usp_content'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_content'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('Post Content', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_images]">
										<option <?php if ($usp_options['usp_images'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_images'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('Post Images', 'usp'); ?></span>
								</li>
								<li>
									<select name="usp_options[usp_captcha]">
										<option <?php if ($usp_options['usp_captcha'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show', 'usp'); ?></option>
										<option <?php if ($usp_options['usp_captcha'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide', 'usp'); ?></option>
									</select> <span><?php _e('Challenge question (Captcha)', 'usp'); ?></span>
								</li>
							</ul>
							<h4><?php _e('General Form Options', 'usp'); ?></h4>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_form_version]"><?php _e('Form style', 'usp'); ?></label></th>
										<td>
											<?php if (!isset($checked)) $checked = '';
												foreach ($usp_form_version as $usp_form) {
													$radio_setting = $usp_options['usp_form_version'];
													if ('' != $radio_setting) {
														if ($usp_options['usp_form_version'] == $usp_form['value']) {
															$checked = "checked=\"checked\"";
														} else {
															$checked = '';
														}
													} ?>
													<div class="mm-radio-inputs">
														<input type="radio" name="usp_options[usp_form_version]" value="<?php esc_attr_e($usp_form['value']); ?>" <?php echo $checked; ?> /> 
														<?php echo $usp_form['label']; ?>
													</div>
											<?php } ?>
											<div class="mm-item-caption">
												<?php echo __('* If &ldquo;Custom&rdquo; is selected, you must upload your own template files,', 'usp') .
														' <code>/custom/submission-form.php</code> '. __('and', 'usp') .' <code>/custom/usp.css</code>. See the readme.txt for more information. '; ?> 
												<?php _e('Note: list of CSS selectors available at ', 'usp'); ?> <a href="http://m0n.co/e" title="<?php _e('CSS selectors for User Submitted Posts', 'usp'); ?>" target="_blank">http://m0n.co/e</a>
											</div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_include_js]"><?php _e('Include JavaScript?', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_include_js]" <?php if (isset($usp_options['usp_include_js'])) { checked('1', $usp_options['usp_include_js']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want to include the external JavaScript files (recommended).', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_display_url]"><?php _e('Targeted Loading', 'usp'); ?></label></th>
										<td><input type="text" size="45" maxlength="200" name="usp_options[usp_display_url]" value="<?php echo esc_attr($usp_options['usp_display_url']); ?>" />
										<div class="mm-item-caption"><?php _e('When enabled, external CSS &amp; JavaScript files are loaded on every page. Here you may specify the URL of the USP form to load resources only on that page. Note: leave blank to load on all pages.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description"><?php _e('Categories', 'usp'); ?></label></th>
										<td>
											<?php $categories = get_categories(array('hide_empty'=> 0)); ?>
											<?php foreach($categories as $category) { ?>
											
											<div class="mm-radio-inputs">
												<label class="description">
													<input <?php checked(true, in_array($category->term_id, $usp_options['categories'])); ?> type="checkbox" name="usp_options[categories][]" value="<?php echo $category->term_id; ?>" /> 
													<span><?php echo sanitize_text_field($category->name); ?></span>
												</label>
											</div>
											
											<?php } ?>
											<div class="mm-item-caption"><?php _e('Select which categories may be assigned to submitted posts.', 'usp'); ?></div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[author]"><?php _e('Assigned Author', 'usp'); ?></label></th>
										<td>
											<select id="usp_options[author]" name="usp_options[author]">
											<?php global $wpdb; $allAuthors = $wpdb->get_results("SELECT ID, display_name FROM {$wpdb->users}");
												foreach($allAuthors as $author) { ?>
													<option <?php selected($usp_options['author'], $author->ID); ?> value="<?php echo $author->ID; ?>">
														<?php echo $author->display_name; ?>
													</option>
												<?php } ?>
											</select>
											<div class="mm-item-caption"><?php _e('Specify the user that should be assigned as author for user-submitted posts.', 'usp'); ?></div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[number-approved]"><?php _e('Auto Publish?', 'usp'); ?></label></th>
										<td>
											<select name="usp_options[number-approved]">
												<option <?php selected(-1, $usp_options['number-approved']); ?> value="-1"><?php _e('Always moderate', 'usp'); ?></option>
												<option <?php selected( 0, $usp_options['number-approved']); ?> value="0"><?php _e('Always publish immediately', 'usp'); ?></option>
												<?php foreach(range(1, 20) as $value) { ?>
												<option <?php selected($value, $usp_options['number-approved']); ?> value="<?php echo $value; ?>"><?php echo $value; ?></option>
												<?php } ?>
											</select>
											<div class="mm-item-caption"><?php _e('For submitted posts, you can always moderate (recommended), publish immediately, or publish after any number of approved posts.', 'usp'); ?></div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_email_alerts]"><?php _e('Receive Email Alert', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_email_alerts]" <?php if (isset($usp_options['usp_email_alerts'])) { checked('1', $usp_options['usp_email_alerts']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want to be notified via email for new post submissions.', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_richtext_editor]"><?php _e('Enable Rich Text Editor', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_richtext_editor]" <?php if (isset($usp_options['usp_richtext_editor'])) { checked('1', $usp_options['usp_richtext_editor']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want to enable WP rich text editing for submitted posts.', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_featured_images]"><?php _e('Set Uploaded Image as Featured Image', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_featured_images]" <?php if (isset($usp_options['usp_featured_images'])) { checked('1', $usp_options['usp_featured_images']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want to set submitted images as Featured Images (aka Post Thumbnails) for posts. 
											Note: your theme&rsquo;s single.php file must include', 'usp'); ?> <code>the_post_thumbnail()</code> <?php _e('to display Featured Images.', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_email_address]"><?php _e('Email Address for Alerts', 'usp'); ?></label></th>
										<td><input type="text" size="45" maxlength="200" name="usp_options[usp_email_address]" value="<?php echo esc_attr($usp_options['usp_email_address']); ?>" />
										<div class="mm-item-caption"><?php _e('If you checked the box to receive email alerts, indicate here the address(es) to which the emails should be sent.', 'usp'); ?> 
										<?php _e('Tip: multiple addresses may be included using a comma, like so:', 'usp'); ?> <code>email01@example.com</code>, <code>email02@example.com</code>, <code>email03@example.com</code></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[redirect-url]"><?php _e('Redirect URL', 'usp'); ?></label></th>
										<td><input type="text" size="45" maxlength="200" name="usp_options[redirect-url]" value="<?php echo esc_attr($usp_options['redirect-url']); ?>" />
										<div class="mm-item-caption"><?php _e('Specify a URL to redirect the user after post-submission. Note: leave blank to redirect back to current page.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[success-message]"><?php _e('Success Message', 'usp'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="50" name="usp_options[success-message]"><?php echo esc_attr($usp_options['success-message']); ?></textarea> 
										<div class="mm-item-caption"><?php _e('This is the success message that is displayed if post-submission is successful.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[error-message]"><?php _e('Error Message', 'usp'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="50" name="usp_options[error-message]"><?php echo esc_attr($usp_options['error-message']); ?></textarea> 
										<div class="mm-item-caption"><?php _e('This is the error message that is displayed if post-submission fails.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_form_content]"><?php _e('Custom Content', 'usp'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="50" name="usp_options[usp_form_content]"><?php echo esc_attr($usp_options['usp_form_content']); ?></textarea> 
										<div class="mm-item-caption"><?php _e('Here you may specify custom text/markup to be included before the submission form. Note: leave blank to disable.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[titles_unique]"><?php _e('Unique Titles', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[titles_unique]" <?php if (isset($usp_options['titles_unique'])) { checked('1', $usp_options['titles_unique']); } ?> />
										<span class="mm-item-caption"><?php _e('Require submitted post titles to be unique (useful for preventing multiple/duplicate submitted posts).', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[disable_required]"><?php _e('Disable Required', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[disable_required]" <?php if (isset($usp_options['disable_required'])) { checked('1', $usp_options['disable_required']); } ?> />
										<span class="mm-item-caption"><?php _e('Disable all required attributes on default form fields (useful for troubleshooting error messages).', 'usp'); ?></span></td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Registered User Info', 'usp'); ?></h4>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_use_author]"><?php _e('Use registered username for author?', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_use_author]" <?php if (isset($usp_options['usp_use_author'])) { checked('1', $usp_options['usp_use_author']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want to automatically use the registered username as the submitted-post author. Note: this really should only be used when requiring log-in for submissions.', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_use_url]"><?php _e('Use registered URL for submitted URL?', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_use_url]" <?php if (isset($usp_options['usp_use_url'])) { checked('1', $usp_options['usp_use_url']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want to automatically use the registered user&rsquo;s specified URL as the submitted-post URL. Note: this really should only be used when requiring log-in for submissions.', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_use_cat]"><?php _e('Use a hidden field for submitted category?', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_use_cat]" <?php if (isset($usp_options['usp_use_cat'])) { checked('1', $usp_options['usp_use_cat']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want to use a hidden category field for the submitted category. Note: this may be used to specify a default category for submitted posts when the category field is hidden.', 'usp'); ?></span></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_use_cat_id]"><?php _e('Category ID for hidden field', 'usp'); ?></label></th>
										<td><input class="input-short" type="text" size="45" maxlength="200" name="usp_options[usp_use_cat_id]" value="<?php echo esc_attr($usp_options['usp_use_cat_id']); ?>" />
										<div class="mm-item-caption"><?php _e('Specify a cateogry (ID) to use as the default category when using the &ldquo;hidden field&rdquo; option.', 'usp'); ?></div></td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Challenge Question (Captcha)', 'usp'); ?></h4>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_question]"><?php _e('Challenge Question', 'usp'); ?></label></th>
										<td><input type="text" size="45" maxlength="200" name="usp_options[usp_question]" value="<?php echo esc_attr($usp_options['usp_question']); ?>" />
										<div class="mm-item-caption"><?php _e('To prevent spam, enter a question that users must answer before submitting the form.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_response]"><?php _e('Challenge Response', 'usp'); ?></label></th>
										<td><input type="text" size="45" maxlength="200" name="usp_options[usp_response]" value="<?php echo esc_attr($usp_options['usp_response']); ?>" />
										<div class="mm-item-caption"><?php _e('Enter the <em>only</em> correct answer to the challenge question.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_casing]"><?php _e('Case-sensitivity', 'usp'); ?></label></th>
										<td><input type="checkbox" value="1" name="usp_options[usp_casing]" <?php if (isset($usp_options['usp_casing'])) { checked('1', $usp_options['usp_casing']); } ?> />
										<span class="mm-item-caption"><?php _e('Check this box if you want the challenge response to be case-sensitive.', 'usp'); ?></span></td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Image Uploads', 'usp'); ?></h4>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="usp_options[upload-message]"><?php _e('Upload Message', 'usp'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="50" name="usp_options[upload-message]"><?php echo esc_attr($usp_options['upload-message']); ?></textarea>
										<div class="mm-item-caption"><?php _e('This is the message that appears next to upload field. Useful to state your upload guidelines/rules/etc.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[usp_add_another]"><?php _e('&ldquo;Add another image&rdquo; link', 'usp'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="50" name="usp_options[usp_add_another]"><?php echo esc_attr($usp_options['usp_add_another']); ?></textarea>
										<div class="mm-item-caption"><?php _e('Here you may specify your own custom markup for the &ldquo;Add another image&rdquo; link (leave blank to use the default markup).', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[min-images]"><?php _e('Minimum number of images', 'usp'); ?></label></th>
										<td>
											<input name="usp_options[min-images]" type="number" step="1" min="0" max="999" maxlength="3" value="<?php echo $usp_options['min-images']; ?>" />
											<div class="mm-item-caption inline"><?php _e('Specify the <em>minimum</em> number of images.', 'usp'); ?></div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[max-images]"><?php _e('Maximum number of images', 'usp'); ?></label></th>
										<td>
											<input name="usp_options[max-images]" type="number" step="1" min="0" max="999" maxlength="3" value="<?php echo $usp_options['max-images']; ?>" />
											<div class="mm-item-caption inline"><?php _e('Specify the <em>maximum</em> number of images.', 'usp'); ?></div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[min-image-width]"><?php _e('Minimum image width', 'usp'); ?></label></th>
										<td><input class="input-short" type="text" size="5" maxlength="200" name="usp_options[min-image-width]" value="<?php echo esc_attr($usp_options['min-image-width']); ?>" />
										<div class="mm-item-caption inline"><?php _e('Specify a <em>minimum width</em> (in pixels) for uploaded images.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[min-image-height]"><?php _e('Minimum image height', 'usp'); ?></label></th>
										<td><input class="input-short" type="text" size="5" maxlength="200" name="usp_options[min-image-height]" value="<?php echo esc_attr($usp_options['min-image-height']); ?>" />
										<div class="mm-item-caption inline"><?php _e('Specify a <em>minimum height</em> (in pixels) for uploaded images.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[max-image-width]"><?php _e('Maximum image width', 'usp'); ?></label></th>
										<td><input class="input-short" type="text" size="5" maxlength="200" name="usp_options[max-image-width]" value="<?php echo esc_attr($usp_options['max-image-width']); ?>" />
										<div class="mm-item-caption inline"><?php _e('Specify a <em>maximum width</em> (in pixels) for uploaded images.', 'usp'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="usp_options[max-image-height]"><?php _e('Maximum image height', 'usp'); ?></label></th>
										<td><input class="input-short" type="text" size="5" maxlength="200" name="usp_options[max-image-height]" value="<?php echo esc_attr($usp_options['max-image-height']); ?>" />
										<div class="mm-item-caption inline"><?php _e('Specify a <em>maximum height</em> (in pixels) for uploaded images.', 'usp'); ?></div></td>
									</tr>
								</table>
							</div>
							<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'usp'); ?>" />
						</div>
					</div>
					<div id="mm-panel-secondary" class="postbox">
						<h3><?php _e('Shortcode &amp; Template Tag', 'usp'); ?></h3>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">

							<h4><?php _e('Shortcode', 'usp'); ?></h4>
							<p><?php _e('Use this shortcode to display the USP Form on any post or page:', 'usp'); ?></p>
							<p><code class="mm-code">[user-submitted-posts]</code></p>

							<h4><?php _e('Template tag', 'usp'); ?></h4>
							<p><?php _e('Use this template tag to display the USP Form anywhere in your theme template:', 'usp'); ?></p>
							<p><code class="mm-code">&lt;?php if (function_exists('user_submitted_posts')) user_submitted_posts(); ?&gt;</code></p>
						</div>
					</div>
					<div id="mm-restore-settings" class="postbox">
						<h3><?php _e('Restore Default Options', 'usp'); ?></h3>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<p>
								<input name="usp_options[default_options]" type="checkbox" value="1" id="mm_restore_defaults" <?php if (isset($usp_options['default_options'])) { checked('1', $usp_options['default_options']); } ?> /> 
								<label class="description" for="usp_options[default_options]"><?php _e('Restore default options upon plugin deactivation/reactivation.', 'usp'); ?></label>
							</p>
							<p>
								<small>
									<?php _e('<strong>Tip:</strong> leave this option unchecked to remember your settings. Or, to go ahead and restore all default options, check the box, save your settings, and then deactivate/reactivate the plugin.', 'usp'); ?>
								</small>
							</p>
							<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'usp'); ?>" />
						</div>
					</div>
					<div id="mm-panel-current" class="postbox">
						<h3><?php _e('Updates &amp; Info', 'usp'); ?></h3>
						<div class="toggle">
							<div id="mm-iframe-wrap">
								<iframe src="https://perishablepress.com/current/index-usp.html"></iframe>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="mm-credit-info">
				<a target="_blank" href="<?php echo $usp_homeurl; ?>" title="<?php echo $usp_plugin; ?> Homepage"><?php echo $usp_plugin; ?></a> by 
				<a target="_blank" href="http://twitter.com/perishable" title="Jeff Starr on Twitter">Jeff Starr</a> @ 
				<a target="_blank" href="http://monzilla.biz/" title="Obsessive Web Design &amp; Development">Monzilla Media</a>
			</div>
		</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			// toggle panels
			jQuery('.default-hidden').hide();
			jQuery('#mm-panel-toggle a').click(function(){
				jQuery('.toggle').slideToggle(300);
				return false;
			});
			jQuery('h3').click(function(){
				jQuery(this).next().slideToggle(300);
			});
			jQuery('#mm-panel-primary-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#mm-panel-primary .toggle').slideToggle(300);
				return true;
			});
			jQuery('#mm-panel-secondary-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#mm-panel-secondary .toggle').slideToggle(300);
				return true;
			});
			//dismiss_alert
			if (!jQuery('.dismiss-alert-wrap input').is(':checked')){
				jQuery('.dismiss-alert-wrap input').one('click',function(){
					jQuery('.dismiss-alert-wrap').after('<input type="submit" class="button-secondary" value="<?php _e('Save Preference', 'gap'); ?>" />');
				});
			}
			// prevent accidents
			if(!jQuery("#mm_restore_defaults").is(":checked")){
				jQuery('#mm_restore_defaults').click(function(event){
					var r = confirm("<?php _e('Are you sure you want to restore all default options? (this action cannot be undone)', 'usp'); ?>");
					if (r == true){  
						jQuery("#mm_restore_defaults").attr('checked', true);
					} else {
						jQuery("#mm_restore_defaults").attr('checked', false);
					}
				});
			}
		});
	</script>

<?php }
