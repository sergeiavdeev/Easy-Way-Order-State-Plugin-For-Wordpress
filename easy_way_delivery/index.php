<?php
/*
Plugin Name: Easy Way Delivery
Plugin URI: http://easyway.ru/
Description: Расчет доставки и оформление заказов в Easy Way.
Version: 1.0
Author: Avdeev Sergey
Author URI: http://avdey78.ru
License: GPLv2 or later
Text Domain: avdey
*/

class WP_Widget_Ewd_State extends WP_Widget
{
    public function __construct() {
        $widget_ops = array(
            'classname' => 'widget_ewd_state',
            'description' => 'Статус заказа Easy Way',
            'customize_selective_refresh' => true,
        );
        parent::__construct('ewd_state', 'Статус заказа', $widget_ops);
    }

    public function widget( $args, $instance ) {
        /** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
        $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Use current theme search form if it exists
        //get_search_form();
        echo "
        <form method='POST' action='".esc_url( home_url( '/' ) )."?page_id=".get_option('ewd_result_page_id')."' class='search-form'>
            <input type='text' name='ewd_order_number' placeholder='Номер заказа...' class='search-field' />
            <input type='submit' name='ewd_order_staete_btn' value='Прверить' class='search-submit' />
        </form>
        ";
        
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '') );
        $title = $instance['title'];
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }
}

function ewd_install()
{
	add_option('ewd_api_url', 'http://');
	add_option('ewd_api_username', 'username');
	add_option('ewd_api_password', 'password');
	add_option('ewd_result_page_id', '0');
}

function ewd_uninstall()
{
	delete_option('ewd_api_url');
	delete_option('ewd_api_username');
	delete_option('ewd_api_password');
	delete_option('ewd_result_page_id');
}

function ewd_admin_menu()
{
	add_options_page('Настройка доставки Easy Way', 'Доставка Easy Way', 8, 'easyway_delivery', 'ewd_options_page');
}

function ewd_options_page()
{		
	if (isset($_POST['ewd_base_setup_btn'])) {
		
		if (function_exists('current_user_can') && !current_user_can('manage_options')) {
			die(_e('Hack !?'));
		}
	
		if (function_exists('check_admin_referer')) {
			
			check_admin_referer('ewd_base_setup_form');
		}
		
		$ewd_api_url = $_POST['ewd_api_url'];
		$ewd_api_username = $_POST['ewd_api_username'];
		$ewd_api_password = $_POST['ewd_api_password'];
		$ewd_result_page_id = $_POST['ewd_result_page_id'];
		
		update_option('ewd_api_url', $ewd_api_url);
		update_option('ewd_api_username', $ewd_api_username);
		update_option('ewd_api_password', $ewd_api_password);
		update_option('ewd_result_page_id', $ewd_result_page_id);
	} 
	
	echo "<h2>Настройка службы доставки Easy Way</h2>";
	echo "<p>Автор плагина: Авдеев С.А.</p>";
	echo "<form name='ewd_base_setup' method='POST' action='".$_SERVER['PHP_SELF']."?page=easyway_delivery&amp;updated=true'>";
	
	if (function_exists('wp_nonce_field')) {
		
		wp_nonce_field('ewd_base_setup_form');
	}
	
	echo 
	"
	<table>
		<tr>
			<td>API URL:</td>
			<td><input type='text' name='ewd_api_url' value='".get_option('ewd_api_url')."'/></td>			
		</tr>		
		<tr>
			<td>Имя пользователя:</td>
			<td><input type='text' name='ewd_api_username' value='".get_option('ewd_api_username')."'/></td>			
		</tr>		
		<tr>
			<td>Пароль:</td>
			<td><input type='password' name='ewd_api_password' value='".get_option('ewd_api_password')."'/></td>			
		</tr>
		<tr>
			<td>ID страницы статуса заказа:</td>
			<td><input type='text' name='ewd_result_page_id' value='".get_option('ewd_result_page_id')."'/></td>			
		</tr>		
	</table>
	<input type='submit' name='ewd_base_setup_btn' value='Сохранить'/>
	";
	
	echo "</form>";
}

function ewd_register_state_widget() {
    register_widget('WP_Widget_Ewd_State');
}

function ewd_generate_order_state($content)
{
    if (isset($_POST['ewd_order_staete_btn'])) {

        include_once "EasyWay\API\EWConnector.php";

        $ew = new \EasyWay\API\EWConnector(get_option('ewd_api_url'), get_option('ewd_api_username'), get_option('ewd_api_password'));
        $result = $ew->getStatus([$_POST['ewd_order_number']]);

        $content = "
        <p>Заказ №".$_POST['ewd_order_number']."</p>
        <p>ID: ".$result[0]['id']."</p>
        <p>Статус: ".$result[0]['status']."</p>
        <p>Плановая дата доставки: ".$result[0]['arrivalPlanDateTime']."</p>
        ";
    }
    return $content;
}

register_activation_hook(__FILE__, 'ewd_install');
register_deactivation_hook(__FILE__, 'ewd_uninstall');

add_action('admin_menu', 'ewd_admin_menu');
add_action( 'widgets_init', 'ewd_register_state_widget' );
add_filter( 'the_content', 'ewd_generate_order_state');
?>
