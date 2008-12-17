<?php
/*
 * class_template.inc - View represents a presentation layer for an action.
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * View represents a presentation layer for an action.
 *
 * @author  Marcus Lunzenauer
 * @package    ualogin
 * @subpackage includes
 */

class ViewTemplate {

  /**
   * Internal template data
   */
  var $attributes = array();

  /**
   * Internal template
   */
  var $template = NULL;

  /**
   * Constructor
   */
  function ViewTemplate($template = NULL) {
    // open template if template not null
    if ($template !== NULL) {
      $this->openTemplate($template);
    }
  }

  /**
   * Clear all attributes associated with this view.
   *
   */
  function clearAttributes() {
    $this->attributes = array();
  }

  /**
   * Retrieve an attribute.
   *
   * @param string An attribute name.
   *
   * @return mixed An attribute value, if the attribute exists, otherwise
   *               null.
   */
  function getAttribute($name) {

    if (isset($this->attributes[$name]))
      return $this->attributes[$name];

    return NULL;
  }

  /**
   * Retrieve an array of attribute names.
   *
   * @return array An indexed array of attribute names.
   */
  function getAttributeNames() {
    return array_keys($this->attributes);
  }

  /**
   * Open a Template
   */
  function openTemplate($template) {
    $this->template = tmpl_open(sprintf(drupal_get_path('module', 'ualogin') .'/template/%s.ihtml',
                                        $template));
  }

  /**
   * Remove an attribute.
   *
   * @param string An attribute name.
   *
   * @return mixed An attribute value, if the attribute was removed,
   *               otherwise null.
   */
  function removeAttribute($name) {

    $retval = NULL;

    if (isset($this->attributes[$name])) {
      $retval = $this->attributes[$name];
      unset($this->attributes[$name]);
    }

    return $retval;
  }

  /**
   * Set an attribute.
   *
   * @param string An attribute name.
   * @param mixed  An attribute value.
   *
   */
  function setAttribute($name, $value) {
    $this->attributes[$name] = $value;
  }

  /**
   * Set an array of attributes.
   *
   * @param array An associative array of attributes and their associated
   *              values.
   *
   */
  function setAttributes($attributes) {
    if (is_array($attributes))
      $this->attributes = array_merge($this->attributes, $attributes);
  }

  /**
   * Render the presentation.
   *
   * @return string a stringified representation of the view.
   */
  function toString() {

    if ($this->template) {

      tmpl_set($this->template, '/', $this->attributes);

      return tmpl_parse($this->template);
    }

    return __CLASS__;
  }
}
