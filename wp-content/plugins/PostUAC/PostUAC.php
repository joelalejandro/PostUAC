<?php
/*
Plugin Name: PostUAC
Plugin URI: http://moobin.net
Description: User Access Control at the post level.
Version: 0.3
Author: Joel A. Villarreal Bertoldi (at Moobin)
Author URI: http://moobin.net
License: MIT
*/

$postuac_plugin_url = plugin_dir_url(__FILE__);
$postuac_user_allowed_pages = array();

function postuac_get_users_list() {
  $users = array();
  foreach (get_users("blog_id=1") as $user) {
    $users[] = get_userdata($user->ID);
  };
  return $users;
};

function postuac_post_meta_box() {
  add_meta_box("postuac-metabox", "Usuarios permitidos", "postuac_post_meta_box_form", "page", "side", "high");
  add_meta_box("postuac-metabox", "Usuarios permitidos", "postuac_post_meta_box_form", "post", "side", "high");
};

function postuac_post_meta_box_form() {
  $is_public = postuac_is_post_public($_REQUEST["post"]);
  $allowed_users = postuac_get_allowed_users($_REQUEST["post"]);
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

function postuac_get_allowed_users($postID) {
  return get_post_meta($postID, "postuac-users", false);
};

function postuac_is_post_public($postID) {
  return get_post_meta($postID, "postuac-is-public", true);
};

function postuac_is_allowed($postID) {
  $is_public = postuac_is_post_public($postID);
  $allowed_users = postuac_get_allowed_users($postID);
  $user = wp_get_current_user();

  if ($is_public == "" || $is_public == "yes") { return true; }
  if ($user->user_nicename == "admin") { return true; }

  if ($user->ID == 0) { return false; }
  if (!in_array($user->ID, $allowed_users)) { return false; }

  return false;
};

function postuac_is_user_allowed_on_this_post($wp_query) {
  $redirect_url = "/";

  if (!postuac_is_allowed($wp_query->post->ID)) {
    echo "<div class='post not allowed' data-post-id='{$wp_query->post->ID}' style='display:none'></div>";
  };

  if ($wp_query->is_singular == 1 && !postuac_is_allowed($wp_query->post->ID)) {
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

function postuac_get_allowed_pages() {
  global $postuac_user_allowed_pages;
  $user = wp_get_current_user();
  $pages = get_pages();
  $excluded = array();
  foreach ($pages as $page) {
    if (get_post_meta($page->ID, "postuac-is-public", true) == "yes") continue;
    if (!in_array($user->ID, get_post_meta($page->ID, "postuac-users"))) {
      $excluded[] = $page->ID;
    };
  };
  $query = "echo=0&title_li=&exclude=" . implode(",", $excluded);
  $postuac_user_allowed_pages = wp_list_pages($query);

  print_r(wp_get_nav_menus());

  add_filter("wp_list_pages", "postuac_delete_forbidden_nav_menu_items");
}

function postuac_delete_forbidden_nav_menu_items($items, $args) {
  global $postuac_user_allowed_pages;
  $user = wp_get_current_user();
  
  if ($user->user_nicename == "admin") return $items;

  return $postuac_user_allowed_pages;
};

add_action("wp_head", "postuac_get_allowed_pages");
add_action("loop_start", "postuac_is_user_allowed_on_this_post");
add_action("admin_menu", "postuac_post_meta_box");
add_action("save_post", "postuac_save_post", 10, 1);
add_action("admin_enqueue_scripts", "postuac_validate_post_script");
add_action("wp_footer", "postuac_delete_forbidden_posts_script");

add_filter("wp_nav_menu_items", "postuac_delete_forbidden_nav_menu_items", null, 3);