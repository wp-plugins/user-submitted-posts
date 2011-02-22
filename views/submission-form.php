<?php // User Submitted Posts Plugin by Jeff Starr @ http://perishablepress.com/user-submitted-posts/

$settings = $this->getSettings(); 

?>

<!-- User Submitted Posts Plugin by Jeff Starr @ http://perishablepress.com/user-submitted-posts/ -->
<div id="usp">
	<form id="usp_form" method="post" enctype="multipart/form-data" action="">
	
		<?php if($_GET['success'] == '1') { ?>
		<div id="usp_success_message"><?php _e('Submission received!'); ?></div>
		<?php } ?>
	
		<ul id="usp_list">
			<?php if ($settings['usp_name'] == 'show') { ?>
			<li class="usp_name">
				<label for="user-submitted-name" class="usp_label"><?php _e('Your Name'); ?></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-name" id="user-submitted-name" value="" />
				</div>
			</li>
			<?php } if ($settings['usp_url'] == 'show') { ?>
			<li class="usp_url">
				<label for="user-submitted-url" class="usp_label"><?php _e('Your URL'); ?></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-url" id="user-submitted-url" value="" />
				</div>
			</li>
			<?php } if ($settings['usp_title'] == 'show') { ?>
			<li class="usp_title">
				<label for="user-submitted-title" class="usp_label"><?php _e('Post Title'); ?></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-title" id="user-submitted-title" value="" />
				</div>
			</li>
			<?php } if ($settings['usp_tags'] == 'show') { ?>
			<li class="usp_tags">
				<label for="user-submitted-tags" class="usp_label"><?php _e('Post Tags'); ?> <small><?php _e('(separate tags with commas)'); ?></small></label>
				<div>
					<input class="usp_input" type="text" name="user-submitted-tags" id="user-submitted-tags" value="" />
				</div>
			</li>
			<?php } if ($settings['usp_category'] == 'show') { ?>
			<li class="usp_category">
				<label for="user-submitted-category" class="usp_label"><?php _e('Post Category'); ?></label>
				<div> 
					<select class="usp_select" name="user-submitted-category" id="user-submitted-category">
						
						<?php foreach($settings['categories'] as $categoryId) { $category = get_category($categoryId); if(!$category) { continue; } ?>
						<option class="usp_option" value="<?php echo $categoryId; ?>"><?php $category = get_category($categoryId); echo htmlentities($category->name); ?></option>
						<?php } ?>
					</select>
				</div>
			</li>
			<?php } if ($settings['usp_content'] == 'show') { ?>
			<li class="usp_content">
				<label for="user-submitted-content" class="usp_label"><?php _e('Post Content'); ?></label>
				<div>
					<textarea class="usp_textarea" name="user-submitted-content" id="user-submitted-content" rows="5"></textarea>
				</div>
			</li>
			<?php } if ($settings['usp_images'] == 'show') { ?>
				<?php if($settings['max-images'] !== 0) { ?>
				<li class="usp_images">
					<label for="user-submitted-image" class="usp_label"><?php _e('Upload an Image'); ?></label>
					<div id="usp_upload-message"><?php echo $settings['upload-message']; ?></div>
					<div>
						<?php 
						if($settings['min-images'] < 1) {
							$numberImages = 1;
						} else {
							$numberImages = $settings['min-images'];
						}
						for($i = 0; $i < $numberImages; $i++) { ?>
						<input class="usp_input usp_clone" type="file" size="25" id="user-submitted-image" name="user-submitted-image[]" />
						<?php } ?>
						<a href="#" id="usp_add-another"><?php _e('Add another image'); ?></a>
					</div>
				</li>
				<?php } ?>
			<?php } ?>
			<li class="usp_submit">
				<?php if(!empty($redirect)) { ?>
					<input type="hidden" name="redirect-override" value="<?php echo $redirect; ?>" />
				<?php } ?>
				<input class="usp_input" type="submit" name="user-submitted-post" id="user-submitted-post" value="<?php _e('Submit Post'); ?>" />
			</li>
		</ul>
	</form>
</div>
<div style="clear:both;"></div>
<!-- User Submitted Posts Plugin by Jeff Starr @ http://perishablepress.com/user-submitted-posts/ -->