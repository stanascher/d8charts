<?php
/**
 * @file
 * Provides elements for rendering charts and Views integration.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;


/**
 * Constant used in hook_charts_type_info() to declare chart types with a single
 * axis. For example a pie chart only has a single dimension.
 */
define('CHARTS_SINGLE_AXIS', 'y_only');

/**
 * Constant used in hook_charts_type_info() to declare chart types with a dual
 * axes. Most charts use this type of data, meaning multiple categories each
 * have multiple values. This type of data is usually represented as a table.
 */
define('CHARTS_DUAL_AXIS', 'xy');

// Store Charts preprocess theme functions in a separate .inc file.
\Drupal::moduleHandler()->loadInclude('charts', 'inc', 'charts.theme');

  /**
   * @return mixed
   */
function getInfo()
{

///**
// * Implements hook_element_info().
// *
//  function charts_element_info()
//  {
    // Create the elements representing charts themselves.
    $info['chart'] = array(
      '#chart_type' => NULL, // Options: pie, bar, column, etc.
      '#chart_id' => NULL, // Machine name to alter this chart individually.
      '#title' => NULL,
      '#title_color' => '#000',
      '#title_font_weight' => 'normal', // Options: normal, bold
      '#title_font_style' => 'normal', // Options: normal, italic
      '#title_font_size' => 14, // Font size in pixels, e.g. 12.
      '#title_position' => 'out', // Options: in, out.
      '#colors' => charts_default_colors(),
      '#font' => 'Arial',
      '#font_size' => 12, // Font size in pixels, e.g. 12.
      '#background' => 'transparent',
      '#stacking' => NULL, // Bar chart only. Set to TRUE to stack.
      '#pre_render' => array('charts_pre_render_element'),
      '#tooltips' => TRUE,
      '#tooltips_use_html' => FALSE,
      '#data_labels' => FALSE,
      '#legend' => TRUE,
      '#legend_title' => NULL,
      '#legend_title_font_weight' => 'bold', // Options: normal, bold
      '#legend_title_font_style' => 'normal', // Options: normal, italic
      '#legend_title_font_size' => NULL, // CSS value for font size, e.g. 1em or 12px.
      '#legend_position' => 'right', // Options: top, right, bottom, left.
      '#legend_font_weight' => 'normal', // Options: normal, bold
      '#legend_font_style' => 'normal', // Options: normal, italic
      '#legend_font_size' => NULL, // CSS value for font size, e.g. 1em or 12px.
      '#width' => NULL,
      '#height' => NULL,

      '#attributes' => array(), // Applied to the wrapper, not the chart.
      '#chart_library' => NULL, // If requiring a particular charting module.
      '#raw_options' => array(), // Raw library-specific options passed directly.
    );

    // Properties for x and y axes.
    $axis_properties = array(
      '#axis_type' => 'linear', // Options: linear, logarithmic, datetime, labels.
      '#title' => '',
      '#title_color' => '#000',
      '#title_font_weight' => 'normal', // Options: normal, bold
      '#title_font_style' => 'normal', // Options: normal, italic
      '#title_font_size' => 12, // CSS value for font size, e.g. 1em or 12px.
      '#labels' => NULL,
      '#labels_color' => '#000',
      '#labels_font_weight' => 'normal', // Options: normal, bold
      '#labels_font_style' => 'normal', // Options: normal, italic
      '#labels_font_size' => NULL, // CSS value for font size, e.g. 1em or 12px.
      '#labels_rotation' => NULL, // Integer rotation value, e.g. 30, -60 or 90.
      '#grid_line_color' => '#ccc',
      '#base_line_color' => '#ccc',
      '#minor_grid_line_color' => '#e0e0e0',
      '#max' => NULL, // Integer max value on this axis.
      '#min' => NULL, // Integer minimum value on this axis.
      '#opposite' => FALSE, // Display axis on opposite normal side.
    );
    $info['chart_xaxis'] = array() + $axis_properties;
    $info['chart_yaxis'] = array() + $axis_properties;

    // And then the pieces of charts used within chart types.
    $info['chart_data'] = array(
      '#title' => NULL,
      '#labels' => NULL,
      '#data' => array(),
      '#color' => NULL,
      '#show_in_legend' => TRUE,
      '#show_labels' => FALSE, // Show inline labels next to the data.
      '#chart_type' => NULL, // If building multicharts. The chart type, e.g. pie.
      '#line_width' => 1, // Line chart only.
      '#marker_radius' => 3, // Line chart only. Size in pixels, e.g. 1, 5.
      '#target_axis' => NULL, // If using multiple axes, key for the matching y axis.

      // Formatting options.
      '#decimal_count' => NULL, // The number of digits after the decimal separator. e.g. 2
      '#date_format' => NULL, // A custom date format, e.g. %Y-%m-%d
      '#prefix' => NULL,
      '#suffix' => NULL,
    );
    $info['chart_data_item'] = array(
      '#data' => NULL,
      '#color' => NULL,
      '#title' => NULL, // Often used as content of the tooltip.
    );

    return $info;
  }
