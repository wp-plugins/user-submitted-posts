<?php // User Submitted Posts - Classic Submission Form

global $usp_options; 

session_start();
$title      = $_SESSION['title'];
$content    = $_SESSION['content'];
$authorName = $_SESSION['authorName'];
$authorUrl  = $_SESSION['authorUrl'];
$tags       = $_SESSION['tags'];
$captcha    = $_SESSION['captcha'];
$category   = $_SESSION['category'];

$author_ID  = $usp_options['author'];
$default_author = get_the_author_meta('display_name', $author_ID);

if ($authorName == $default_author) {
	$authorName = '';
} ?>

<!-- User Submitted Posts @ http://perishablepress.com/user-submitted-posts/ -->
<div id="usp">
	<form id="usp_form" method="post" enctype="multipart/form-data" action="">

		<?php if($_GET['submission-error'] == '1') { ?>
		<div id="usp_error_message"><?php echo $usp_options['error-message']; ?></div>
		<?php } ?>
		<?php if($_GET['success'] == '1') { ?>
		<div id="usp_success_message"><?php echo $usp_options['success-message']; ?></div>
		<?php } else { ?>

		<ul id="usp_list">
			<?php if ($usp_options['usp_name'] == 'show') { ?>
			<li class="usp_name">
				<label for="user-submitted-name" class="usp_label"><?php _e('Your Name'); ?></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-name" id="user-submitted-name" value="<?php echo $authorName; ?>" />
				</div>
			</li>
			<?php } if ($usp_options['usp_url'] == 'show') { ?>
			<li class="usp_url">
				<label for="user-submitted-url" class="usp_label"><?php _e('Your URL'); ?></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-url" id="user-submitted-url" value="<?php echo $authorUrl; ?>" />
				</div>
			</li>
			<?php } if ($usp_options['usp_title'] == 'show') { ?>
			<li class="usp_title">
				<label for="user-submitted-title" class="usp_label"><?php _e('Post Title'); ?></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-title" id="user-submitted-title" value="<?php echo $title; ?>" />
				</div>
			</li>
			<?php } if ($usp_options['usp_tags'] == 'show') { ?>
			<li class="usp_tags">
				<label for="user-submitted-tags" class="usp_label"><?php _e('Post Tags'); ?> <small><?php _e('(separate with commas)'); ?></small></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-tags" id="user-submitted-tags" value="<?php echo $tags; ?>" />
				</div>
			</li>
			<?php } if ($usp_options['usp_category'] == 'show') { ?>
			<li class="usp_category">
				<label for="user-submitted-category" class="usp_label"><?php _e('Post Category'); ?></label>
				<div> 
					<select class="usp_select" name="user-submitted-category" id="user-submitted-category">
						
						<?php foreach($usp_options['categories'] as $categoryId) { $category = get_category($categoryId); if(!$category) { continue; } ?>
						<option class="usp_option" value="<?php echo $categoryId; ?>"><?php $category = get_category($categoryId); echo htmlentities($category->name); ?></option>
						<?php } ?>
					</select>
				</div>
			</li>
			<?php } if ($usp_options['usp_captcha'] == 'show') { ?>
			<li class="usp_captcha">
				<label for="user-submitted-captcha" class="usp_label"><?php echo $usp_options['usp_question']; ?></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-captcha" id="user-submitted-captcha" value="<?php echo $captcha; ?>" />
				</div>
			</li>
			<?php } if ($usp_options['usp_content'] == 'show') { ?>
			<li class="usp_content">
				<label for="user-submitted-content" class="usp_label"><?php _e('Post Content'); ?></label>
				<div>
					<textarea class="usp_textarea" name="user-submitted-content" id="user-submitted-content" rows="5"><?php echo $content; ?></textarea>
				</div>
			</li>
			<?php } if ($usp_options['usp_images'] == 'show') { ?>
				<?php if($usp_options['max-images'] !== 0) { ?>
				<li class="usp_images">
					<label for="user-submitted-image" class="usp_label"><?php _e('Upload an Image'); ?></label>
					<div id="usp_upload-message"><?php echo $usp_options['upload-message']; ?></div>
					<div>
						<?php 
						if($usp_options['min-images'] < 1) {
							$numberImages = 1;
						} else {
							$numberImages = $usp_options['min-images'];
						}
						for($i = 0; $i < $numberImages; $i++) { ?>
						<input class="usp_input usp_clone" type="file" size="25" id="user-submitted-image" name="user-submitted-image[]" />
						<?php } ?>
						<a href="#" id="usp_add-another"><?php _e('Add another image'); ?></a>
					</div>
				</li>
				<?php } ?>
			<?php } ?>
			<li id="coldform_verify" style="display:none;">
				<label for="user-submitted-verify">Human verification: leave this field empty.</label>
				<input name="user-submitted-verify" type="text" value="" />
			</li>
			<li class="usp_submit">
				<?php if(!empty($usp_options['redirect-url'])) { ?>
					<input type="hidden" name="redirect-override" value="<?php echo $usp_options['redirect-url']; ?>" />
				<?php } ?>
				<input class="usp_input" type="submit" name="user-submitted-post" id="user-submitted-post" value="<?php _e('Submit Post'); ?>" />
			</li>
		</ul>
	</form>
</div>
<div style="clear:both;"></div>
<script>(function(){var e = document.getElementById("coldform_verify");e.parentNode.removeChild(e);})();</script>
<!-- User Submitted Posts @ http://perishablepress.com/user-submitted-posts/ -->

<?php } ?>