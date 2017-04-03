<?php

namespace Drupal\charts_google\Charts;

use Drupal\charts\Charts\ChartsRenderInterface;
use Drupal\charts\Util\Util;
use Drupal\charts_google\Settings\Google\GoogleOptions;
use Drupal\charts_google\Settings\Google\ChartType;
use Drupal\charts_google\Settings\Google\ChartArea;

class GoogleChartsRender implements ChartsRenderInterface {

  private $googleData;
  private $googleOptions;
  private $googleChartType;
  private $chartId;

  /*public function __construct($categories, $seriesData, $options, $attachmentDisplayOptions, &$variables, $chartId) {
    Util::checkMissingLibrary('charts_google', '/vendor/google/loader.js');
    $this->chartId = $chartId;
    $this->googleData = $this->charts_google_render_charts($categories, $seriesData);
    $this->googleOptions = $this->charts_google_create_charts_options($options, $seriesData, $attachmentDisplayOptions);
    $this->googleChartType = $this->charts_google_create_chart_type($options);
    $variables['chart_type'] = 'google';
    $variables['attributes']['class'][0] = 'charts-google';
    $variables['attributes']['id'][0] = $this->chartId;
    $variables['content_attributes']['data-chart'][] = $this->googleData;
    $variables['attributes']['google-options'][1] = json_encode($this->googleOptions);
    $variables['attributes']['google-chart-type'][2] = json_encode($this->googleChartType);
  }*/

  /**
   * Creates a JSON Object formatted for Google charts to use
   * @param array $categories
   * @param array $seriesData
   *
   * @return json|string
   */
  //$options, $categories = [], $seriesData = [], $attachmentDisplayOptions = [], &$variables, $chartId
  public function charts_render_charts($options, $categories = [], $seriesData = [], $attachmentDisplayOptions = [], &$variables, $chartId) {

    $variables['naam'] = 'Michael Mwebaze';
    $google = array();
    $dataTable = [];
    for ($j = 0; $j < count($categories); $j++) {
      $rowDataTable = [];
      for ($i = 0; $i < count($seriesData); $i++) {
        $rowDataTabletemp = $seriesData[$i]['data'][$j];
        array_push($rowDataTable, $rowDataTabletemp);
      }
      array_unshift($rowDataTable, $categories[$j]);
      array_push($dataTable, $rowDataTable);
    }

    $dataTableHeader = [];
    for ($r = 0; $r < count($seriesData); $r++) {
      array_push($dataTableHeader, $seriesData[$r]['name']);
    }

    array_unshift($dataTableHeader, 'label');
    array_unshift($dataTable, $dataTableHeader);

    $variables['chart_type'] = 'google';
    $variables['attributes']['class'][0] = 'charts-google';
    $variables['attributes']['id'][0] = $chartId;
    $variables['content_attributes']['data-chart'][] = json_encode($dataTable);
    $variables['attributes']['google-options'][1] = json_encode($this->charts_google_create_charts_options($options, $seriesData, $attachmentDisplayOptions));
    $variables['attributes']['google-chart-type'][2] = json_encode($this->charts_google_create_chart_type($options));
drupal_set_message(json_encode($dataTable));
    //array_push($google, $dataTable);
    //return json_encode($dataTable);
  }

  /**
   * @param $options
   * @param array $seriesData
   * @param array $attachmentDisplayOptions
   * @return GoogleOptions object with chart options or settings to be used by google visualization framework
   */
  private function charts_google_create_charts_options($options, $seriesData = [], $attachmentDisplayOptions = []) {
    $chartSelected = [];
    $seriesTypes = [];
    $firstVaxis = ['minValue' => 0, 'title' => $options['yaxis_title']];
    $secondVaxis = ['minValue' => 0];
    $vAxes = [];
    array_push($vAxes, $firstVaxis);
    //sets secondary axis from the first attachment only
    if ($attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $secondVaxis['title'] = $attachmentDisplayOptions[0]['style']['options']['yaxis_title'];
      array_push($vAxes, $secondVaxis);
    }
    array_push($chartSelected, $options['type']);
    for ($i = 0; $i < count($attachmentDisplayOptions); $i++) {
      $attachmentChartType = $attachmentDisplayOptions[$i]['style']['options']['type'];
      if ($attachmentChartType == 'column')
        $attachmentChartType = 'bars';
      if ($attachmentDisplayOptions[0]['inherit_yaxis'] == 0 && $i == 0) {
        $seriesTypes[$i + 1] = ['type' => $attachmentChartType, 'targetAxisIndex' => 1];
      } else
        $seriesTypes[$i + 1] = ['type' => $attachmentChartType];
      array_push($chartSelected, $attachmentChartType);
    }

    $chartSelected = array_unique($chartSelected);
    $googleOptions = new GoogleOptions();
    if (count($chartSelected) > 1) {
      $parentChartType = $options['type'];
      if ($parentChartType == 'column')
        $parentChartType = 'bars';
      $googleOptions->seriesType = $parentChartType;
      $googleOptions->series = $seriesTypes;
    }
    $googleOptions->setTitle($options['title']);
    $googleOptions->vAxes = $vAxes;
    //$vAxis['title'] = $options['yaxis_title'];
    //$googleOptions->setVAxis($vAxis);
    $chartArea = new ChartArea();
    $chartArea->setWidth(400);
    // $googleOptions->setChartArea($chartArea);
    $seriesColors = [];
    for ($i = 0; $i < count($seriesData); $i++) {
      $seriesColor = $seriesData[$i]['color'];
      array_push($seriesColors, $seriesColor);
    }
    $googleOptions->setColors($seriesColors);

    return $googleOptions;
  }

  /**
   * @param $options
   * @return ChartType
   */
  private function charts_google_create_chart_type($options) {

    $googleChartType = new ChartType();
    $googleChartType->setChartType($options['type']);

    return $googleChartType;
  }
  public function charts_library_check($moduleName, $libraryPath){
    //Util::checkMissingLibrary('charts_google', '/vendor/google/loader.js');
    Util::checkMissingLibrary($moduleName, $libraryPath);
  }
}