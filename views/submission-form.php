<?php // User Submitted Posts - HTML5 Submission Form

global $usp_options, $current_user;

$author_ID  = $usp_options['author'];
$default_author = get_the_author_meta('display_name', $author_ID);
if ($authorName == $default_author) {
	$authorName = '';
} ?>

<!-- User Submitted Posts @ http://perishablepress.com/user-submitted-posts/ -->
<div id="user-submitted-posts">

	<?php if ($usp_options['usp_form_content'] !== '') {
		echo $usp_options['usp_form_content'];
	} ?>

	<form method="post" enctype="multipart/form-data" action="">

		<?php if($_GET['submission-error'] == '1') { ?>
		<div id="usp-error-message"><?php echo $usp_options['error-message']; ?></div>
		<?php } ?>

		<?php if($_GET['success'] == '1') { ?>
		<div id="usp-success-message"><?php echo $usp_options['success-message']; ?></div>
		<?php } else { ?>

		<?php if (($usp_options['usp_name'] == 'show') && ($usp_options['usp_use_author'] == false)) { ?>
		<fieldset class="usp-name">
			<label for="user-submitted-name"><?php _e('Your Name'); ?></label>
			<input name="user-submitted-name" type="text" value="" placeholder="<?php _e('Your Name'); ?>">
		</fieldset>
		<?php } if (($usp_options['usp_url'] == 'show') && ($usp_options['usp_use_url'] == false)) { ?>
		<fieldset class="usp-url">
			<label for="user-submitted-url"><?php _e('Your URL'); ?></label>
			<input name="user-submitted-url" type="text" value="" placeholder="<?php _e('Your URL'); ?>">
		</fieldset>
		<?php } if ($usp_options['usp_title'] == 'show') { ?>
		<fieldset class="usp-title">
			<label for="user-submitted-title"><?php _e('Post Title'); ?></label>
			<input name="user-submitted-title" type="text" value="" placeholder="<?php _e('Post Title'); ?>">
		</fieldset>
		<?php } if ($usp_options['usp_tags'] == 'show') { ?>
		<fieldset class="usp-tags">
			<label for="user-submitted-tags"><?php _e('Post Tags'); ?></label>
			<input name="user-submitted-tags" id="user-submitted-tags" type="text" value="" placeholder="<?php _e('Post Tags'); ?>">
		</fieldset>
		<?php } if ($usp_options['usp_captcha'] == 'show') { ?>
		<fieldset class="usp-captcha">
			<label for="user-submitted-captcha"><?php echo $usp_options['usp_question']; ?></label>
			<input name="user-submitted-captcha" type="text" value="" placeholder="<?php echo $usp_options['usp_question']; ?>">
		</fieldset>
		<?php } if (($usp_options['usp_category'] == 'show') && ($usp_options['usp_use_cat'] == false)) { ?>
		<fieldset class="usp-category">
			<label for="user-submitted-category"><?php _e('Post Category'); ?></label>
			<select name="user-submitted-category">
				<?php foreach($usp_options['categories'] as $categoryId) { $category = get_category($categoryId); if(!$category) { continue; } ?>
				<option value="<?php echo $categoryId; ?>"><?php $category = get_category($categoryId); echo htmlentities($category->name); ?></option>
				<?php } ?>
			</select>
		</fieldset>
		<?php } if ($usp_options['usp_content'] == 'show') { ?>
		<fieldset class="usp-content">
			<label for="user-submitted-content"><?php _e('Post Content'); ?></label>
			<textarea name="user-submitted-content" rows="5" placeholder="<?php _e('Post Content'); ?>"></textarea>
		</fieldset>
		<?php } if ($usp_options['usp_images'] == 'show') { ?>
		<?php if ($usp_options['max-images'] !== 0) { ?>
		<fieldset class="usp-images">
			<label for="user-submitted-image"><?php _e('Upload an Image'); ?></label>
			<div id="usp-upload-message"><?php echo $usp_options['upload-message']; ?></div>
			<div id="user-submitted-image">
				<?php if($usp_options['min-images'] < 1) {
					$numberImages = 1;
				} else {
					$numberImages = $usp_options['min-images'];
				} for($i = 0; $i < $numberImages; $i++) { ?>
				<input name="user-submitted-image[]" type="file" size="25" class="usp-clone">
				<?php } ?>
				<a href="#" id="usp_add-another"><?php _e('Add another image'); ?></a>
			</div>
		</fieldset>
		<?php } ?>
		<?php } ?>
		<fieldset id="coldform_verify" style="display:none;">
			<label for="user-submitted-verify">Human verification: leave this field empty.</label>
			<input name="user-submitted-verify" type="text" value="">
		</fieldset>
		<div id="usp-submit">
			<?php if (!empty($usp_options['redirect-url'])) { ?>
			<input type="hidden" name="redirect-override" value="<?php echo $usp_options['redirect-url']; ?>">
			<?php } ?>
			<?php if ($usp_options['usp_use_author'] == true) { ?>
			<input class="hidden" type="hidden" name="user-submitted-name" value="<?php echo $current_user->user_login; ?>">
			<?php } ?>
			<?php if ($usp_options['usp_use_url'] == true) { ?>
			<input class="hidden" type="hidden" name="user-submitted-url" value="<?php echo $current_user->user_url; ?>">
			<?php } ?>
			<?php if ($usp_options['usp_use_cat'] == true) { ?>
			<input class="hidden" type="hidden" name="user-submitted-category" value="<?php echo $usp_options['usp_use_cat_id']; ?>">
			<?php } ?>
			<input name="user-submitted-post" type="submit" value="<?php _e('Submit Post'); ?>">
		</div>

		<?php } ?>

	</form>
</div>
<script>(function(){var e = document.getElementById("coldform_verify");e.parentNode.removeChild(e);})();</script>
<!-- User Submitted Posts @ http://perishablepress.com/user-submitted-posts/ -->