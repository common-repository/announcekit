<?php

/**
 * Plugin Name: AnnounceKit
 * Plugin URI: https://announcekit.app/docs
 * Description: Beautifully designed newsfeed powered with fancy widgets and email notifications.
 * Version: 2.0.9
 * Text Domain: announcekit
 * Author: AnnounceKit
 * Author URI: https://announcekit.app
 * Tested up to: 5.5.3
 * License: GNUGPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */



 
class Announcekit
{
  public $settings;
  public $key = 'ak_';
  public $plugin_name = 'Announcekit';
  public $plugin_path = __FILE__;

  public $menu_list = [];

  public $default_settings = [
    'widget_list' => []
  ];

  function __construct()
  {
    register_activation_hook(__FILE__, array(&$this, 'plugin_init'));

    $this->settings = get_option($this->key);

    if (!is_array($this->settings)) {
      update_option($this->key, $this->default_settings);
    }

    $this->settings = get_option($this->key);

    add_filter("plugin_action_links_" . plugin_basename($this->plugin_path), array(&$this, 'plugins_page_links'));
    add_filter('plugin_row_meta', array(&$this, 'plugins_page_links_meta'), 10, 2);

    add_action('admin_menu', array(&$this, 'insert_menu'));
    add_action('admin_init', array(&$this, 'init'));

    add_action('wp_footer', array(&$this, 'load_scripts'));

    function load_custom_wp_admin_style($hook)
    {
      if (strpos($hook, 'announcekit') !== false) {
        wp_enqueue_script('my_custom_script', plugins_url('admin.js', __FILE__), array('jquery'), 'v1');
        wp_enqueue_style('bootstrap', plugins_url('bootstrap.min.css', __FILE__), null, 'v1');
      }
    }

    add_action('admin_enqueue_scripts', 'load_custom_wp_admin_style');

    register_setting($this->key, $this->key, function ($input) {
      if (sanitize_text_field($_POST['action']) !== 'update') {
        return $this->settings;
      }

      $widget_id = sanitize_text_field($_GET['widget_id']);

      $widget_url = [];
      preg_match('/https:\/\/(.*)\/widgets?(\/v2)?\/([a-zA-Z0-9]+)/', $input['js_code'], $widget_url);

      if ($widget_url && $widget_url[0]) {
        $input['widget_url'] = $widget_url[0];
      }

      $input['type'] = "Float";

      preg_match('/"selector"/', $input['js_code'], $type);

      if (count($type)) {
        $input['type'] = "Badge / Line";
      }

      preg_match('/"embed"/', $input['js_code'], $embed);

      if (count($embed)) {
        $input['type'] = "Embed";
      }

      if ($input['type'] == "Float") {
        $input['selector'] = null;
      }

      if ($widget_id != "") {
        $this->settings['widget_list'][$widget_id] = $input;
      } else {
        array_push($this->settings['widget_list'], $input);
      }

      return $this->settings;
    });

    if (isset($_GET['delete_widget_id'])) {
      array_splice($this->settings['widget_list'], sanitize_text_field($_GET['delete_widget_id']), 1);

      update_option($this->key, $this->settings);

      header("Location: options-general.php?page=" . plugin_basename($this->plugin_path));
      die();
    }
  }

  function get_menu_list()
  {
    $ml = [];

    $nav_menus = wp_get_nav_menus();

    foreach (array_values($nav_menus) as $key => $menuID) {
      $menus = wp_get_nav_menu_items($menuID);

      if (is_array($menus)) {
        foreach ($menus as $menu) {
          array_push($ml, $menu);
        }
      }
    }

    return $ml;
  }

  function plugin_init()
  {
    update_option($this->key, $this->default_settings);
  }

  function insert_menu()
  {
    add_options_page($this->plugin_name, $this->plugin_name, 'administrator', $this->plugin_path, array(
      &$this,
      'render_settings_page'
    ));
  }

