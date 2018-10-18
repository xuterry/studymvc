<?php
namespace http;
class macd
{
         static  function build_diff_data($m_slow, $m_long, $data) {
             $result = [];
             $pre_emashort = 0;
             $pre_emalong = 0;
			 $ema_short=$ema_long=0;
             for ($i = 0, $len = sizeof($data); $i < $len; $i++) {
                if($i!=0)
				{
                    $ema_short = (2 / ($m_slow+1)) * $data[$i] + (1 - 2/ ($m_slow+1)) * $pre_emashort;
				
                    $ema_long = (2 / ($m_long+1)) * $data[$i] + (1 - 2 / ( $m_long+1)) * $pre_emalong;
				}
                $pre_emashort = $ema_short;
                $pre_emalong = $ema_long;	
				if($i==0)
				$pre_emashort=$pre_emalong=$data[0];
                $diff = $ema_short - $ema_long;
                $result[]=$diff;
            }
            return $result;
           }
           static  function build_dea_data ($m, $diff) {
            $result = [];
            $pre_ema_diff = 0;
			$ema_diff=0;
            for ($i = 0, $len = sizeof($diff); $i < $len; $i++) {
               // $ema_diff = $diff[$i];
                if($i!=0)
                $ema_diff = (2 / ($m+1)) * $diff[$i] + (1 - 2/ ($m+1)) * $pre_ema_diff;
                $pre_ema_diff = $ema_diff;
				if($i==0)
				{			
					$pre_ema_diff=$diff[0];
				}
				$result[]=$ema_diff;
				/*
				if($i<$m)
				$ema_diff=$ema_diff+$diff[$i];
				else
					$ema_diff=$diff[$i]+$ema_diff-$diff[$i-$m];
				$result[]=$ema_diff/$m;
				*/
            }
            return $result;
          }
        static  function build_macd_data ($data, $diff, $dea) {
           
            for ($i = 0, $len = sizeof($data); $i < $len; $i++) {
                $macd = 2 * ($diff[$i] - $dea[$i]);
                $result[]=round($macd,11);
			}
            return $result;

            }    
	      static function get($data,$slow=12,$long=26,$dif=9)
	        {
		    $diff=self::build_diff_data($slow,$long,$data);
	    	$dea=self::build_dea_data($dif,$diff);
		    $getdata=self::build_macd_data ($data, $diff, $dea);
		    return $getdata;
	         }
	   
}