//}



/**
 * Implements hook_permission().
 */
function charts_permission()
{
  return array(
    'administer charts' => array(
      'title' => t('Administer Charts'),
      //todo  'description' => t('Visit the <a href="!url">Default chart configuration</a> (used for Charts administration)', array('!url' => url('admin/config/content/charts'))),
    ),
    'access example charts' => array(
      'title' => t('Access example charts'),
      //todo    'description' => t('Visit the <a href="!url">Charts examples</a> (used for Charts testing and demo)', array('!url' => url('charts/examples'))),
    ),
  );
}



/**
 * Main #pre_render callback to expand a chart element.
 * @param $element
 * @return
 */
//function preRenderChart($element)
//{
//function charts_pre_render_element($element) {
//  $charts_info = charts_info();
//  $chart_library = isset($element['#chart_library']) ? $element['#chart_library'] : NULL;
//  // Use the first charting library if the requested library is not available.
//  if (isset($chart_library) && isset($charts_info[$chart_library])) {
//    $chart_library_info = $charts_info[$chart_library];
//  } else {
//    $chart_library = key($charts_info);
//    $chart_library_info = $charts_info[$chart_library];
//  }
//
//  if (!isset($chart_library_info)) {
//    $element['#type'] = 'markup';
//    $element['#markup'] = t('No charting library found. Enable a charting module such as Google Charts or Highcharts.');
//    return $element;
//  }
//
//  // Ensure there's an x and y axis to provide defaults.
//  $chart_type = chart_get_type($element['#chart_type']);
//
//  if ($chart_type['axis'] === CHARTS_DUAL_AXIS) {
////    foreach (\Drupal::state()->getMultiple($element) as $key) { //element_children
//      foreach (Element::children($element) as $key) {
//
//      $children_types[] = $element[$key]['#type'];
//
//    }
//    if (!in_array('chart_xaxis', $children_types)) {
//      $element['xaxis'] = array('#type' => 'chart_xaxis');
//    }
//    if (!in_array('chart_yaxis', $children_types)) {
//      $element['yaxis'] = array('#type' => 'chart_yaxis');
//    }
//  }
//
//  // Convert integer properties to save library modules the hassle.
//  charts_cast_element_integer_values($element);
//
//  // Generic theme function assuming it will be suitable for most chart types.
//  $element['#theme'] = 'charts_chart';
//
//  // Allow the chart to be altered.
//  $alter_hooks = array('chart');
//  if ($element['#chart_id']) {
//    $alter_hooks[] = 'chart_' . $element['#chart_id'];
//  }
//  Drupal::moduleHandler()->alter($alter_hooks, $element, $element['#chart_id']);
//
//  // Include the library specific file and render via their callback.
//  if (isset($chart_library_info['file'])) {
//    $charting_module_name = $chart_library_info['module'];
////    include_once $module_path . '/' . $chart_library_info['file'];
//    module_load_include('inc', $charting_module_name, $charting_module_name);
//  }
//  $callback = $chart_library_info['render'];
//  $element = $callback($element);
//
//  if (!empty($element['#chart_definition'])) {
//    $chart_definition = $element['#chart_definition'];
//    unset($element['#chart_definition']);
//
//    // Allow the chart definition to be altered.
//    $alter_hooks = array('chart_definition');
//    if ($element['#chart_id']) {
//      $alter_hooks[] = 'chart_definition_' . $element['#chart_id'];
//    }
//    Drupal::moduleHandler()->alter($alter_hooks, $chart_definition, $element, $element['#chart_id']);
//
//    // Set the element #chart_json property as a data-attribute.
//    $element['#attributes']['data-chart'] = json_encode($chart_definition);
//  }
//  return $element;
//}