  function plugins_page_links($links)
  {
    $settings_link =
      '<a href="options-general.php?page=' . plugin_basename($this->plugin_path) . '">' . __('Settings') . '</a>';

    array_push($links, $settings_link);

    return $links;
  }

  function plugins_page_links_meta($links, $file)
  {
    if (strpos($file, 'announcekit') !== false) {
      $settings_link = [
        'demo' => '<a href="https://announcekit.app/" target="_blank">Demo</a>',
        'documentation' => '<a href="https://announcekit.app/docs" target="_blank">Documentation</a>'
      ];

      $links = array_merge($links, $settings_link);
    }

    return $links;
  }

  function init()
  {
    $first_section = $this->key . "first";

    add_settings_section($first_section, null, null, __FILE__);

    add_settings_field("name", 'Widget name', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "widget_name"
    ));

    add_settings_field("js_code", 'JS Code', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "js_code",
      "textarea" => true
    ));

    add_settings_field("widget_url", null, array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "widget_url",
      "hidden" => "true"
    ));

    add_settings_field("selector", 'Attach to menu', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "selector"
    ));

    add_settings_field("selector_manual", null, array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "selector_manual"
    ));

    add_settings_field(
      "user_tracking",
      "User tracking",
      array(&$this, 'render_settings_field'),
      __FILE__,
      $first_section,
      array(
        "key" => "user_tracking"
      )
    );

    add_settings_field(
      "language_auto_detect",
      "Detect the language automatically",
      array(&$this, 'render_settings_field'),
      __FILE__,
      $first_section,
      array(
        "key" => "language_auto_detect"
      )
    );

    add_settings_field("position_right", 'Right', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "position_right"
    ));

    add_settings_field("position_left", 'Left', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "position_left"
    ));

    add_settings_field("position_top", 'Top', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "position_top"
    ));

    add_settings_field("position_bottom", 'Bottom', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "position_bottom"
    ));

    add_settings_field("selector", 'Attach to menu', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "selector"
    ));

    add_settings_field("position", 'Position', array(&$this, 'render_settings_field'), __FILE__, $first_section, array(
      "key" => "position"
    ));
  }

  function render_settings_field($arg)
  {
    $widget_id = sanitize_text_field($_GET['widget_id']);

    $options = get_option($this->key);
    $html = "";

    $default_attrs = "id='" . $arg["key"] . "' name='" . $this->key . "[" . $arg["key"] . "]'";
    if (isset($widget_id)) {
      $value = $options['widget_list'][$widget_id][$arg["key"]];
    } else {
      $value = $options['widget_list'][$arg["key"]];
    }

    switch ($arg["key"]) {
      case 'position_top':
      case 'position_bottom':
      case 'position_left':
      case 'position_right':
        $html =
          "
          <div class=\"input-group\">
          <input class='form-control col-md-3' " .
          $default_attrs .
          " value='" .
          $value .
          "' />
            <div class=\"input-group-append\">
              <div class=\"input-group-text\">px</div>
            </div>
          </div>";
        break;

      case 'selector_manual':
        $html =
          "<input class='form-control'" .
          ($arg["readonly"] ? 'readonly' : '') .
          " " .
          $default_attrs .
          " value='" .
          $value .
          "' />";
        break;

      case 'user_tracking':
        $html = '<input value="1" ' . ($value == "1" ? "checked" : "") . ' type="checkbox" ' . $default_attrs . ' />';
        break;

      case 'language_auto_detect':
        $html = '<input value="1" ' . ($value == "1" ? "checked" : "") . ' type="checkbox" ' . $default_attrs . ' />';
        break;

      case 'selector':
        $opts = array_values(
          array_map(function ($item) {
            return array(
              'label' => $item->menu_item_parent ? ' + ' . $item->title : $item->title,
              'value' => $item->ID
            );
          }, $this->get_menu_list())
        );

        array_push($opts, array('label' => "Manual selector", "value" => '0'));

        $html = "<select class='form-control' " . $default_attrs . ">" . $this->render_option_tag($opts, $value) . "</select>";
        break;

      case 'position':
        $opts = array(array('value'=>'absolute', 'label'=>'absolute'), array('value'=>'relative', 'label'=>'relative'), array('value'=>'inherit', 'label'=>'inherit'));
        $html = "<select class='form-control' " . $default_attrs . ">" . $this->render_option_tag($opts, $value) . "</select>";
        break;

      default:
        if ($arg['hidden']) {
          $html = "<input " . $default_attrs . "  type=\"hidden\"/>";
          break;
        }

        if ($arg['textarea']) {
          $html =
            "<textarea rows='4' class='form-control'" .
            ($arg["readonly"] ? 'readonly' : '') .
            " " .
            $default_attrs .
            " >" .
            $value .
            "</textarea>";
        } else {
          $html =
            "<input class='form-control' required=required " .
            ($arg["readonly"] ? 'readonly' : '') .
            " " .
            $default_attrs .
            " value='" .
            $value .
            "' />";
        }
        break;
    }

    if ($arg['placeholder']) {
      $html = $html . "<span style='display: block'>Example: " . htmlspecialchars($arg['placeholder']) . "</span> ";
    }

    echo $html;
  }

  function render_option_tag($opts, $value)
  {
    $optsHtml = "";

    foreach ($opts as $item) {
      $optsHtml .=
        "<option value=\"" .
        $item['value'] .
        "\" " .
        ($item['value'] == $value ? "selected" : "") .
        ">" .
        $item["label"] .
        "</option>\n";
    }

    return $optsHtml;
  }

  function render_settings_page()
  {
    $menuArr = [];
    foreach ($this->get_menu_list() as $key => $menu) {
      $menuArr[$menu->ID] = $menu;
    }
    ?>

    <div class="col-md-7">

      <div style="text-align: center">
        <img style="width:50%; margin-top: 10px; margin-bottom:10px;" src="<?php echo plugin_dir_url(
          $this->plugin_path
        ); ?>assets/be-banner.png" />
        <p> Boost user engagement, retention and customer happiness 🎉 <br />
        Beautifully designed newsfeed powered with fancy widgets and email notifications.
        </p>

      </div>
      <a style="float:right" href="options-general.php?page=<?php echo plugin_basename(
        $this->plugin_path
      ); ?>&add_widget=true">Add widget</a>

