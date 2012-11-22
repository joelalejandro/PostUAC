<?php 
  if ($is_public == "") $is_public = "yes";

  $is_public_selected = $is_public === "yes" ? "checked" : "";
  $is_public_not_selected = $is_public === "no" ? "checked" : "";
?>
<input type="hidden" id="postuac-security-token" value="<?php echo $security_token ?>">
<input <?php echo $is_public_selected ?> id="postuac_is_public_yes" 
  type="radio" name="postuac_is_public" value="yes"> 
  <label for="postuac_is_public_yes">Visible para todos</label>
<br>
<input <?php echo $is_public_not_selected ?> id="postuac_is_public_no" 
  type="radio" name="postuac_is_public" value="no"> 
  <label for="postuac_is_public_no">Accesible para los siguientes usuarios:</label>
<blockquote>
<?php
  foreach (postuac_get_users_list() as $user) {
    if ($user->user_nicename == 'admin') continue;
    if ($user->user_firstname) {
      $username = "{$user->user_firstname} {$user->user_lastname} ($user->user_nicename)";
    } else {
      $username = "$user->user_nicename";
    };
    $checked = (in_array($user->ID, $allowed_users) ? "checked" : "");
?>
<input id="user-<?php echo $user->ID ?>" type="checkbox" 
  class="postuac user check" name="postuac-users[]"
  value="<?php echo $user->ID ?>" <?php echo $checked ?> />
  <label for="user-<?php echo $user->ID ?>"><?php echo $username ?></label>
<br>
<?php
  };
?>
</blockquote>