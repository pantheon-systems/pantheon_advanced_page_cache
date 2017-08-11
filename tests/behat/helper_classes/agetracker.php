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

         // Assign the headers to a new variable so that $this->headers is not modified by array_pop().
         $headers = $this->headers[$path];

       print_r($headers);

         $most_recent = array_pop($headers);



         $second_most_recent = array_pop($headers);
         // If the Age header on the most recent request is smaller than the age header on the second most recent
         // Then the cache was cleared (@todo, or it expired)
         // @todo, account for max age.
         return $most_recent['Age'][0] < $second_most_recent['Age'][0];


   }

}