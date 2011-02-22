<?php // User Submitted Posts Plugin by Jeff Starr @ http://perishablepress.com/user-submitted-posts/

$settings = $this->getSettings();
global $pagenow;

?>
<style type="text/css">
	#wrap { width: 700px; }
	#wrap code { margin: 0; padding: 0; }
	#usp_admin_info { padding-right: 340px; background: url(<?php echo WP_PLUGIN_URL.'/user-submitted-posts/images/usp_logo-admin.jpg'; ?>) no-repeat 400px 0; }
	#usp_admin_form #usp_admin_table { width: 700px; }
	#usp_admin_form #usp_admin_table td { display: block; margin: 5px 10px; padding: 20px; border: 1px solid #ddd; background-color: #f8f8f8; }
	#usp_admin_form #usp_admin_table h3 { margin: 0; }
	#redirect-url { width: 500px; }
</style>
<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery('#usp_admin_toggle').toggle(function(){ 
			jQuery('#usp_admin_info').slideUp(300); 
			jQuery(this).html('Expand Info &darr;');
			return false;
		},function(){ 
			jQuery('#usp_admin_info').slideDown(300); 
			jQuery(this).html('Collapse Info &uarr;');
			return false;
		});
	});
</script>
<div id="wrap" class="wrap">
	<h2><?php _e('User Submitted Posts'); ?></h2>
	<div id="usp_admin_info">
		<p>
			<?php _e('<strong>Thanks</strong> for using <em>User Submitted Posts</em>! Please use this page to customize plugin settings. For more information, see the <code>readme.txt</code> 
			file or visit <a href="http://perishablepress.com/user-submitted-posts/" title="User Submitted Posts WordPress Plugin">the <abbr title="User Submitted Posts">USP</abbr> page</a> at 
			<a href="http://perishablepress.com/" title="WordPress, Web Design, Code &amp; Tutorials">Perishable Press</a>.'); ?> 
		</p>
		<p>
			<?php _e('<strong>To show support</strong> for the plugin, consider buying a copy of our book <a href="http://digwp.com/book/" title="Learn How to WordPress">Digging into WordPress</a>. 
			Links and tweets are also appreciated! Thanks for your support :)'); ?>
		</p>
		<p><?php _e('<strong>Note:</strong> After setting your options, include the following template snippet anywhere in your theme to display the User-Submission Form:'); ?></p>
		<p><code><</code><code>?php if(function_exists('public_submission_form')) public_submission_form(true); ?</code><code>></code></p>
		<p><?php _e('You can also use the following <a href="http://codex.wordpress.org/Shortcode_API">shortcode</a> in any post or page:'); ?></p>
		<p><code>[user-submitted-posts]</code></p>
	</div>
	<p><a id="usp_admin_toggle" href="#">Collapse info &uarr;</a></p>

	<h2><?php _e('Plugin Settings'); ?></h2>

	<?php if($_GET['updated'] == '1') { ?>
	<div id="usp_success_message"><p style="font-weight:bold;font-size:110%;color:green;"><?php _e('Options Updated Successfully'); ?></p></div>
	<?php } ?>

	<form id="usp_admin_form" method="post" action="">
		<table id="usp_admin_table" class="form-table">
			<tbody>
				<tr>
					<td>
						<h3><?php _e('Customize Form Display'); ?></h3>
						<p><?php _e('Display the following form items:'); ?></p>
						<ul>
							<li>
								<select name="usp_name">
									<option <?php if($settings['usp_name'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show'); ?></option>
									<option <?php if($settings['usp_name'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide'); ?></option>
								</select> <?php _e('User Name'); ?>
							</li>
							<li>
								<select name="usp_url">
									<option <?php if($settings['usp_url'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show'); ?></option>
									<option <?php if($settings['usp_url'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide'); ?></option>
								</select> <?php _e('Post URL'); ?>
							</li>
							<li>
								<select name="usp_title">
									<option <?php if($settings['usp_title'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show'); ?></option>
									<option <?php if($settings['usp_title'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide'); ?></option>
								</select> <?php _e('Post Title'); ?>
							</li>
							<li>
								<select name="usp_tags">
									<option <?php if($settings['usp_tags'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show'); ?></option>
									<option <?php if($settings['usp_tags'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide'); ?></option>
								</select> <?php _e('Post Tags'); ?>
							</li>
							<li>
								<select name="usp_category">
									<option <?php if($settings['usp_category'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show'); ?></option>
									<option <?php if($settings['usp_category'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide'); ?></option>
								</select> <?php _e('Post Category'); ?>
							</li>
							<li>
								<select name="usp_content">
									<option <?php if($settings['usp_content'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show'); ?></option>
									<option <?php if($settings['usp_content'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide'); ?></option>
								</select> <?php _e('Post Content'); ?>
							</li>
							<li>
								<select name="usp_images">
									<option <?php if($settings['usp_images'] == 'show') echo 'selected="selected"'; ?> value="show"><?php _e('Show'); ?></option>
									<option <?php if($settings['usp_images'] == 'hide') echo 'selected="selected"'; ?> value="hide"><?php _e('Hide'); ?></option>
								</select> <?php _e('Post Images'); ?>
							</li>
						</ul>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label for="upload-message"><?php _e('Upload Message'); ?></label></h3>
						<p><?php _e('Message appearing next to upload field:'); ?></p>
						<textarea class="large-text" name="upload-message" id="upload-message"><?php echo attribute_escape($settings['upload-message']); ?></textarea>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label for="error-message"><?php _e('Error Message'); ?></label></h3>
						<p><?php _e('Error message to display if post-submission fails:'); ?></p>
						<textarea class="large-text" name="error-message" id="error-message"><?php echo attribute_escape($settings['error-message']); ?></textarea>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label for="author"><?php _e('Assigned Author'); ?></label></h3>
						<p><?php _e('Assign user-submitted posts to:'); ?></p>
						<select id="author" name="author">
						<?php global $wpdb; $allAuthors = $wpdb->get_results("SELECT ID, display_name FROM {$wpdb->users}");
							foreach($allAuthors as $author) { ?>
							<option <?php selected($settings['author'], $author->ID); ?> value="<?php echo $author->ID; ?>">
							<?php echo $author->display_name; ?>
							</option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label for="redirect-url"><?php _e('Redirect URL'); ?></label></h3>
						<p><?php _e('Redirect user to this page after post-submission<br />(leave blank to redirect back to current page):'); ?></p>
						<input type="text" class="regular-text" name="redirect-url" id="redirect-url" value="<?php echo attribute_escape($settings['redirect-url']); ?>" />
					</td>
				</tr>
				<tr>
					<td>
						<h3><label for="number-approved"><?php _e('Automatically Publish'); ?></label></h3>
						<p><?php _e('For submitted posts, you can always moderate (recommended), publish immediately, or publish after any number of approved posts:'); ?></p>
						<select name="number-approved">
							<option <?php selected(-1, $settings['number-approved']); ?> value="-1"><?php _e('Always moderate'); ?></option>
							<option <?php selected( 0, $settings['number-approved']); ?> value="0"><?php _e('Always publish immediately'); ?></option>
							<?php foreach(range(1, 20) as $value) { ?>
							<option <?php selected($value, $settings['number-approved']); ?> value="<?php echo $value; ?>"><?php echo $value; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<h3><?php _e('Categories'); ?></h3>
						<p><?php _e('Allow users to choose from the following categories:'); ?></p>
						<ul>
							<?php $categories = get_categories(array('hide_empty' => false)); ?>
							<?php foreach($categories as $category) { ?>
							<li>
								<label for="categories-<?php echo $category->term_id; ?>">
									<input <?php checked(true, in_array($category->term_id, $settings['categories'])); ?> type="checkbox" name="categories[]" id="categories-<?php echo $category->term_id; ?>" value="<?php echo $category->term_id; ?>" /> 
									<?php echo htmlentities($category->name); ?>
								</label>
							</li>
							<?php } ?>
						</ul>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label for="min-images"><?php _e('Minimum Number of Images'); ?></label></h3>
						<p><?php _e('The'); ?> <em><?php _e('minimum'); ?></em> <?php _e('number of uploaded images:'); ?> 
							<select name="min-images" id="min-images">
								<?php foreach(range(0, 20) as $number) { ?>
								<option <?php selected($number, $settings['min-images']); ?> value="<?php echo $number; ?>">
									<?php echo $number; ?>
								</option>
								<?php } ?>
							</select>
						</p>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label for="max-images"><?php _e('Maximum Number of Images'); ?></label></h3>
						<p><?php _e('The'); ?> <em><?php _e('maximum'); ?></em> <?php _e('number of uploaded images:'); ?> 
							<select name="max-images" id="max-images">
								<option value="-1"><?php _e('No Limit'); ?></option>
								<?php foreach(range(0, 20) as $number) { ?>
								<option <?php selected($number, $settings['max-images']); ?> value="<?php echo $number; ?>">
									<?php echo $number; ?>
								</option>
								<?php } ?>
							</select>
						</p>
					</td>
				</tr>
				<tr>
					<td>
						<h3><?php _e('Minimum Image Size'); ?></h3>
						<p><label for="min-image-width"><?php _e('Minimum Width'); ?></label> 
						<input type="text" class="" size="4" name="min-image-width" id="min-image-width" value="<?php echo attribute_escape($settings['min-image-width']); ?>" />
						<p><label for="min-image-height"><?php _e('Minimum Height'); ?></label> 
						<input type="text" class="" size="4" name="min-image-height" id="min-image-height" value="<?php echo attribute_escape($settings['min-image-height']); ?>" />
					</td>
				</tr>
				<tr>
					<td>
						<h3><?php _e('Maximum Image Size'); ?></h3>
						<p><label for="max-image-width"><?php _e('Maximum Width'); ?></label> 
						<input type="text" class="" size="4" name="max-image-width" id="max-image-width" value="<?php echo attribute_escape($settings['max-image-width']); ?>" />
						<p><label for="max-image-height"><?php _e('Maximum Height'); ?></label> 
						<input type="text" class="" size="4" name="max-image-height" id="max-image-height" value="<?php echo attribute_escape($settings['max-image-height']); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<?php wp_nonce_field('save-post-submission-settings'); ?>
			<input type="submit" name="save-post-submission-settings" id="save-post-submisstion-settings" value="<?php _e('Save Settings'); ?>" />
		</p>
	</form>
</div>