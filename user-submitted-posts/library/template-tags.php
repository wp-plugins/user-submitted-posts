<?php // User Submitted Posts Plugin by Jeff Starr @ http://perishablepress.com/user-submitted-posts/

function is_public_submission($postId = false) {
	global $publicSubmissionForm;
	if(false === $postId) {
		global $post;
		$postId = $post->ID;
	}
	if(get_post_meta($postId, $publicSubmissionForm->_post_meta_IsSubmission, true) == true) {
		return true;
	} else {
		return false;
	}
}

function get_public_submission_form($overrideRedirect = false) {
	global $publicSubmissionForm;
	return $publicSubmissionForm->getPublicSubmissionForm($overrideRedirect);
}

function public_submission_form($overrideRedirect = false) {
	echo get_public_submission_form($overrideRedirect);
}

function get_post_images($postId = false) {
	if(is_public_submission($postId)) {
		if(false === $postId) {
			global $post;
			$postId = $post->ID;
		}
		global $publicSubmissionForm;
		return get_post_meta($postId, $publicSubmissionForm->_post_meta_Image);
	} else {
		return array();
	}
}

function post_attachments($size = 'full', $beforeUrl = '<img src="', $afterUrl = '" />', $numberImages = false, $postId = false) {
	if(false === $postId) {
		global $post;
		$postId = $post->ID;
	}
	if(!in_array($size, array('thumbnail', 'medium', 'large', 'full'))) {
		$size = 'full';
	}
	if(false === $numberImages || !is_numeric($numberImages)) {
		$numberImages = 99;
	}
	$attachments = get_posts(array('post_type'=>'attachment', 'post_parent'=>$postId, 'post_status'=>'inherit', 'numberposts'=>$numberImages));
	foreach($attachments as $attachment) {
		$info = wp_get_attachment_image_src($attachment->ID, $size);

		echo $beforeUrl . $info[0] . $afterUrl;
	}
}

?>