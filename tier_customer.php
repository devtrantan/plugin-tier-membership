<?php


add_action('admin_menu', 'add_custom_woocommerce_tab');

function add_custom_woocommerce_tab() {
    add_submenu_page(
        'woocommerce',          
        'Tier Customer',      
        'Tier Customer',        
        'manage_woocommerce',   
        'tier-customer',        
        'tier_customer_callback'   
    );
}

function tier_customer_callback() {
   // Query all customers
   $args = array(
      'role'    => 'customer',
      'orderby' => 'user_nicename',
      'order'   => 'ASC',
  );

  $users = get_users($args);
  ?>
  <table class="table-total-spend">
  <tr>
    <th>Name</th>
    <th>Total Spend</th>
    <th>Tier</th>
    <th>Rate Conversion</th>  
</tr>
  <?php
  foreach ($users as $user) {
      $user_id = $user->ID;
  
      $total_spent = wc_get_customer_total_spent($user_id);
      $user_tier = get_user_meta($user_id, 'user_tier', true);
      $number_tier = 0;

      if(empty($user_tier)){
        $user_tier = 'Silver';
      }

      switch ($user_tier){
        case 'Silver':
            $number_tier = 1;
            break;
        case 'Gold':
            $number_tier = 2.5;
            break;
        case 'Platinum':
            $number_tier = 5;
            break;
        default:
            return;
    }

   ?>
   <tr>
      <td><?php echo esc_html($user->display_name) ?> </td>
      <td><?php echo wc_price($total_spent) ?> </td>
      <td><?php echo $user_tier ?> </td>
      <td><?php echo 'Spend 1$ = Earn ' . $number_tier . ' Point';?> </td>
  </tr>
   <?php
   }
   ?>
   </table>
   <?php
}


function update_user_tier($user_id) {

    $total_spent = wc_get_customer_total_spent($user_id);

    if ($total_spent >= 0 && $total_spent < 10) {
        $tier = 'Silver';
    } elseif ($total_spent >= 10 && $total_spent < 20) {
        $tier = 'Gold';
    } elseif ($total_spent >= 20) {
        $tier = 'Platinum';
    } else {
        $tier = 'none'; 
    }


    update_user_meta($user_id, 'user_tier', $tier);
}

add_action('woocommerce_order_status_completed', 'update_user_tier_on_order_complete');
add_action('woocommerce_order_status_cancelled', 'update_user_tier_on_order_complete');
add_action('woocommerce_order_status_refunded', 'update_user_tier_on_order_complete');
add_action('woocommerce_order_status_processing', 'update_user_tier_on_order_complete');

function update_user_tier_on_order_complete($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();

    if ($user_id) {
        update_user_tier($user_id);
    }
}


add_action('woocommerce_order_status_processing', 'update_point_follow_tier_user');
function update_point_follow_tier_user($order_id){
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();

    $old_point = get_user_points_by_id($user_id);
    $point_change = $order->get_total();
    $tier_user = get_user_meta($user_id, 'user_tier', true);

    switch ($tier_user){
        case NULL:
            $tier_number = 1;
            break;
        case 'Silver':
            $tier_number = 1;
            break;
        case 'Gold':
            $tier_number = 2.5;
            break;
        case 'Platinum':
            $tier_number = 5;
            break;
        default:
            return;
    }

    $point_user = $old_point + $point_change * $tier_number;
    WC_Points_Rewards_Manager::set_points_balance($user_id, $point_user, 'admin-adjustment');
}





