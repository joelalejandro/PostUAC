<?php
/*
Plugin Name: PostUAC
Plugin URI: http://moobin.net
Description: User Access Control at the post level.
Version: 0.2
Author: Joel A. Villarreal Bertoldi (at Moobin)
Author URI: http://moobin.net
License: MIT
*/

$postuac_plugin_url = plugin_dir_url(__FILE__);

function postuac_get_users_list() {
  $users = array();
  foreach (get_users("blog_id=1") as $user) {
    $users[] = get_userdata($user->ID);
  };
  return $users;
};

function postuac_post_meta_box() {
  add_meta_box( "postuac-metabox", "Usuarios permitidos", "postuac_post_meta_box_form", "page", "side", "high");
  add_meta_box( "postuac-metabox", "Usuarios permitidos", "postuac_post_meta_box_form", "post", "side", "high");
};

function postuac_post_meta_box_form() {
  $is_public = get_post_meta($_REQUEST["post"], "postuac-is-public", true);

  $allowed_users = get_post_meta($_REQUEST["post"], "postuac-users", false);
  $security_token = wp_create_nonce("pre_publish_validation");

  include "Widget.php";
};

function postuac_save_post($postID) {
  delete_post_meta($postID, "postuac-users");
  delete_post_meta($postID, "postuac-is-public");
  add_post_meta($postID, "postuac-is-public", isset($_REQUEST["postuac_is_public"]) && $_REQUEST["postuac_is_public"] == "yes" ? "yes" : "no");
  foreach ($_REQUEST["postuac-users"] as $user) {
    add_post_meta($postID, "postuac-users", $user);
  };
};

function postuac_is_user_allowed_on_this_post($wp_query) {
  $allowed_users = get_post_meta($wp_query->post->ID, "postuac-users", false);
  $is_public = get_post_meta($wp_query->post->ID, "postuac-is-public", true);

  $redirect_url = "/";
  $user = wp_get_current_user();

  if ($is_public == "") $is_public = "yes";
  if ($is_public == "yes") return;

  if ($user->ID != 0 && $user->user_nicename != "admin" && !in_array($user->ID, $allowed_users)) {
    echo "<div class='post not allowed' data-post-id='{$wp_query->post->ID}' style='display:none'></div>";
  };

  if ($wp_query->is_singular == 1 && $user->ID != 0 && $user->user_nicename != "admin" && !in_array($user->ID, $allowed_users)) {
    echo "<script type=\"text/javascript\">location = \"$redirect_url\";</script>";
    die;
  };
};

function postuac_delete_forbidden_posts_script() {
  global $postuac_plugin_url;
  if (!is_admin()) {
    wp_enqueue_script("postuac-handler", $postuac_plugin_url . "PostUAC.Manager.js", array("jquery"), null, true);
  };
}

function postuac_validate_post_script($page) {
  global $postuac_plugin_url;
  if ($page === "post.php") {
    wp_enqueue_script("postuac", $postuac_plugin_url . "PostUAC.js", null, null, true);
  };
};

add_action("loop_start", "postuac_is_user_allowed_on_this_post");
add_action("admin_menu", "postuac_post_meta_box");
add_action("save_post", "postuac_save_post", 10, 1);
add_action("admin_enqueue_scripts", "postuac_validate_post_script");
add_action("wp_footer", "postuac_delete_forbidden_posts_script");