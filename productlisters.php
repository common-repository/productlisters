<?php
/*
  Plugin Name: Productlisters - Upload your business into over 8 social media websites
  Description: Generate XML file of products for Productlisters.com
  Version: 0.2
	Author: Productlisters
	Author URI: http://www.productlisters.com
	License: GPLv2
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
class Productlisters {

    protected static $instance;

    private function __construct() {
        $this->current_plugin = $this->get_CurrentCommercePlugin();
        add_action('init', array($this, 'init'));
        if (is_admin() && !empty($_POST) && !defined('DOING_AJAX')) {
            add_action('init', array($this, 'productlisters_save'));
        }
    }

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $options = get_option('productlisters');
        add_action('admin_menu', array($this, 'init_admin_menu'));
        if (!wp_next_scheduled('pl_weekly_event')) {
            if ($options['cron'] != '0') {
                wp_schedule_event(time(), $options['cron'], 'pl_weekly_event');
            }
        }

        // generate on button click
        if (isset($_GET['pl_action']) && $_GET['pl_action'] == 'regenerate') {
            if (!empty($this->current_plugin)) {
                include dirname(__FILE__) . '/includes/' . $this->pluginNameToFilename($this->current_plugin) . '.php';
            }
        }
    }

    public function init_admin_menu() {
        add_menu_page('Productlisters XML creator plugin', 'Productlisters', 'manage_options', 'productlisters', array($this, 'productlisters_callback'), WP_PLUGIN_URL . '/productlisters/img/productlisters16.png');
    }

    public function productlisters_callback() {
        ?>
        <div class="wrap">
            <div style="background-image:url('<?php echo WP_PLUGIN_URL . '/productlisters/img/productlisters32.png' ?>');float:left;height:32px;margin:7px 4px 0 0;width:32px;"></div>
            <h2>Productlisters XML creator plugin</h2>
            <div id="message" class="updated">
                <p><?php $this->the_CurrentCommercePlugin(); ?></p>
            </div>
            <form method="post" action="">
                <table class="form-table" style="width:800px;">
                    <?php
										if ($this->current_plugin!='') {
											$options = get_option('productlisters');
											$options = (!empty($options)) ? $options : array();
                    ?>
                        <tr valign="top">
                            <th><?php _e('XML url', 'pl'); ?></th>
                            <td>
															<?php _e('Now you can hit the \'Regenate\' button below so your first XML export is created. ', 'pl'); ?><br/>
															<?php _e('Go to <a href="http://www.productlisters.com" target="_blank">Productlisters.com</a> and signup for your free trial! ', 'pl'); ?><br/>
															<?php _e('Copy the url below into your Productlisters account into the field \'Your product feed url\'. You see this field when you click the button \'XML productfeed\' on step 2 of the registration process ', 'pl'); ?>
															<a href="<?php echo site_url() . '/wp-content/productlisters.xml'; ?>"><?php echo site_url() . '/wp-content/productlisters.xml'; ?></a>
														</td>
                        </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cron timing', 'pl'); ?></th>
                        <td>
                            <label style="margin-right: 20px;"><input name="productlisters[cron]" type="radio" value="0"<?php echo ($options['cron'] == 0) ? ' checked' : ''; ?> /> <?php _e('Off', 'pl'); ?></label>
                            <label style="margin-right: 20px;"><input name="productlisters[cron]" type="radio" value="daily"<?php echo ($options['cron'] == 'daily') ? ' checked' : ''; ?> /> <?php _e('Daily', 'pl'); ?></label>
                            <label style="margin-right: 20px;"><input name="productlisters[cron]" type="radio" value="weekly"<?php echo ($options['cron'] == 'weekly') ? ' checked' : ''; ?> /> <?php _e('Weekly', 'pl'); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Resource ID', 'pl'); ?></th>
                        <td>
													<?php _e('You may optionally provide your Productlisters id here to see the statistics from your PL account.', 'pl'); ?><br/>
													<input type="text" size="50" name="productlisters[user_id]" value="<?php echo (isset($options['user_id']) && !empty($options['user_id'])) ? $options['user_id'] : ''; ?>" /><br/>
													<?php _e('You can find your resource ID beneath <a href="http://www.productlisters.com/my_stats.php">\'Statistics\'</a> in your Productlisters account dashboard.', 'pl'); ?>
												</td>
                    </tr>
                    <tr>
                        <td><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" style=""></td>
                        <td><a href="<?php echo admin_url('admin.php?page=productlisters&pl_action=regenerate'); ?>" class="button-primary" style="display:inline-block;"><?php _e('Regenerate', 'pl'); ?></a></td>
                    </tr>
                    <?php
                    if (isset($options['user_id']) && !empty($options['user_id'])) {
                        $xml_error = true;
                        $url = 'http://www.productlisters.com/xml_stats.php?ent_resid=' . $options['user_id'];
                        $response = wp_remote_post($url, array(
                                        'method'      => 'GET',
                                        'timeout'     => 45,
                                        'redirection' => 5,
                                        'httpversion' => '1.0',
                                        'blocking'    => true
                                    ));

                        if (!empty($response) && !is_wp_error($response) && !empty($response['body'])) {
                            $xml = new SimpleXMLElement($response['body']);
                            if (!count($xml->error)) {
                                $xml_error = false;
                                if (count($xml->data)) {?>
                                    <tr><td><h3><?php _e('Statistics:', 'pl'); ?></h3></td><td></td></tr>
                                    <?php
                                    $total = 0;
                                    foreach ($xml->data as $row) {
                                        if (!empty($row->name)) {
                                            $margin = ($row->label == 'total') ? 2 : 1;
                                            ?>
                                            <tr valign="top" style="border-bottom:<?php echo $margin; ?>px solid #ccc;">
                                                <td style="vertical-align:middle;padding: 2px 0;"><?php echo (!empty($row->label) && $row->label != 'total') ? '<img src="' . WP_PLUGIN_URL . '/productlisters/img/' . $row->label . '.png" alt="' . $row->name . '" title="' . $row->name . '">' : '<b>' . $row->name . '</b>'; ?></td>
                                                <td style="vertical-align:middle;padding: 2px 0;"><?php echo (!empty($row->count)) ? $row->count : 0; ?> <?php _e('clicks', 'pl'); ?></td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                }
                            } else {
                                echo '<div id="message" class="error"><p>'.$xml->error.'</p></div>';
                            }
                        }
                    }
									}
                    ?>
                </table>
                <input type='hidden' name='productlisters_settings_form' value='sended' />
            </form>
        </div>
        <?php
    }

    public function productlisters_save() {
        if (isset($_POST['productlisters_settings_form']) && $_POST['productlisters_settings_form'] == 'sended') {
            foreach ($_POST['productlisters'] as $key => $value) {
                $productlisters[$key] = $this->pl_escape($_POST['productlisters'][$key]);
            }
            wp_clear_scheduled_hook('pl_weekly_event');
            if ($productlisters['cron'] != '0') {
                wp_schedule_event(time(), $productlisters['cron'], 'pl_weekly_event');
            }
            update_option('productlisters', $productlisters);
        }
    }

    public function pl_escape($str) {
        return strip_tags(trim($str));
    }

    public function get_CurrentCommercePlugin() {
				if (class_exists('WP_eCommerce')) {
						$current_plugin='WP eCommerce';
        } elseif (class_exists('jigoshop')) {
						$current_plugin='Jigoshop';
        } elseif (class_exists('Easy_Digital_Downloads')) {
						$current_plugin='Easy Digital Download';
        } elseif (defined('ESHOP_VERSION')) {
						$current_plugin='eShop';
				} elseif (file_exists('../wp-content/plugins/woocommerce/woocommerce.php')) {
						$current_plugin='Woocommerce';
        } else {
            return false;
        }
			return $current_plugin;
    }

    public function the_CurrentCommercePlugin() {
        echo (!empty($this->current_plugin)) ? sprintf(__('The e-commerce platform you are using: <b>%s</b>', 'pl'), $this->current_plugin) : __('For this plugin to work you need to have one of the following E-commerce plugins activated:<br/><ul><li>Woocommerce</li><li>eCommerce</li><li>Jigoshop</li><li>Easy Digital Downloads</li><li>eShop</li></ul>', 'pl');
    }

    public function cron_callback() {
        if (!empty($this->current_plugin)) {
            include dirname(__FILE__) . '/includes/' . $this->pluginNameToFilename($this->current_plugin) . '.php';
        }
    }

    public function pluginNameToFilename($name) {
        return strtolower(preg_replace("/[^\w]+/", '_', $name));
    }
}

add_action('plugins_loaded', 'productlisters_instance');

function productlisters_instance() {
    $productlisters = Productlisters::getInstance();
}

add_action('pl_weekly_event', array(Productlisters::getInstance(), 'cron_callback'));