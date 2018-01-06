<?php

namespace Drupal\visualn;

interface ResourceInterface {

  public function getResourceType();

  public function setResourceType($resource_type);



  public function getResourceParams();

  public function setResourceParams(array $resource_params);

}
