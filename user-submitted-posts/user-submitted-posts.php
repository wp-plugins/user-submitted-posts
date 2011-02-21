<?php 
/*
Plugin Name: User Submitted Posts
Author: Jeff Starr @ Perishable Press
Author URI: http://perishablepress.com/
Plugin URI: http://perishablepress.com/user-submitted-posts/
Description: The User Submitted Posts plugin enables your visitors to submit posts from anywhere on your site.
Version: 1.0

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA

*/

if (!class_exists('Public_Submission_Form')) {
	class Public_Submission_Form {
		var $version = '1.0';
		var $_post_meta_IsSubmission = 'is_submission';
		var $_post_meta_Submitter    = 'user_submit_name';
		var $_post_meta_SubmitterUrl = 'user_submit_url';
		var $_post_meta_SubmitterIp  = 'user_submit_ip';
		var $_post_meta_Image        = 'user_submit_image';
		var $_post_meta_ImageInfo    = 'user_submit_image_info';
		var $settings = null;

		function Public_Submission_Form() {
			register_activation_hook(__FILE__, array(&$this, 'saveDefaultSettings'));
			add_action('admin_init', array(&$this, 'checkForSettingsSave'));
			add_action('admin_menu', array(&$this, 'addAdministrativeElements'));
			add_action('init', array(&$this, 'enqueueResources'));
			add_action('parse_request', array(&$this, 'checkForPublicSubmission'));
			add_action('parse_query', array(&$this, 'addSubmittedStatusClause'));
			add_action('restrict_manage_posts', array(&$this, 'outputUserSubmissionLink'));
			add_filter('the_author', array(&$this, 'replaceAuthor'));
			add_filter('the_author_link', array(&$this, 'replaceAuthorLink'));
			add_filter('post_stati', array(&$this, 'addNewPostStatus'));
			add_shortcode('user-submitted-posts', array(&$this, 'getPublicSubmissionForm'));
		}
		function addAdministrativeElements() {
			add_options_page(__('User Submitted Posts'), __('User Submitted Posts'), 'manage_options', 'user-submitted-posts', array(&$this, 'displaySettingsPage'));
		}
		function addNewPostStatus($postStati) {
			$postStati['submitted'] = array(__('Submitted'), __('User submitted posts'), _n_noop('Submitted', 'Submitted'));
			return $postStati;
		}
		function addSubmittedStatusClause($wp_query) {
			global $pagenow;
			if (is_admin() && $pagenow == 'edit.php' && $_GET['user_submitted'] == '1') {
				set_query_var('meta_key', $this->_post_meta_IsSubmission);
				set_query_var('meta_value', 1);
				set_query_var('post_status', 'pending');
			}
		}
		function checkForPublicSubmission() {
			if (isset($_POST['user-submitted-post']) && ! empty($_POST['user-submitted-post'])) {
				$settings = $this->getSettings();
				$title = stripslashes($_POST['user-submitted-title']);
				$content = stripslashes($_POST['user-submitted-content']);
				$authorName = stripslashes($_POST['user-submitted-name']);
				$authorUrl = stripslashes($_POST['user-submitted-url']);
				$tags = stripslashes($_POST['user-submitted-tags']);
				$category = intval($_POST['user-submitted-category']);
				$fileData = $_FILES['user-submitted-image'];
				$publicSubmission = $this->createPublicSubmission($title, $content, $authorName, $authorUrl, $tags, $category, $fileData);

				if (false == ($publicSubmission)) {
					$errorMessage = empty($settings['error-message']) ? __('An error occurred.  Please go back and try again.') : $settings['error-message'];
					if( !empty( $_POST[ 'redirect-override' ] ) ) {
						$redirect = stripslashes( $_POST[ 'redirect-override' ] );
						$redirect = add_query_arg( array( 'submission-error' => '1' ), $redirect );
						wp_redirect( $redirect );
						exit();
					}
					wp_die($errorMessage);
				} else {
					$redirect = empty($settings['redirect-url']) ? $_SERVER['REQUEST_URI'] : $settings['redirect-url'];
					if (! empty($_POST['redirect-override'])) {
						$redirect = stripslashes($_POST['redirect-override']);
					}
					$redirect = add_query_arg(array('success'=>1), $redirect);
					wp_redirect($redirect);
					exit();
				}
			}
		}
		function checkForSettingsSave() {
			if (isset($_POST['save-post-submission-settings']) && current_user_can('manage_options') && check_admin_referer('save-post-submission-settings')) {
				$settings = $this->getSettings();

				$settings['author'] = get_userdata($_POST['author']) ? $_POST['author'] : $settings['author'];
				$settings['categories'] = is_array($_POST['categories']) && ! empty($_POST['categories']) ? array_unique($_POST['categories']) : array(get_option('default_category'));
				$settings['number-approved'] = is_numeric($_POST['number-approved']) ? intval($_POST['number-approved']) : - 1;

				$settings['redirect-url'] = stripslashes($_POST['redirect-url']);
				$settings['error-message'] = stripslashes($_POST['error-message']);
				
				$settings['min-images'] = is_numeric($_POST['min-images']) ? intval($_POST['min-images']) : $settings['max-images'];
				$settings['max-images'] = (is_numeric($_POST['max-images']) && ($settings['min-images'] <= $_POST['max-images'])) ? intval($_POST['max-images']) : $settings['max-images'];
				
				$settings['min-image-height'] = is_numeric($_POST['min-image-height']) ? intval($_POST['min-image-height']) : $settings['min-image-height'];
				$settings['min-image-width'] = is_numeric($_POST['min-image-width']) ? intval($_POST['min-image-width']) : $settings['min-image-width'];
				
				$settings['max-image-height'] = (is_numeric($_POST['max-image-height']) && ($settings['min-image-height'] <= $_POST['max-image-height'])) ? intval($_POST['max-image-height']) : $settings['max-image-height'];
				$settings['max-image-width'] = (is_numeric($_POST['max-image-width']) && ($settings['min-image-width'] <= $_POST['max-image-width'])) ? intval($_POST['max-image-width']) : $settings['max-image-width'];

				$settings['usp_name'] = stripslashes($_POST['usp_name']);
				$settings['usp_url'] = stripslashes($_POST['usp_url']);
				$settings['usp_title'] = stripslashes($_POST['usp_title']);
				$settings['usp_tags'] = stripslashes($_POST['usp_tags']);
				$settings['usp_category'] = stripslashes($_POST['usp_category']);
				$settings['usp_content'] = stripslashes($_POST['usp_content']);
				$settings['usp_images'] = stripslashes($_POST['usp_images']);

				$settings['upload-message'] = stripslashes($_POST['upload-message']);
				$settings['usp_form_width'] = stripslashes($_POST['usp_form_width']);

				$this->saveSettings($settings);
				wp_redirect(admin_url('options-general.php?page=user-submitted-posts&updated=1'));
			}
		}
		function displaySettingsPage() {
			include ('views/settings.php');
		}
		function enqueueResources() {
			wp_enqueue_script('usp_script', WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/resources/user-submitted-posts.js', array('jquery'), $this->version);
			wp_enqueue_style('usp_style', WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/resources/user-submitted-posts.css', false, $this->version, 'screen');
		}
		function getPublicSubmissionForm($atts = array(), $content = null) {
			if ($atts === true) {
				$redirect = $this->currentPageURL();
			}
			ob_start();
			include (WP_PLUGIN_DIR.'/'.basename(dirname(__FILE__)).'/views/submission-form.php');
			return ob_get_clean();
		}
		function outputUserSubmissionLink() {
			global $pagenow;
			if ($pagenow == 'edit.php') {
				echo '<a id="usp_admin_filter_posts" class="button-secondary" href="'.admin_url('edit.php?post_status=pending&amp;user_submitted=1').'">'.__('User Submitted Posts').'</a>';
			}
		}
		function replaceAuthor($author) {
			global $post;
			$isSubmission = get_post_meta($post->ID, $this->_post_meta_IsSubmission, true);
			$submissionAuthor = get_post_meta($post->ID, $this->_post_meta_Submitter, true);
			if ($isSubmission && ! empty($submissionAuthor)) {
				return $submissionAuthor;
			} else {
				return $author;
			}
		}
		function replaceAuthorLink($authorLink) {
			global $post;
			$isSubmission = get_post_meta($post->ID, $this->_post_meta_IsSubmission, true);
			$submissionAuthor = get_post_meta($post->ID, $this->_post_meta_Submitter, true);
			$submissionLink = get_post_meta($post->ID, $this->_post_meta_SubmitterUrl, true);
			if ($isSubmission && ! empty($submissionAuthor)) {
				if ( empty($submissionLink)) {
					return $submissionAuthor;
				} else {
					return "<a href='{$submissionLink}'>{$submissionAuthor}</a>";
				}
			} else {
				return $authorLink;
			}
		}
		function saveDefaultSettings() {
			$settings = $this->getSettings();
			if ( empty($settings)) {
				$currentUser = wp_get_current_user();
				
				$settings = array();
				$settings['author'] = $currentUser->ID;
				$settings['categories'] = array(get_option('default_category'));
				$settings['number-approved'] = -1;
				
				$settings['redirect-url'] = ''; //site_url();
				$settings['error-message'] = __('There was an error. Please ensure that you have added a title, some content, and that you have uploaded only images.');
				
				$settings['min-images'] = 0;
				$settings['max-images'] = 1;
				
				$settings['min-image-height'] = 0;
				$settings['min-image-width'] = 0;
				
				$settings['max-image-height'] = 500;
				$settings['max-image-width'] = 500;
				
				$settings['usp_name'] = 'show';
				$settings['usp_url'] = 'show';
				$settings['usp_title'] = 'show';
				$settings['usp_tags'] = 'show';
				$settings['usp_category'] = 'show';
				$settings['usp_content'] = 'show';
				$settings['usp_images'] = 'hide';

				$settings['upload-message'] = ''; // 'Please select your image(s) to upload:';
				$settings['usp_form_width'] = '300'; // in pixels

				$this->saveSettings($settings);
			}
		}
		function getSettings() {
			if ($this->settings === null) {
				$defaults = array();
				$this->settings = get_option('User Submitted Posts Settings', array());
			}
			return $this->settings;
		}
		function saveSettings($settings) {
			if (!is_array($settings)) {
				return;
			}
			$this->settings = $settings;
			update_option('User Submitted Posts Settings', $this->settings);
		}
		function createPublicSubmission($title, $content, $authorName, $authorUrl, $tags, $category, $fileData) {
			$settings = $this->getSettings();
			$authorName = strip_tags($authorName);
			$authorUrl = strip_tags($authorUrl);
			$authorIp = $_SERVER['REMOTE_ADDR'];

			if (!$this->validateTitle($title)) {
				return false;
			}
			if (!$this->validateContent($title)) {
				return false;
			}
			if (!$this->validateTags($tags)) {
				return false;
			}
			$postData = array();
			$postData['post_title'] = $title;
			$postData['post_content'] = $content;
			$postData['post_status'] = 'pending';
			$postData['author'] = $settings['author'];
			$numberApproved = $settings['number-approved'];

			if ($numberApproved < 0) {} elseif ($numberApproved == 0) {
				$postData['post_status'] = 'publish';
			} else {
				$posts = get_posts(array('post_status'=>'publish', 'meta_key'=>$this->_post_meta_Submitter, 'meta_value'=>$authorName));
				$counter = 0;
				foreach ($posts as $post) {
					$submitterUrl = get_post_meta($post->ID, $this->_post_meta_SubmitterUrl, true);
					$submitterIp = get_post_meta($post->ID, $this->_post_meta_SubmitterIp, true);
					if ($submitterUrl == $authorUrl && $submitterIp == $authorIp) {
						$counter++;
					}
				}
				if ($counter >= $numberApproved) {
					$postData['post_status'] = 'publish';
				}
			}
			$newPost = wp_insert_post($postData);

			if ($newPost) {
				wp_set_post_tags($newPost, $tags);
				wp_set_post_categories($newPost, array($category));
			
			if (!function_exists('media_handle_upload')) {
				require_once (ABSPATH.'/wp-admin/includes/media.php');
				require_once (ABSPATH.'/wp-admin/includes/file.php');
				require_once (ABSPATH.'/wp-admin/includes/image.php');
			}
			$attachmentIds = array();
			$imageCounter = 0;
			for ($i = 0; $i < count($fileData['name']); $i++) {
				$imageInfo = getimagesize($fileData['tmp_name'][$i]);
				if (false === $imageInfo || !$this->imageIsRightSize($imageInfo[0], $imageInfo[1])) {
					continue;
				}
				$key = "public-submission-attachment-{$i}";
				$_FILES[$key] = array();
				$_FILES[$key]['name'] = $fileData['name'][$i];
				$_FILES[$key]['tmp_name'] = $fileData['tmp_name'][$i];
				$_FILES[$key]['type'] = $fileData['type'][$i];
				$_FILES[$key]['error'] = $fileData['error'][$i];
				$_FILES[$key]['size'] = $fileData['size'][$i];
				$attachmentId = media_handle_upload($key, $newPost);

				if (!is_wp_error($attachmentId) && wp_attachment_is_image($attachmentId)) {
					$attachmentIds[] = $attachmentId;
					add_post_meta($newPost, $this->_post_meta_Image, wp_get_attachment_url($attachmentId));
					$imageCounter++;
				} else {
					wp_delete_attachment($attachmentId);
				}
				if ($imageCounter == $settings['max-images']) {
					break;
				}
			}
			if (count($attachmentIds) < $settings['min-images']) {
				foreach ($attachmentIds as $idToDelete) {
					wp_delete_attachment($idToDelete);
				}
				wp_delete_post($newPost);
				return false;
			}
			update_post_meta($newPost, $this->_post_meta_IsSubmission, true);
			update_post_meta($newPost, $this->_post_meta_Submitter, htmlentities(($authorName)));
			update_post_meta($newPost, $this->_post_meta_SubmitterUrl, htmlentities(($authorUrl)));
			update_post_meta($newPost, $this->_post_meta_SubmitterIp, $authorIp);
		}
		return $newPost;
	}
	function imageIsRightSize($width, $height) {
		$settings = $this->getSettings();
		$widthFits = ($width <= intval($settings['max-image-width'])) && ($width >= $settings['min-image-width']);
		$heightFits = ($height <= $settings['max-image-height']) && ($height >= $settings['min-image-height']);
		return $widthFits && $heightFits;
		}
		function validateContent($content) {
			return ! empty($content);
		}
		function validateTags($tags) {
			return true;
		}
		function validateTitle($title) {
			return ! empty($title);
		}
		function currentPageURL() {
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
			return $pageURL;
		}
	}
	$publicSubmissionForm = new Public_Submission_Form();
	include ('library/template-tags.php');
}
?>