<?php

namespace Drupal\visualn;

class Resource implements ResourceInterface {

  protected $resource_type;

  protected $resource_params = [];


  public function getResourceType() {
    return $this->resource_type;
  }

  public function setResourceType($resource_type) {
    $this->resource_type = $resource_type;

    return $resource_type;
  }



  public function getResourceParams() {
    return $this->resource_params;
  }

  public function setResourceParams(array $resource_params) {
    $this->resource_params = $resource_params;

    return $resource_params;
  }

}