<table class="table">
      <thead>
        <tr>
        <th>#</th>
        <th>Widget</th>
        <th>Type</th>
        <th>Attached to</th>
        <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($this->settings['widget_list'] as $key => $item) { ?>

        <tr>
          <td><?php echo $key + 1; ?></td>
          <td><?php echo $item['widget_name']; ?></td>
          <td><?php echo $item['type']; ?></td>
          <td><?php echo $item['selector'] ? $menuArr[$item['selector']]->title : $item['selector_manual']; ?></td>
          <td><a href="options-general.php?page=<?php echo plugin_basename(
            $this->plugin_path
          ); ?>&widget_id=<?php echo $key; ?>">Edit</a> / <a href="options-general.php?page=<?php echo plugin_basename(
  $this->plugin_path
); ?>&delete_widget_id=<?php echo $key; ?>">Delete</a></td>
        </tr>
      <?php } ?>
      <tr><td style="text-align:center;" colspan="5"><small>Before adding a widget, make sure you have already created it in <a target="_blank" href="https://announcekit.app">AnnounceKit</a>.</small></td> </tr>
      </tbody>
      </table>

      <?php
      $display = 'none';

      if (isset($_GET['widget_id'])) {
        $display = 'block';
      }

      if (isset($_GET['add_widget'])) {
        $display = 'block';
      }
      ?>

      <form style="display:<?php echo $display; ?>" class="submit-form" action="options.php?widget_id=<?php echo sanitize_text_field(
  $_GET['widget_id']
); ?>" method="post">

      <div class="add-widget-page">
      <h4 style="margin-top: 40px;text-align:center;"><?php echo sanitize_text_field($_GET['widget_id']) != ""
        ? 'Edit widget'
        : 'Add widget'; ?></h4>
      <?php settings_fields($this->key); ?>
      <?php do_settings_sections($this->plugin_path); ?>

      </div>

      <p style="text-align: right">
        <input name="Submit" type="submit" class="btn btn-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
      </p>
      </form>
	  </div>

  <?php
  }

  function update_option($key, $value)
  {
    $option = $this->settings;
    $option[$key] = $value;

    update_option($this->key, $option);
  }

  function load_scripts()
  {
    $widgets = "";
    foreach ($this->settings['widget_list'] as $key => $item) {
      $conf = ["version" => 2];
      $stlye = [];
      $type = null;

      switch ($item['type']) {
        case 'Badge / Line':
          $stlye['position'] = $item['position'] ? $item['position'] : "absolute";
          $type = "badge";
          break;

        case "Float":
          $type = "float";
          break;

        case 'Embed':
          $type = "embed";
          break;

        default:
          break;
      }

      if ($item['position_right'] != "") {
        $stlye['right'] = $item['position_right'] . 'px';
      }

      if ($item['position_left'] != "") {
        $stlye['left'] = $item['position_left'] . 'px';
      }

      if ($item['position_bottom'] != "") {
        $stlye['bottom'] = $item['position_bottom'] . 'px';
      }

      if ($item['position_top'] != "") {
        $stlye['top'] = $item['position_top'] . 'px';
      }

      $conf['widget'] = $item['widget_url'];
      $conf[$type] = ['style' => $stlye];

      if (isset($item['user_tracking'])) {
        $current_user = wp_get_current_user();
        if ($current_user->exists()) {
          $conf['user'] = [
            "id" => $current_user->ID,
            "name" => $current_user->display_name,
            "email" => $current_user->user_email,
            "login" => $current_user->user_login
          ];

          $data = (array) $current_user->data;
          unset($data['user_activation_key']);
          unset($data['user_login']);
          unset($data['user_pass']);
          unset($data['user_nicename']);
          unset($data['user_email']);
          unset($data['ID']);
          unset($data['display_name']);


          $conf["data"] = $data;
          $data["roles"] = implode(',', ( array )$current_user->roles);
          $conf['data'] = $data;
        }
      }

      if(isset($item['language_auto_detect'])) {
        $conf['lang'] = "auto";
      }

      if ($item['selector'] == "0" && $item['selector_manual']) {
        $conf['selector'] = $item['selector_manual'];
        $js = json_encode(array_filter((array) $conf), JSON_UNESCAPED_SLASHES);
      } else {
        $conf['selector'] = $item['selector'] ? "XXXSELECTORXXX" : null;
        $js = json_encode(array_filter((array) $conf), JSON_UNESCAPED_SLASHES);

        if ($conf['selector']) {
          $js = str_replace(
            '"XXXSELECTORXXX"',
            "selPath(document.querySelector('#menu-item-" .
              $item['selector'] .
              "') || document.querySelector('.menu-item-" .
              $item['selector'] .
              "') || document.querySelector('.ak-man')) + ' a'",
            $js
          );
        }
      }
      $widgets .= 'window.announcekit.push(' . $js . ');';
    }

    $src = "https://cdn.announcekit.app/widget.js";

    if(isset($conf['widget']) && strpos($conf['widget'], '/v2')) {
      $src = "https://cdn.announcekit.app/widget-v2.js";
    }

    echo '
    <script type="text/javascript">
      function selPath(item) { if(!item)return null; var nodeName = item.nodeName.toLowerCase(); var id = item.getAttribute("id") ? "#" + item.getAttribute("id") : ""; var cl = [].slice.apply(item.classList);; cl.length ? cl.unshift("") : null; cl = cl.join("."); return [nodeName, id, cl].join("") }
      window.announcekit = (window.announcekit || { queue: [], on: function(n, x) { window.announcekit.queue.push([n, x]); }, push: function(x) { window.announcekit.queue.push(x); } });
      ' .
      $widgets .
      '
      </script>

      <script async src="'.$src.'"></script>
    ';
  }
}

new Announcekit();
