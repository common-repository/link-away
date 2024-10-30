<?php
/*
Plugin Name: Link Away
Plugin URI: http://imnotmarvin.com/link-away/
Description: Link Away makes it easy to replace a post's permalink with any URL you choose on a post by post basis. Link Away adds a field to the post admin page for entering the new destination address.
Version: 1.0
Author: Michael Davis
Author URI: http://imnotmarvin.com
License: GPLv2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

For the latest copy of the GNU General Public License, see <http://www.gnu.org/licenses/>.

== Changelog ==

= 1.0 = 
* Initial release
*/
If( !function_exists('testurl') ) {
	add_action( 'admin_head', 'testurl' );
	function testurl() {
	?>
		<script type="text/javascript">
			function openURL(urlToOpen){
			var win;
			win = window.open(urlToOpen, "VerifyURL", "width=850,height=400,toolbar=no,resizable=yes");
			return false;
			}
		</script>
	<?php
	}
}
$prefix = 'inm_la_';
$meta_box = array(
    'id' => 'inm-meta-box',
    'title' => 'Link Away',
    'page' => 'post',
    'context' => 'side',
    'priority' => 'high',
    'fields' => array(
        array(
            'name' => 'URL',
            'desc' => '<em>To link away from this post, paste new URL here. Otherwise leave blank.</em>',
            'id' => $prefix . 'title_url',
            'type' => 'text',
            'std' => ''
        ),
        array(
            'name' => 'Open in new window?',
            'desc' => '<em>Check to have this URL open in a new browser window.</em>',
            'id' => $prefix . 'new',
            'type' => 'checkbox',
            'std' => ''
        )
    )
);
// Add meta box
If( !function_exists('inm_la_add_box') ) {
	add_action('admin_menu', 'inm_la_add_box');
	function inm_la_add_box() {
		global $meta_box;
		
		$args = array(
			'public'   => true,
			'_builtin' => false
		);

		$output = 'names'; // names or objects, note names is the default
		$operator = 'and'; // 'and' or 'or'
		// Add metabox to posts
		add_meta_box($meta_box['id'], $meta_box['title'], 'inm_la_show_box', $meta_box['page'], $meta_box['context'], $meta_box['priority']);
		// Add settings metabox to public custom post types
		$post_types = get_post_types( $args, $output, $operator ); 
		foreach ( $post_types  as $post_type ) {
			$this_post_type = "'".$post_type."'";
			add_meta_box($meta_box['id'], $meta_box['title'], 'inm_la_show_box', $this_post_type, $meta_box['context'], $meta_box['priority']);
		}
	}
}
If( !function_exists('inm_la_show_box') ) {
	function inm_la_show_box() {
		global $meta_box, $post;
		// Use nonce for verification
		echo '<input type="hidden" name="inm_la_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
		echo '<table class="form-table">';
		foreach ($meta_box['fields'] as $field) {
			// get current post meta data
			$meta = get_post_meta($post->ID, $field['id'], true);
			echo '<tr>',
					'<td>';
			switch ($field['type']) {
				case 'text':
					echo '<strong><label for="', $field['id'], '">', $field['name'], '</label></strong><br /><input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" />', '<br />', $field['desc'];
					break;
				case 'textarea':
					echo '<strong><label for="', $field['id'], '">', $field['name'], '</label></strong><br /><textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="4" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>', '<br />', $field['desc'];
					break;
				case 'select':
					echo '<strong><label for="', $field['id'], '">', $field['name'], '</label></strong><br /><select name="', $field['id'], '" id="', $field['id'], '">';
					foreach ($field['options'] as $option) {
						echo '<option', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
					}
					echo '</select>';
					break;
				case 'radio':
				echo '<strong><label for="', $field['id'], '">', $field['name'], '</label></strong><br />';
					foreach ($field['options'] as $option) {
						echo '<input type="radio" name="', $field['id'], '" value="', $option['value'], '"', $meta == $option['value'] ? ' checked="checked"' : '', ' />', $option['name'];
					}
					break;
				case 'checkbox':
					if( $field['id'] == "inm_la_new" ) {
						echo '<strong><label for="', $field['id'], '">', $field['name'], '</label></strong>&nbsp;&nbsp;<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />', '<br />', $field['desc'], '<br /><br /><input id="inm_la_test_button" style="height:22px;width:36px;font-size:10px;cursor:pointer;" type="button" value="Test..." id="inm_la_url_check" onclick="return openURL(inm_la_title_url.value)" >';
					}else{
						echo '<strong><label for="', $field['id'], '">', $field['name'], '</label></strong>&nbsp;&nbsp;<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />', '<br />', $field['desc'];
						break;
					}
			}
			echo '</tr>';
		}
		echo '</table>';
	}
}
// Save data from meta box
If( !function_exists('inm_la_save_data') ) {
	add_action('save_post', 'inm_la_save_data');
	function inm_la_save_data($post_id) {
		global $meta_box;
		// verify nonce
		if (!wp_verify_nonce($_POST['inm_la_meta_box_nonce'], basename(__FILE__))) {
			return $post_id;
		}
		// check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}
		// check permissions
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
		} elseif (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
		foreach ($meta_box['fields'] as $field) {
			$old = get_post_meta($post_id, $field['id'], true);
			$new = $_POST[$field['id']];
			if ($new && $new != $old) {
				update_post_meta($post_id, $field['id'], $new);
			} elseif ('' == $new && $old) {
				delete_post_meta($post_id, $field['id'], $old);
			}
		}
	}
}
// Swap out the url with the new one, if there is a new one
If( !function_exists('append_query_string') ) {
	function append_query_string($url) {
		global $post;
		if ( get_post_meta($post->ID, 'inm_la_title_url', true) ) {
			if ( get_post_meta($post->ID, 'inm_la_new', true) ) {
				$link = get_post_meta($post->ID, 'inm_la_title_url', true) . '" target="_blank';
			}else{
				$link = get_post_meta($post->ID, 'inm_la_title_url', true);
			}
		}else{
			$link = $url;
		}
		return $link;
	}
	if( !is_page() && !is_singular() ) {
		add_filter('post_link', 'append_query_string', 999999);
	}
}
?>