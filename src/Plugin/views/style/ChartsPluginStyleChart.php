<?php
/**
 * @file
 * Contains the Chart style (format) plugin (similar to Table, HTML List, etc.)
 */

namespace Drupal\charts\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\views\style\StylePluginBase;

\Drupal::moduleHandler()->loadInclude('charts', 'inc', 'charts.theme');
\Drupal::moduleHandler()->loadInclude('charts', 'php', 'charts.functions');
\Drupal::moduleHandler()->loadInclude('charts', 'inc', 'includes/charts.pages');

/**
 * Style plugin to render view as a chart.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "chart",
 *   title = @Translation("Chart"),
 *   help = @Translation("Render a chart of your data."),
 *   theme = "views_view_charts",
 *   display_types = { "normal" }
 * )
 *
 */
class ChartsPluginStyleChart extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;
  protected $usesFields = TRUE;
  protected $usesRowPlugin = TRUE;


  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Get the default chart values
    $defaults = \Drupal::state()->get('charts_default_settings', array());  // was variable_get()

    $defaults += charts_default_settings();
    foreach ($defaults as $default_key => $default_value) {
      $options[$default_key]['default'] = $default_value;
    }

    // Remove the default setting for chart type so it can be inherited if this
    // is a chart extension type.
    if ($this->view->style_plugin === 'chart_extension') {
      $options['type']['default'] = NULL;
    }
    $options['path'] = array('default' => 'charts');

    return $options;
  }

  /**
   * Generate a form for setting options.
   * @param $form
   * @param FormStateInterface $form_state
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = array(
        '#markup' => '<div class="error messages">' . t('You need at least one field before you can configure your table settings') . '</div>',
      );
    }

    // Limit grouping options (we only support one grouping field).
    if (isset($form['grouping'][0])) {
      $form['grouping'][0]['field']['#title'] = t('Grouping field');
      $form['grouping'][0]['field']['#description'] = t('If grouping by a particular field, that field will be used to generate multiple data series on the same chart.');
      $form['grouping'][0]['field']['#attributes']['class'][] = 'charts-grouping-field';
      // Grouping by rendered version has no effect in charts. Hide the options.
      $form['grouping'][0]['rendered']['#access'] = FALSE;
      $form['grouping'][0]['rendered_strip']['#access'] = FALSE;
    }
    if (isset($form['grouping'][1])) {
      $form['grouping'][1]['#access'] = FALSE;
    }

    // Merge in the global chart settings form.
    $field_options = $this->displayHandler->getFieldLabels();
    $form = charts_settings_form($form, $this->options, $field_options, array('style_options'));

    // Reduce the options if this is a chart extension.
    if (empty($this->displayHandler->getAttachedDisplays())) {
      $form['type']['#description'] = empty($form['type']['#description']) ? '' : $form['type']['#description'] . ' ';
      $form['type']['#description'] .= t('This chart will be combined with the parent display "@display_title",
          which is a "@type" chart. Not all chart types may be combined. Selecting a different chart type than
          the parent may cause errors.' //,
      //    array('@display_title' => $parent_display->display_title, '@type' => $parent_chart_type['label'])
      );
      $form['fields']['label_field']['#disabled'] = TRUE;
      $form['display']['#access'] = FALSE;
      $form['xaxis']['#access'] = FALSE;
      if ($this->displayHandler->options['inherit_yaxis']) {
        $form['yaxis']['#access'] = FALSE;
      }
      else {
        $form['yaxis']['#title'] = t('Secondary axis');
        $form['yaxis']['#attributes']['class'] = array();
      }
    }
  }

  /**
   * Generate a form for setting options.
   * @param $form
   * @param FormStateInterface $form_state
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
  }


  /**
   * Make sure the display and all associated handlers are valid.
   * @return array Empty array if the display is valid; an array of error strings if it is not.
   * Empty array if the display is valid; an array of error strings if it is not.
   */
  public function validate() {

      $errors = parent::validate();
      $dataFields = $this->options['data_fields'];
      $dataFieldsValueState = array();
      $dataFieldsCounter = 0;

      foreach($dataFields as $key => $value) {
          /*if (count(array_unique($plugin)) === 1 && end($plugin) === 0) {
              $errors[] = $this->t('At least one data field must be selected in the chart configuration before this chart may be shown');
          }*/
          /*Skip title field no need to validate it and if data field is set add to dataFieldsValueState array state 1
          otherwise add to same array state 0*/
          if ($dataFieldsCounter > 0){
              if (empty($value)){
                  array_push($dataFieldsValueState, 0);
              }
              else{
                  array_push($dataFieldsValueState, 1);
              }
          }
          $dataFieldsCounter++;
      }
      /*If total sum of dataFieldsValueState is less than 1, then no dataFields were selected otherwise 1 or more selected
      total sum will be greater than 1*/
      if (array_sum($dataFieldsValueState) < 1)
          $errors[] = $this->t('At least one data field must be selected in the chart configuration before this chart may be shown');
      return $errors;
  }

  /**
   * Render the entire view from the view result.
   */
  public function render() {
    $field_handlers = $this->view->getHandlers('field');

    // Calculate the labels field alias.
    $label_field = FALSE;
    $label_field_key = NULL;
    if ($this->options['label_field'] && array_key_exists($this->options['label_field'], $field_handlers)) {
      $label_field = $field_handlers[$this->options['label_field']];
      $label_field_key = $this->options['label_field'];
    }

    // Assemble the fields to be used to provide data access.
    $data_field_options = array_filter($this->options['data_fields']);
    $data_fields = array();
    foreach ($data_field_options as $field_key) {
      if (isset($field_handlers[$field_key])) {
        $data_fields[$field_key] = $field_handlers[$field_key];
      }
    }

    // Do not allow the label field to be used as a data field.
    if (isset($data_fields[$label_field_key])) {
      unset($data_fields[$label_field_key]);
    }

    $chart_id = $this->view->id() . '__' . $this->view->current_display;
    $chart = array(
      '#type' => 'chart',
      '#chart_type' => $this->options['type'],
      '#chart_library' => $this->options['library'],
      '#chart_id' => $chart_id,
      '#id' => ('chart_' . $chart_id),
      '#title' => $this->options['title_position'] ? $this->options['title'] : FALSE,
      '#title_position' => $this->options['title_position'],
      '#tooltips' => $this->options['tooltips'],
      '#data_labels' => $this->options['data_labels'],
      '#colors' => $this->options['colors'],
      '#background' => $this->options['background'] ? $this->options['background'] : 'transparent',
      '#legend' => $this->options['legend_position'] ? TRUE : FALSE,
      '#legend_position' => $this->options['legend_position'] ? $this->options['legend_position'] : NULL,
      '#width' => $this->options['width'],
      '#height' => $this->options['height'],
      '#view' => $this->view, // pass info about the actual view results to allow further processing
      '#theme' => 'views_view_charts',
    );
    $chart_type_info = chart_get_type($this->options['type']);
    if ($chart_type_info['axis'] === CHARTS_SINGLE_AXIS) {
      $data_field_key = key($data_fields);
      $data_field = $data_fields[$data_field_key];

      $data = array();
        $this->renderFields($this->view->result);
        $renders = $this->rendered_fields;
        foreach ($renders as $row_number => $row) {
        $data_row = array();
        if ($label_field_key) {
          // Labels need to be decoded, as the charting library will re-encode.
          $data_row[] = htmlspecialchars_decode($this->getField($row_number, $label_field_key), ENT_QUOTES);
        }
        $value = $this->getField($row_number, $data_field_key);
        // Convert empty strings to NULL.
        if ($value === '') {
          $value = NULL;
        }
        // Strip thousands placeholders if present, then cast to float.
        else {
          $value = (float) str_replace(array(',', ' '), '', $value);
        }
        $data_row[] = $value;
        $data[] = $data_row;
      }

      if ($label_field) {
        $chart['#legend_title'] = $label_field->options['label'];
      }

      $chart[$this->view->current_display . '_series'] = array(
        '#type' => 'chart_data',
        '#data' => $data,
        '#title' => $data_field->options['label'],
      );

    }
    else {
      $chart['xaxis'] = array(
        '#type' => 'chart_xaxis',
        '#title' => $this->options['xaxis_title'] ? $this->options['xaxis_title'] : FALSE,
        '#labels_rotation' => $this->options['xaxis_labels_rotation'],
      );
      $chart['yaxis'] = array(
        '#type' => 'chart_yaxis',
        '#title' => $this->options['yaxis_title'] ? $this->options['yaxis_title'] : FALSE,
        '#labels_rotation' => $this->options['yaxis_labels_rotation'],
        '#max' => $this->options['yaxis_max'],
        '#min' => $this->options['yaxis_min'],
      );

      $sets = $this->renderGrouping($this->view->result, $this->options['grouping'], TRUE);
      foreach ($sets as $series_label => $data_set) {
        $series_index = isset($series_index) ? $series_index + 1 : 0;
        $series_key = $this->view->current_display . '__' . $field_key . '_' . $series_index;
        foreach ($data_fields as $field_key => $field_handler) {
          $chart[$series_key] = array(
            '#type' => 'chart_data',
            '#data' => array(),
            // If using a grouping field, inherit from the chart level colors.
            '#color' => ($series_label === '' && isset($this->options['field_colors'][$field_key])) ? $this->options['field_colors'][$field_key] : NULL,
            '#title' => $series_label ? $series_label : $field_handler->options['label'],
            '#prefix' => $this->options['yaxis_prefix'] ? $this->options['yaxis_prefix'] : NULL,
            '#suffix' => $this->options['yaxis_suffix'] ? $this->options['yaxis_suffix'] : NULL,
            '#decimal_count' => $this->options['yaxis_decimal_count'] ? $this->options['yaxis_decimal_count'] : NULL,
          );
        }

        // Grouped results come back indexed by their original result number
        // from before the grouping, so we need to keep our own row number when
        // looping through the rows.
        $row_number = 0;
        foreach ($data_set['rows'] as $result_number => $row) {
          if ($label_field_key && !isset($chart['xaxis']['#labels'][$row_number])) {
            $chart['xaxis']['#labels'][$row_number] = $this->getField($result_number, $label_field_key);
          }
          foreach ($data_fields as $field_key => $field_handler) {
            // Don't allow the grouping field to provide data.
            if (isset($this->options['grouping'][0]['field']) && $field_key === $this->options['grouping'][0]['field']) {
              continue;
            }

            $value = $this->getField($result_number, $field_key);
            // Convert empty strings to NULL.
            if ($value === '') {
              $value = NULL;
            }
            // Strip thousands placeholders if present, then cast to float.
            else {
              $value = (float) str_replace(array(',', ' '), '', $value);
            }
            $chart[$series_key]['#data'][] = $value;
          }
          $row_number++;
        }
      }
    }

    // Check if this display has any children charts that should be applied
    // on top of it.
    if($this->pluginDefinition['id'] === 'chart'
          && $this->displayHandler->pluginDefinition['id'] !== 'chart_extension') {
        $parent_display_id = $this->displayHandler->display['id'];
    }

    $children_displays = $this->get_children_chart_displays();

    foreach ($children_displays as $child_display) {
      // If the user doesn't have access to the child display, skip.
      if (!$this->view->access($child_display)) {
        continue;
      }

      // Generate the subchart by executing the child display. We load a fresh
      // view here to avoid collisions in shifting the current display while in
      // a display.
      $subview = $this->view->createDuplicate();
      $subview->setDisplay($child_display);
    //   Copy the settings for our axes over to the child view.

      foreach ($this->options as $option_name => $option_value) {
        if (strpos($option_name, 'yaxis') === 0
        && $this->view->storage->getDisplay($child_display)['display_options']['inherit_yaxis']) {
          $subview->display_handler->options['style_options'][$option_name] = $option_value;
        }
        elseif (strpos($option_name, 'xaxis') === 0) {
          $subview->display_handler->options['style_options'][$option_name] = $option_value;
        }
      }

      // Execute the subview and get the result.
      $subview->preExecute();
      $subview->execute();

      // If there's no results, don't attach the subview.
      if (empty($subview->result)) {
        continue;
      }

      $subchart = $subview->style_plugin->render(); //$subview->style_plugin->render($subview->result);

      $subview->postExecute();
      unset($subview);

  //     Create a secondary axis if needed.
      if ($this->view->storage->getDisplay($child_display)['display_options']['inherit_yaxis'] !== '1'
      && isset($subchart['yaxis'])) {
        $chart['secondary_yaxis'] = $subchart['yaxis'];
        $chart['secondary_yaxis']['#opposite'] = TRUE;
      }

//       Merge in the child chart data.
      //foreach (\Drupal::state()->getMultiple($subchart) as $key) {
      //foreach (\Drupal::state()->getMultiple($subchart) as $key) {
      foreach (Element::children($subchart) as $key) {
        if ($subchart[$key]['#type'] === 'chart_data') {
          $chart[$key] = $subchart[$key];
          // If the subchart is a different type than the parent chart, set
          // the #chart_type property on the individual chart data elements.
          if ($subchart['#chart_type'] !== $chart['#chart_type']) {
            $chart[$key]['#chart_type'] = $subchart['#chart_type'];
          }
          if ($this->view->storage->getDisplay($child_display)['display_options']['inherit_yaxis'] !== '1') {
            $chart[$key]['#target_axis'] = 'secondary_yaxis';
          }
        }
      }
    }

    // Print the chart.
    return $chart;
  }

  /**
   * Utility function to check if this chart has a parent display.
   */
  function get_parent_chart_display() {
    $parent_display = FALSE;
//    if ($this->view->style_plugin === 'chart'
//        && $this->displayHandler->display && $this->displayHandler->options['parent_display']) {
//      $parent_display_name = $this->displayHandler->options['parent_display'];
//      if (isset($this->view->display_handler->display[$parent_display_name])) {
//        $parent_display = $this->view->display_handler->display[$parent_display_name];
//      }
//    }
//    // Ensure the parent is a chart.
//    if ($parent_display && $parent_display->display_options['style_plugin'] !== 'chart') {
//      $parent_display = FALSE;
//    }



    return $parent_display;
  }

  /**
   * Utility function to check if this chart has children displays.
   */
  function get_children_chart_displays() {
    $children_displays = $this->displayHandler->getAttachedDisplays();
    return $children_displays;
  }
}