/**
 * Retrieve a list of all charting libraries available.
 *
 * @see hook_charts_info()
 */
function charts_info()
{
  $charts_info = array();
  $chart_modules = Drupal::moduleHandler()->getImplementations('charts_info');
  foreach ($chart_modules as $module) {
    $module_charts_info = Drupal::moduleHandler()->invoke($module, 'charts_info');
    foreach ($module_charts_info as $chart_library => $chart_library_info) {
      $module_charts_info[$chart_library]['module'] = $module;
    }
    $charts_info = array_merge($charts_info, $module_charts_info);
  }
  Drupal::moduleHandler()->alter('charts_info', $charts_info);
  return $charts_info;
}


/**
 * Retrieve a specific chart library.
 * @param $library
 * @return bool|mixed
 */
function chart_get_library($library)
{
  $info = charts_info();
  return $info[$library] ? $info[$library] : FALSE;
}

/**
 * Retrieve a list of all chart types available.
 *
 * @see hook_charts_type_info()
 */
function charts_type_info()
{
  $charts_type_info = Drupal::moduleHandler()->invokeAll('charts_type_info');

  foreach ($charts_type_info as $chart_type => $chart_type_info) {
    $charts_type_info[$chart_type] += array(
      'label' => '',
      'axis' => CHARTS_DUAL_AXIS,
      'axis_inverted' => FALSE,
      'stacking' => FALSE,
    );
  }

  Drupal::moduleHandler()->alter('charts_type_info', $charts_type_info);
  return $charts_type_info;
}

/**
 * Retrieve a specific chart type.
 * @param $chart_type
 * @return bool
 */
function chart_get_type($chart_type) {
  $types = charts_type_info();
  return ($types[$chart_type])? $types[$chart_type]: FALSE;
}

/**
 * Implements hook_charts_type_info().
 */
function charts_charts_type_info() {
  $chart_types['pie'] = array(
    'label' => t('Pie'),
    'axis' => CHARTS_SINGLE_AXIS,
  );
  $chart_types['bar'] = array(
    'label' => t('Bar'),
    'axis' => CHARTS_DUAL_AXIS,
    'axis_inverted' => TRUE, // Meaning x/y axis are flipped.
    'stacking' => TRUE,
  );
  $chart_types['column'] = array(
    'label' => t('Column'),
    'axis' => CHARTS_DUAL_AXIS,
    'stacking' => TRUE,
  );
  $chart_types['line'] = array(
    'label' => t('Line'),
    'axis' => CHARTS_DUAL_AXIS,
  );
  $chart_types['area'] = array(
    'label' => t('Area'),
    'axis' => CHARTS_DUAL_AXIS,
    'stacking' => TRUE,
  );
  $chart_types['scatter'] = array(
    'label' => t('Scatter'),
    'axis' => CHARTS_DUAL_AXIS,
  );
  return $chart_types;
}

/**
 * Default colors used in all libraries.
 */
function charts_default_colors() {
  return array(
    '#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce',
    '#492970', '#f28f43', '#77a1e5', '#c42525', '#a6c96a',
  );
}

/**
 * Recursive function to trim out empty options that aren't used.
 * @param $array
 */
function charts_trim_array(&$array) {
  foreach ($array as $key => &$value) {
    if (is_array($value)) {
      charts_trim_array($value);
    } elseif (is_null($value) || (is_array($value) && count($value) === 0)) {
      unset($array[$key]);
    }
  }
}

/**
 * Recursive function to cast integer values.
 * @param $element
 */
function charts_cast_element_integer_values(&$element)
{
  // Cast options to integers to avoid redundant library fixing problems.
  $integer_options = array(
    // Chart options.
    '#title_font_size',
    '#font_size',
    '#legend_title_font_size',
    '#legend_font_size',
    '#width',
    '#height',

    // Axis options.
    '#title_font_size',
    '#labels_font_size',
    '#labels_rotation',
    '#max',
    '#min',

    // Data options.
    '#decimal_count',
  );

  foreach ($element as $property_name => $value) {
    if (is_array($element[$property_name])) {
      charts_cast_element_integer_values($element[$property_name]);
    } elseif ($property_name && in_array($property_name, $integer_options)) {
      $element[$property_name] = (is_null($element[$property_name]) || strlen($element[$property_name]) === 0)
      ? NULL : (int)$element[$property_name];
    }
  }
}
