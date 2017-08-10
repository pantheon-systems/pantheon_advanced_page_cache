<?php
declare(strict_types=1);


namespace PantheonSystems\CDNBehatHelpers;
use InvalidArgumentException;

final class agetracker
{



    public function trackHeaders($path, $headers) {
      $this->headers[$path][] = $headers;
   }
   public function getTrackedHeaders($path) {
        return $this->headers[$path];
   }

   public function wasCacheClearedBetweenLastTwoRequests($path) {
        return TRUE;
   }

}