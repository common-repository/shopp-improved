<?php
/**
 * Plugin Name: Shopp Improved
 * Plugin URI: http://xavisys.com/contact-us/?reason=Shopp+Improved
 * Description: This plugins adds some functionality to the Shopp E-Commerce system.  Requires PHP5.
 * Version: 1.0.3
 * Author: Aaron D. Campbell
 * Author URI: http://xavisys.com/
 * Text Domain: shopp-improved
 */

/*  Copyright 2010  Aaron D. Campbell  (email : wp_plugins@xavisys.com)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
/**
 * shoppImproved is the class that handles ALL of the plugin functionality.
 * It helps us avoid name collisions
 * http://codex.wordpress.org/Writing_a_Plugin#Avoiding_Function_Name_Collisions
 */
require_once('xavisys-plugin-framework.php');

class shoppImproved extends XavisysPlugin {
	/**
	 * @var wpTwitterWidget - Static property to hold our singleton instance
	 */
	static $instance = false;

	private $_input_types = array(
		'text',
		'password',
		'hidden',
		'checkbox',
		'radio',
		'textarea',
		'menu'
	);

	protected function _init() {
		$this->_hook = 'shoppImproved';
		$this->_slug = 'shopp-improved';
		$this->_file = plugin_basename( __FILE__ );
		$this->_pageTitle = __( 'Shopp Improved', $this->_slug );
		$this->_menuTitle = __( 'Shopp Improved', $this->_slug );
		$this->_accessLevel = 'manage_options';
		$this->_optionGroup = 'shopp-improved-options';
		$this->_optionNames = array('shopp-improved');
		$this->_optionCallbacks = array();
		$this->_paypalButtonId = 'A89J4TGVXMRNQ';

		$pluginDir = plugin_dir_url( __FILE__ );

		/**
		 * Register Scripts
		 */
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		wp_register_script( 'shopp-improved-product-edit', $pluginDir . "js/shopp-improved.product-edit{$suffix}.js", array('wp-ajax-response'), '0.0.2', true );

		/**
		 * Add filters and actions
		 */
		add_action('admin_menu', array($this, 'add_shop_meta_boxes'));
		add_action( 'wp_ajax_add-input', array( $this, 'add_input' ) );
		add_action( 'wp_ajax_delete-input', array( $this, 'delete_input' ) );
		add_action( 'shopp_admin_menu', array( $this, 'add_product_edit_scripts' ) );
	}

	public function add_product_edit_scripts() {
		wp_enqueue_script( 'shopp-improved-product-edit' );
	}

	public function delete_input( ) {
		check_ajax_referer( "delete-input_{$_POST['id']}" );

		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			die('-1');
		}

		$inputs = get_option('shopp-improved-inputs-' . $_POST['product_id']);

