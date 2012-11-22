<?php
/*
Plugin Name: PostUAC
Plugin URI: http://moobin.net
Description: User Access Control at the post level.
Version: 0.1
Author: Joel A. Villarreal Bertoldi (at Moobin)
Author URI: http://moobin.net
License: MIT
*/

function postuac_get_users_list() {
  $users = array();
  foreach (get_users("blog_id=1") as $user) {
    $users[] = get_userdata($user->ID);
  };
  return $users;
};

function postuac_post_meta_box() {
  add_meta_box( 'postuac-metabox', "Usuarios permitidos", 'postuac_post_meta_box_form', 'page', 'side', 'low');
  add_meta_box( 'postuac-metabox', "Usuarios permitidos", 'postuac_post_meta_box_form', 'post', 'side', 'low');
};

function postuac_post_meta_box_form() {
  $allowed_users = get_post_meta($_REQUEST["post"], 'postuac-users', false);
  $redirect_url = get_post_meta($_REQUEST["post"], 'postuac-redirect-url', true);

  foreach (postuac_get_users_list() as $user) {
    if ($user->user_nicename == 'admin') continue;
    echo "<input type='checkbox' " . (in_array($user->ID, $allowed_users) ? "checked" : "") 
       . " name='postuac-users[]' value='{$user->ID}'> "
       . "{$user->user_firstname} {$user->user_lastname}<br />";
  };
  echo "<p>Enviar a esta URL a los usuarios no autorizados:<br/>";
  echo "<input type='text' name='postuac-redirect-url' value='$redirect_url'/></p>";
};

function postuac_save_post($postID) {
  delete_post_meta($postID, 'postuac-users');
  foreach ($_REQUEST["postuac-users"] as $user) {
    add_post_meta($postID, 'postuac-users', $user);
  };
  delete_post_meta($postID, 'postuac-redirect-url');
  add_post_meta($postID, 'postuac-redirect-url', $_REQUEST["postuac-redirect-url"]);
};

function postuac_is_user_allowed_on_this_post($wp_query) {
  $allowed_users = get_post_meta($wp_query->post->ID, 'postuac-users', false);
  $redirect_url = get_post_meta($wp_query->post->ID, 'postuac-redirect-url', true);
  $user = wp_get_current_user();

  if ($wp_query->is_single == 1 && $user->ID != 0 && $user->user_nicename != "admin" && !in_array($user->ID, $allowed_users)) {
    if ($redirect_url != "") 
      echo "<script type='text/javascript'>location = \"$redirect_url\";</script>";
    else
      echo "<script>location = 'about:blank';</script>";
    
    die;
  }
};

add_action("loop_start", "postuac_is_user_allowed_on_this_post");
add_action('admin_menu', "postuac_post_meta_box");
add_action('save_post', "postuac_save_post", 10, 1);