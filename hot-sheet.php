<?php
/*
Plugin Name: Hot Sheet by Designgeneeers!
Plugin URI: http://www.designgeneers.com/plugins/hot-sheet
Description: Hot Sheet provides a WordPress widget that can display a list of posts until their expiration date.  Also useful for a list of upcoming events.  To add a post to the Hot Sheet, simply set a date in the Hot Sheet options for the post.  To leave a post off of the Hot Sheet, leave the date empty.
Version: 1.0.4
Author: designgeneers
Author URI: http://www.designgeneers.com
License: GPL2
*/

/*  Copyright 2010 designgeneers (email : info@designgeneers.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
add_action('add_meta_boxes', 'dgx_hotsheet_add_meta_box');

function dgx_hotsheet_add_meta_box() {
    add_meta_box( 'dgx_hotsheet_sectionid', 'Hot Sheet', 'dgx_hotsheet_meta_box', 'post', 'side', 'high');
}

function dgx_hotsheet_meta_box($post) {
?>
	<input type="hidden" name="dgx_hotsheet_noncename" id="dgx_hotsheet_noncename" value="<?php echo wp_create_nonce( 'dgx_hotsheet'.$post->ID );?>" />
<?php

	$timestamp = get_post_meta($post->ID, '_dgx_hotsheet_date', true);
	if (!empty($timestamp))
	{
		$hotSheetDate = strftime("%m/%d/%Y", $timestamp);
	}
	echo "Feature this post until<br><br>";
	echo '<input type="text" id="_dgx_hotsheet_date" name="_dgx_hotsheet_date" value="';
	echo $hotSheetDate;
	echo '" size="10" maxlength="10" />';
	echo "<br><br>";
	echo "Enter date in the form m/d/y or d-m-y.  Leave empty to leave this post off the Hot Sheet";
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
add_action('save_post', 'dgx_hotsheet_save_postdata');
function dgx_hotsheet_save_postdata( $post_id ) {

	if ( !wp_verify_nonce( $_POST['dgx_hotsheet_noncename'], 'dgx_hotsheet'.$post_id )) {
		return $post_id;
	}

	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
	return $post_id;

	// Check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}

	// OK, we're authenticated: we need to find and save the data
	$hotSheetDate = $_POST['_dgx_hotsheet_date'];

	if (empty($hotSheetDate))
	{
		delete_post_meta($post_id, '_dgx_hotsheet_date');
	}
	else
	{
		// Convert user entered date into unix time
		// Dates in the m/d/y or d-m-y formats are disambiguated by strtotime by looking at the separator
		// between the various components: if the separator is a slash (/), then the American m/d/y is assumed;
		// whereas if the separator is a dash (-) or a dot (.), then the European d-m-y format is assumed. 
		if (($timestamp = strtotime($hotSheetDate)) === false)
		{
			// GNDN
		}
		else
		{
			update_post_meta($post_id, '_dgx_hotsheet_date', $timestamp);
		}
	}

	return $post_id;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function dgx_hotsheet_widget($args)
{
	extract($args);

	$title = get_option("dgx_hotsheet_title");
	if (empty($title))
	{
		$title = "Hot Sheet";
	}

	$foundOne = false;
	$widgetContent = "";

	$widgetContent .= $before_widget;
	$widgetContent .= $before_title . $title . $after_title;
	$widgetContent .= "<ul>\n";

	$args = array(
		'numberposts'   => -1,
		'post_type'     => 'post',
		'orderby'	=> 'meta_value',
		'order'		=> 'ASC',
		'meta_key'      => '_dgx_hotsheet_date'
	); 

	$myPosts = get_posts($args); 

	foreach ($myPosts as $myPost)
	{ 
		$postID = $myPost->ID;
		$postTitle = get_the_title($postID);
		$postLink = get_permalink($postID);
		$postTimestamp =  get_post_meta($postID, '_dgx_hotsheet_date', true);
		$timestampNow = time();
		$diff = $postTimestamp - $timestampNow;
		$diffHours = $diff / 3600;
		if ($postTimestamp >= $timestampNow)
		{
			$foundOne = true;
			$widgetContent .= "   <li><a href=\"$postLink\" title=\"$postTitle\">$postTitle</a></li>\n";
		}
	}
	$widgetContent .= "</ul>\n";
	$widgetContent .= $after_widget;

	if ($foundOne)
	{
		echo $widgetContent;
	}
}

function dgx_hotsheet_widget_control()
{
	$title = get_option("dgx_hotsheet_title");
	if (empty($title))
	{
		$title = "Hot Sheet";
	}

	if ($_POST['dgx_hotsheet_submit'])
	{
		$newtitle = htmlspecialchars($_POST['dgx_hotsheet_title']);
		update_option("dgx_hotsheet_title", $newtitle);
		$title = $newtitle;
	}
?>
	<p>
	<label for="dgx_hotsheet_title">Widget Title: </label>
	<input type="text" id="dgx_hotsheet_title" name="dgx_hotsheet_title" value="<?php echo $title;?>" />
	<input type="hidden" id="dgx_hotsheet_submit" name="dgx_hotsheet_submit" value="1" />
	</p>
<?php
}

add_action("plugins_loaded", "dgx_hotsheet_init");
function dgx_hotsheet_init() {
	register_sidebar_widget('Hot Sheet by Designgeneeers!', 'dgx_hotsheet_widget');
	register_widget_control('Hot Sheet by Designgeneeers!', 'dgx_hotsheet_widget_control', 300, 200);
}

?>