		if ( !array_key_exists($_POST['id'], $inputs) ) {
			die("That input doesn't seem to exist.");
		} else {
			unset($inputs[$_POST['id']]);
			update_option('shopp-improved-inputs-' . $_POST['product_id'] , $inputs);
			die('1');
		}
	}

	public function add_input( ) {
		check_ajax_referer( 'add-input' );

		$_POST['product_id'] = absint( $_POST['product_id'] );

		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			die('-1');
		}

		$product = new Product( $_POST['product_id'] );

		if ( empty( $product->id ) ) {
			die("That does not seem to be a valid product.");
		}

		if ( isset($_POST['shopp-improved-input-name']) && isset($_POST['shopp-improved-input-label']) ) {
			$_POST['shopp-improved-input-name'] = trim($_POST['shopp-improved-input-name']);
			$_POST['shopp-improved-input-label'] = trim($_POST['shopp-improved-input-label']);
			$_POST['shopp-improved-input-value'] = trim($_POST['shopp-improved-input-value']);
			$_POST['shopp-improved-input-type'] = trim($_POST['shopp-improved-input-type']);
			$inputs = get_option('shopp-improved-inputs-' . $_POST['product_id']);
			if ( empty($inputs) ) {
				$inputs = array();
			}

			if ( empty($_POST['shopp-improved-input-name']) ) {
				die('Please specify an input name.');
			}

			if ( empty($_POST['shopp-improved-input-label']) ) {
				$_POST['shopp-improved-input-label'] = $_POST['shopp-improved-input-name'];
			}

			$test_slug = $slug = sanitize_title_with_dashes($_POST['shopp-improved-input-name']);
			$n = 1;

			while ( array_key_exists($test_slug, $inputs) ) {
				$test_slug = $slug . '-' . ++$n;
			}

			$slug = $test_slug;

			$inputs[$slug] = array(
				'slug'	=> $slug,
				'name'	=> $_POST['shopp-improved-input-name'],
				'label'	=> $_POST['shopp-improved-input-label'],
				'value'	=> $_POST['shopp-improved-input-value'],
				'type'	=> $_POST['shopp-improved-input-type'],
			);

			update_option('shopp-improved-inputs-' . $_POST['product_id'] , $inputs);
			$return = new WP_Ajax_Response( array(
				'what'			=> 'input',
				'id'			=> $slug,
				'data'			=> $this->_list_input_row($inputs[$slug], count($inputs)),
				'supplemental'	=> array('product_id'=>$_POST['product_id'])
			) );
		} elseif ( !empty($_POST['input']) && is_array($_POST['input']) ) {
			$slug = array_pop(array_keys($_POST['input']));
			$_POST['input'][$slug]['name'] = trim($_POST['input'][$slug]['name']);
			$_POST['input'][$slug]['label'] = trim($_POST['input'][$slug]['label']);
			$_POST['input'][$slug]['value'] = trim($_POST['input'][$slug]['value']);
			$_POST['input'][$slug]['type'] = trim($_POST['input'][$slug]['type']);

			$inputs = get_option('shopp-improved-inputs-' . $_POST['product_id']);
			if ( empty($inputs) ) {
				$inputs = array();
			}

			if ( empty($_POST['input'][$slug]['name']) ) {
				die('Please specify an input name. To delete, use the delete button.');
			}

			if ( !array_key_exists($slug, $inputs) ) {
				die("That input doesn't seem to exist.");
			}

			if ( empty($_POST['input'][$slug]['label']) ) {
				$_POST['input'][$slug]['label'] = $_POST['input'][$slug]['name'];
			}

			$inputs[$slug] = array(
				'slug'	=> $slug,
				'name'	=> $_POST['input'][$slug]['name'],
				'label'	=> $_POST['input'][$slug]['label'],
				'value'	=> $_POST['input'][$slug]['value'],
				'type'	=> $_POST['input'][$slug]['type'],
			);

			update_option('shopp-improved-inputs-' . $_POST['product_id'] , $inputs);
			$return = new WP_Ajax_Response( array(
				'what'			=> 'input',
				'id'			=> $slug,
				'data'			=> $this->_list_input_row($inputs[$slug], count($inputs)),
				'supplemental'	=> array('product_id'=>$_POST['product_id'])
			) );
		} else {
			die('Action could not be handled.  Please try again.');
		}

		$return->send();
	}

	public function add_shop_meta_boxes() {
		add_meta_box( 'shopp-improved-inputbox', __('Input Items', $this->_slug), array( $this, 'input_meta_box'), 'admin_page_shopp-products-edit', 'normal', 'core' );
		add_meta_box( 'shopp-improved-inputbox', __('Input Items', $this->_slug), array( $this, 'input_meta_box'), 'shopp_page_shopp-products', 'normal', 'core' );
	}

	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	protected function _postSettingsInit() {
		//$this->mapApiUrl = "http://maps.google.com/maps?file=api&amp;v=2&amp;key={$this->_settings['gtm']['google_key']}";
		//wp_register_script('googleMaps', $this->mapApiUrl, null, 2, true);
	}

	public function input_meta_box( $product ) {
		/**
		 * We have these two extra wrapper divs because the CSS in the admin of
		 * WordPress (at least up to 3.0) styles the post meta boxes, which we
		 * want to emulate, based on two div IDs.  Since the post meta box
		 * doesn't exist on product pages we can simply re-use the IDs without
		 * any problem.
		 */
		?>
		<div id="postcustom">
			<div id="postcustomstuff">
				<div id="shopp-improved-input">
					<div id="input-ajax-response"></div>
					<table cellpadding="3">
						<?php $this->list_inputs($product->id); ?>
					</table>
					<p>
						<strong><?php _e('Add a new User Input:', $this->_slug); ?></strong>
					</p>
					<table cellspacing="3" cellpadding="3" id="newinput">
						<thead>
							<tr>
								<th><label for="shopp-improved-input-name"><?php _e('Name', $this->_slug); ?></label></th>
								<th><label for="shopp-improved-input-label"><?php _e('Label', $this->_slug); ?></label></th>
								<th><label for="shopp-improved-input-value"><?php _e('Value', $this->_slug); ?></label></th>
								<th><label for="shopp-improved-input-type"><?php _e('Type', $this->_slug); ?></label></th>
							</tr>
						</thead>
						<tbody>
							<tr valign="top">
								<td>
									<input type="text" name='shopp-improved-input-name' id='shopp-improved-input-name' tabindex="3" />
								</td>
								<td>
									<input type="text" name='shopp-improved-input-label' id='shopp-improved-input-label' tabindex="3" />
								</td>
								<td>
									<input type="text" name='shopp-improved-input-value' id='shopp-improved-input-value' tabindex="3" />
								</td>
								<td>
									<select name='shopp-improved-input-type' id='shopp-improved-input-type' tabindex='3'>
										<?php echo $this->_input_type_options('text'); ?>
									</select>
								</td>
							</tr>
							<tr class="submit">
								<td colspan="4">
									<?php wp_nonce_field( 'add-input', '_ajax_nonce', false ); ?>
									<input type="submit" value="Add User Input" tabindex="3" class="add:input-list:newinput" name="addinput" id="addinputsub"/>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

<?php
	}

	private function _input_type_options( $selected_input ) {
		$return = '';
		foreach( $this->_input_types as $type ) {
			$selected = ( $type == $selected_input )? " selected='selected'":'';
			$return .= "<option value='" . esc_attr($type) . "'{$selected}>" . esc_html( ucwords($type) ) . '</option>';
		}
		return $return;
	}

	/**
	 * List out all the existing tickers for a post
	 */
	private function list_inputs($product_id) {
		$inputs = get_option('shopp-improved-inputs-' . $product_id);

		// Exit if no meta
		if ( !$inputs ) {
			echo '<tbody id="input-list" class="list:input"><tr style="display: none;"><td>&nbsp;</td></tr></tbody>'; //TBODY needed for list-manipulation JS
			return;
		}
		$count = 0;
	?>
		<thead>
		<tr>
			<th><?php _e( 'Name', $this->_slug ); ?></th>
			<th><?php _e( 'Label', $this->_slug ); ?></th>
			<th><?php _e( 'Value', $this->_slug ); ?></th>
			<th><?php _e( 'Type', $this->_slug ); ?></th>
			<th><?php _e( 'Action', $this->_slug ); ?></th>
		</tr>
		</thead>
		<tbody id='input-list' class='list:input'>
<?php
		foreach ($inputs as $input) {
			echo $this->_list_input_row($input, $count++);
			?>
			<?php
		}
		echo "\n\t</tbody>";
	}

	private function _list_input_row($input, $count) {
		static $update_nonce = false;
		if ( !$update_nonce )
			$update_nonce = wp_create_nonce( 'add-input' );

		$r = '';
		$class = ( $count % 2 )? 'alternate':'';

		$delete_slug = preg_replace( '/[^\w]/', '', $input['slug'] );
		$delete_nonce = wp_create_nonce( 'delete-input_' . $input['slug'] );
		$name_label = __('Name', $this->_slug);
		$label_label = __('Label', $this->_slug);
		$value_label = __('Value', $this->_slug);
		$type_label = __('Type', $this->_slug);
		$update_label = esc_attr__('Update', $this->_slug);
		$delete_label = esc_attr__('Delete', $this->_slug);
		$input = array_map('esc_attr', $input);

		$input_type_options = $this->_input_type_options($input['type']);

		$r = <<<ROW
		<tr id="input-{$input['slug']}" class="{$class}">
			<td valign='top'>
				<label class='hidden' for='input_{$input['slug']}_name'>{$name_label}</label>
				<input name='input[{$input['slug']}][name]' id='input_{$input['slug']}_name' type='text' size='20' value='{$input['name']}' tabindex='3' />
			</td>
			<td valign='top'>
				<label class='hidden' for='input_{$input['slug']}_label'>{$label_label}</label>
				<input name='input[{$input['slug']}][label]' id='input_{$input['slug']}_label' type='text' size='20' value='{$input['label']}' tabindex='3' />
			</td>
			<td valign='top'>
				<label class='hidden' for='input_{$input['slug']}_value'>{$value_label}</label>
				<input name='input[{$input['slug']}][value]' id='input_{$input['slug']}_value' type='text' size='20' value='{$input['value']}' tabindex='3' />
			</td>
			<td valign='top'>
				<label class='hidden' for='input_{$input['slug']}_type'>{$type_label}</label>
				<select name='input[{$input['slug']}][type]' id='input_{$input['slug']}_type'>
				{$input_type_options}
				</select>
			</td>
			<td class="submit">
				<input name='updateinput' type='submit' value='{$update_label}' class='add:input-list:input-{$input['slug']}::_ajax_nonce={$update_nonce} updateinput' />
				<input name='deleteinput[{$input['slug']}]' type='submit' class='delete:input-list:{$input['slug']}::_ajax_nonce={$delete_nonce} deleteinput' value='{$delete_label}' />
			</td>
		</tr>
ROW;

		return $r;
	}

	public function get_inputs( $product_id = 0 ) {
		$product_id = (int) $product_id;
		// If a product ID isn't specified, get it from shopp
		if ( empty( $product_id ) ) {
			$product_id = shopp( 'product', 'id', array('return' => true) );
		}
		if ( empty( $product_id ) ) {
			return;
		}
		$inputs = get_option('shopp-improved-inputs-' . $product_id);
		if ( !empty($inputs) ) {
			echo '<ul class="shopp-improved-inputs">';
		}
		$count = 0;
		foreach ( $inputs as $input ) {
			$count++;
			$classes = array("input-{$count}");
			if ( 1 == $count ) {
				$classes[] = 'first';
			}
			if ( count($inputs) == $count ) {
				$classes[] = 'last';
			}
			$classes = implode(' ', $classes);
			echo "<li class='{$classes}'>";
			echo '<label>' . esc_html($input['label']) . '</label>';
			$args = array(
				'name'	=> $input['name']
			);
			if ( !empty( $input['type'] ) ) {
				$args['type'] = $input['type'];
				if ( 'menu' == $input['type'] && !empty( $input['value'] ) ) {
					$args['options'] = $input['value'];
					unset( $input['value'] );
				}
			}

			if ( !empty($input['type']) ) {
				$args['type'] = $input['type'];
			}

			if ( !empty($input['value']) ) {
				$args['value'] = $input['value'];
			}
			shopp('product','input', $args);
			echo '</li>';

		}
		if ( !empty($inputs) ) {
			echo '</ul>';
		}
	}

	public function get_icons( $args ) {
		$defaults = array(
			'before'		=> '<div class="icons">',
			'after'			=> '</div>',
			'before_icon'	=> '',
			'after_icon'	=> '',
		);
		$args = wp_parse_args($args, $defaults);

		global $Shopp, $icon_descriptions;
		if ( ! is_array( $icon_descriptions ) ) {
			$icon_descriptions = array();
		}

		$pages = $Shopp->Settings->get('pages');
		if (SHOPP_PERMALINKS) $tag_path = $Shopp->shopuri;
		else $page = add_query_arg('page_id',$pages['catalog']['id'],$Shopp->shopuri);

		$icons = '';
		while(shopp('product','tags')) {
			$tag_name = shopp( 'product', 'tag', array('return' => true) );
			$sanitized_tag = sanitize_title_with_dashes( $tag_name );
			$tag_filename = apply_filters('shopp-improved-tag-filename',  "{$sanitized_tag}.png", $tag_name);
			$file_locations = array(
				get_stylesheet_directory() => get_stylesheet_directory_uri(),
				path_join(get_stylesheet_directory(), 'images') => path_join(get_stylesheet_directory_uri(), 'images')
			);
			$file_locations = apply_filters( 'shopp-improved-icon-locations', $file_locations );

			if (SHOPP_PERMALINKS) $tag_url = $tag_path.'tag/'.urlencode($tag_name).'/';
			else $tag_url = add_query_arg('shopp_tag',urlencode($tag_name),$page);

			foreach ( $file_locations as $path => $uri ) {
				if ( file_exists( path_join($path, $tag_filename) ) ) {
					$icon = $args['before_icon'] . '<a href="%1$s" title="%2$s"><img src="%3$s" alt="%2$s" title="%2$s" /></a>' . $args['after_icon'];
					if ( !empty( $icon_descriptions[$sanitized_tag] ) ) {
						$icon = '<div class="icon">' . $icon . '<p>' . esc_html( $icon_descriptions[$sanitized_tag] ) . '</p></div>';
					}
					$icon = sprintf( $icon, esc_attr($tag_url), esc_attr($tag_name), esc_attr( path_join($uri, $tag_filename) ) );
					$icons .= $icon;
					break;
				}
			}
		}
		if ( !empty( $icons ) ) {
			$icons = $args['before'] . $icons . $args['after'];
		}
		echo apply_filters('shopp-improved-icons', $icons, $args);
	}
}

/**
 * For use with debugging
 */
if ( !function_exists('dump') ) {
	function dump($v, $title = '', $return = false) {
		if (!empty($title)) {
			echo '<h4>' . htmlentities($title) . '</h4>';
		}
		ob_start();
		var_dump($v);
		$v = ob_get_clean();
		$v = '<pre>' . htmlentities($v) . '</pre>';
		if ( $return ) {
			return $v;
		}
		echo $v;
	}
}


// Instantiate our class
$shoppImproved = shoppImproved::getInstance();
