=== User Submitted Posts ===

Plugin Name:       User Submitted Posts
Plugin URI:        http://perishablepress.com/user-submitted-posts/
Tags:              posts, user-submit, community, public, submit
Author URI:        http://perishablepress.com/
Author:            Jeff Starr
Requires at least: 2.8
Tested up to:      3.0.5
Version:           1.00
License:           GPLv2 or later

User Submitted Posts enables your visitors to submit posts and images from anywhere on your site.

== Description ==  

Adds a simple form via template tag or shortcode that enables your visitors to submit posts. 
User-submitted posts optionally include tags, categories, post titles, and more. You can set 
submitted posts as draft, publish immediately, or after some number of approved posts. 
Also enables users to upload multiple images when submitting a post. 
Everything super-easy to customize via Admin Settings page.

**Features**

* Let visitors submit posts from anywhere in your site
* Display submission form anywhere on the page via shortcode or template tag
* Post submissions may include title, tags, category, author, url, post and image(s)
* Redirect user to anywhere or return to current page after successful post submission

**Image Uploads**

* Optionally allow/require visitors to upload any number of images
* Specify minimum and maximum width and height for uploaded images
* Specicy minimum and maximum number of allowed image uploads for each post
* Includes jQuery snippet for easy choosing of multiple images
 
**Customization**

* Control which fields are displayed in the submission form
* Choose which categories users are allowed to select
* Assign submitted posts to any registered user
* Customizable error and upload messages

**Post Management**

* Saves as custom-fields with each post: user name, IP, URL, and path info for each uploaded image
* Set submitted posts to any status: Draft, Publish, or publish after some number of approved posts
* One-click post-filtering of user-submitted posts on the Admin Posts page
* Includes template tags for easy display of post attachments and images

== Installation ==

1. Upload the user-submitted-posts directory to your plugins folder and activate via Admin
2. Go to the "User Submitted Posts" Settings Page and customize the options for your site
3. Display the submission form on your page(s) using template tag or shortcode:

 - To display the form on a post or page, use the shortcode: [user-submitted-posts]
 - To display the form anywhere in your theme, use the template tag: public_submission_form(true)

**Note:** By default, the form width is 300px. To change the width, do the following:

1. Open the CSS file: /user-submitted-posts/resources/user-submitted-posts.css
2. Edit the first declaration block to the desired width: div#usp { width: 300px; }
3. All other styles are relative to that width, so no other changes are required

== Template Tags ==

To display the images attached to user-submitted posts, use this template tag:

<?php post_attachments(); ?>

This template tag prints the URLs for all post attachments and accepts the following paramters:

<?php post_attachments($size, $beforeUrl, $afterUrl, $numberImages, $postId); ?>

$size         = image size as thumbnail, medium, large or full -> default = full
$beforeUrl    = text/markup displayed before the image URL     -> default = <img src="
$afterUrl     = text/markup displayed after the image URL      -> default = " />
$numberImages = the number of images to display for each post  -> default = false (display all)
$postId       = an optional post ID to use                     -> default = uses global post

Additionally, the following template tag returns an array of URLs for the specified post image:

<?php get_post_images(); ?>
	
This tag returns a boolean value indicating whether the specified post is a public submission: 

<?php is_public_submission(); ?>

== Styling the Submission Form ==

By default a CSS file is included with the submission form. It includes some basic styles for 
uniform structural and font display, but you will probably want to customize the look and feel 
of the form by adding a few styles of your own. The stylesheet includes all available selectors 
and is located in the following directory:

/wp-content/plugins/user-submitted-posts/resources/user-submitted-posts.css

== jQuery/JavaScript ==

Along with the stylesheet, an external JavaScript file is included on any page that displays 
the submission form. This file is located in the following directory:

/wp-content/plugins/user-submitted-posts/resources/user-submitted-posts.js

By default, this file contains only a jQuery snippet for multiple image uploads. If you are 
customizing the form with additional jQuery/JavaScript, this is a convenient place to do so.

== Upgrade Notice ==

None yet (initial release)

== Screenshots ==

See http://perishablepress.com/user-submitted-posts/

== Changelog ==

= 1.0 =

 * Initial release

== Frequently Asked Questions ==

See http://perishablepress.com/user-submitted-posts/

== Donations ==

To show support for the plugin, consider buying a copy of our book, 
Digging into WordPress, now available at <http://digwp.com/book/>
Links and tweets are also appreciated! Thanks for your support :)