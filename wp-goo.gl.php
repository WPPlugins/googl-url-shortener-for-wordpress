<?php
/*
Plugin Name: goo.gl URL Shortener for WordPress
Plugin URI: http://www.skiyo.cn/2010/04/19/goo-gl-url-shortener-for-wordpress
Description: wp-goo.gl allows you to publish links in your posts or comments using goo.gl short URL service. With <code>[goo.gl="name"]link[/goo.gl]</code> or <code>[goo.gl]link[/goo.gl]</code> in your posts. In comments, you donot have to write such special codes. wp-goo.gl will change the links automatically. The cURL functions must be available on your server. <code>WARNING: This plug-in will permanently change your input link!</code>
Author: Skiyo
Version: 1.0.1
Author URI: http://www.skiyo.cn/
*/
require_once('GoogleShorter.class.php');
try {
	$googl = new GoogleShorter();
} catch(Exception $e) {
	//we don't care the exception.
}


function wp_gg_textdomain () {
    load_plugin_textdomain('wp-goo.gl', false, 'wp-goo.gl');
}

function gg_options() {
	if(!current_user_can('manage_options')) {
		die(__('Access Denied','wp-goo.gl'));
	}
	if(!function_exists('curl_init')){
		?>
		<div class="wrap">
			<h2><?php _e("WARNING",'wp-goo.gl'); ?></h2>
			<br /><div style="color:#770000;"><?php _e("wp-goo.gl can not run on the server because The cURL functions are NOT available!",'wp-goo.gl'); ?></div><br />
		</div>
	<?php
	 die();
	}
	if(!get_option('gg_options_flag')) {
		update_option('gg_open_post', 1);
		update_option('gg_open_comment', 1);
		update_option('gg_options_flag', 1);
	}

	if ($_POST['post'] == '1') {
    	update_option('gg_open_post', $_POST['gg_open_post']);
		update_option('gg_open_comment', $_POST['gg_open_comment']);
		?>
		<div class="updated"><br /><strong><?php _e("Saved Settings Successfully!",'wp-goo.gl'); ?></strong><br />&nbsp;</div>
	<?php
	}

  ?>
  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<input type="hidden" name="post" value="1" />
    <div class="wrap">
    <fieldset>
	<h3><legend><?php _e('wp-goo.gl options','wp-goo.gl'); ?></legend> </h3>
		<table width="96%" align="center" id="options">
			<tr>
				<td>
					<input type="checkbox" name="gg_open_post" <?php if(get_option('gg_open_post')) {?>checked="checked"<?php } ?> /><?php _e('open in posts','wp-goo.gl'); ?><br />
				<span class="help"><?php _e("you can use [goo.gl][/goo.gl] in your posts.",'wp-goo.gl'); ?>  </span>
				</td>
			</tr>
            <tr>
                <td>
				<input type="checkbox" name="gg_open_comment" <?php if(get_option('gg_open_comment')) {?>checked="checked"<?php } ?> /><?php _e("open in comments",'wp-goo.gl'); ?><br />
				<span class="help"><?php _e('If you turn this option on, all links in comments will be automatically converted to goo.gl short URL.','wp-goo.gl'); ?> </span>
				</td>
            </tr>
		</table>
	</fieldset>
	<fieldset class="submit">
		<div align="center">
    	<input type="submit" value="<?php _e("Update Options",'wp-goo.gl'); ?>" />
		</div>
	</fieldset>
	</div>
  </form>
    <?php
}

function gg_menu ()
{
    add_options_page(__('wp-goo.gl Options', 'wp-goo.gl'), __('wp-goo.gl', 'wp-goo.gl'), 8, basename(__FILE__), 'gg_options');
}

function wp_gg_posts_callback($matches) {
	$url = wp_gg_get_url($matches[1]);
	return '<a href="'.$url.'">'.$url.'</a>';
}

function wp_gg_posts($postid) {
	global $wpdb;
	$result = $wpdb->get_results("SELECT * FROM $wpdb->posts where ID = $postid");
	$post = $result[0];
	$post->post_content = preg_replace("/\s*\[goo\.gl(?:=[\"'](.+)[\"']|\s)\](.+)\[\/goo.gl\]\s*/siUe", "'<a href=\"'.wp_gg_get_url('\\1').'\">\\2</a>'", $post->post_content);
	$post->post_content = preg_replace_callback("/\s*\[goo\.gl\](.+)\[\/goo.gl\]\s*/siU", "wp_gg_posts_callback", $post->post_content);
	$post->post_content = addslashes($post->post_content);
	$wpdb->query("UPDATE $wpdb->posts SET post_content = '$post->post_content' WHERE ID = $postid");
}


function wp_gg_comments($commentid) {
	global $wpdb;
	$result = $wpdb->get_results("SELECT * FROM $wpdb->comments where comment_ID = $commentid");
	$comment = $result[0];
	$comment->comment_content = preg_replace('/((((https?|ftp):\/\/))([\w\-]+\.)*[:\.@\-\w]+\.([\.a-zA-Z0-9]+)((\?|\/|:)+[\w\.\/=\?%\-&~`@\':+!#]*)*)/ie', "wp_gg_get_url('\\0')", $comment->comment_content);
	$comment->comment_content = preg_replace('/((www|ftp)\.[\w\\x80-\\xff\#$%&~\/.\-;:=,?@\[\]+]+)/ise', "wp_gg_get_url('http://\\0')", $comment->comment_content);
	$comment->comment_content = addslashes($comment->comment_content);
	$wpdb->query("UPDATE $wpdb->comments SET comment_content = '$comment->comment_content' WHERE comment_ID = $commentid");
}

function wp_gg_get_url($a) {
    global $googl;
    $url = $googl->getURL($a);
	return empty($url) ? $a : $url;
}


add_action('init', 'wp_gg_textdomain');

if(function_exists('curl_init')){

	get_option('gg_open_post') && add_action('publish_post', 'wp_gg_posts');

	get_option('gg_open_comment') && add_action('comment_post', 'wp_gg_comments');
}

add_action('admin_menu', 'gg_menu');
?>