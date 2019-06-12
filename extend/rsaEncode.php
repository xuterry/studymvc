<?php
class rsaEncode
{
    protected $m;
    protected $e;
    protected $pw;
    protected $block;
    protected $data=[];
    protected $size=126;
    protected $lowBitMasks = [0x0000, 0x0001, 0x0003, 0x0007, 0x000F, 0x001F,
        0x003F, 0x007F, 0x00FF, 0x01FF, 0x03FF, 0x07FF,
        0x0FFF, 0x1FFF, 0x3FFF, 0x7FFF, 0xFFFF];
    protected $highBitMasks =[0x0000, 0x8000, 0xC000, 0xE000, 0xF000, 0xF800,
        0xFC00, 0xFE00, 0xFF00, 0xFF80, 0xFFC0, 0xFFE0,  0xFFF0, 0xFFF8, 0xFFFC, 0xFFFE, 0xFFFF];
    protected $bitsPerDigit=16;
    protected $maxDigitVal;
    protected $biRadixBase = 2;
    protected $biRadixBits = 16;
    protected $biRadix;
    protected $biHalfRadix;
    protected $biRadixSquared;
    protected $k=64;
    protected $ZERO_ARRAY=[];
    protected $maxDigits;
    protected $mu;
    protected $b2k;
    protected $bkplus1;
    protected $bigOne;
    protected $maxInteger = 9999999999999998;
    public function setM($value)
    {
        $this->m=$value;
    }
    protected function newBig($flag=false)
    {
        if($flag===true)
            $digits=[];
        else
            $digits=$this->ZERO_ARRAY;
        return ['digits'=>$digits,'isNeg'=>false];
    }
    public function setE($value)
    {
        $this->e=$value;
    }
    protected function setZore($value)
    {
        return array_fill(0,$value,0);
    }
    function biHighIndex($val)
    {
        $result= count($val['digits']) - 1;
        while ($result > 0 && $val['digits'][$result]== 0)
            --$result;
        return $result;
    }
   protected function toChar($str)
   {
       $len=strlen($str);
       //$return=$this->newBig();
       for($i=0;$i<$len;$i++){
           $return['digits'][]=ord(substr($str,$i,1));
       }
       while (count($return['digits'])%$this->size != 0) {
           $return['digits'][$i++]=0;
       }
       return $return;
   }
   function setMaxDigits($value)
   {
       $this->maxDigits = $value;
       $this->ZERO_ARRAY =$this->setZore($value);
       for ($iza = 0; $iza < count($this->ZERO_ARRAY); $iza++) $this->ZERO_ARRAY[$iza] = 0;
       $this->bigZero = $this->newBig();
       $this->bigOne = $this->newBig();
       $this->bigOne['digits'][0] = 1;
   }
   function __construct($m,$e,$pw)
   {
       $this->setMaxDigits(150);
       $this->biRadix = 1 << 16;
       $this->biHalfRadix=$this->biRadix>>1;
       $this->maxDigitVal=$this->biRadix-1;
       $this->biRadixSquared=$this->biRadix*$this->biRadix;
       $this->m=$m;
       $this->pw=$pw;
       $this->ZERO_ARRAY=$this->setZore($this->maxDigits);    
       $this->e=$this->splitM($e);       
       $this->pw=$this->toChar($this->pw);
       $this->m=$this->splitM($this->m);
       $this->data=$this->m;
       $this->block=$this->toDigit();
       $this->b2k=$this->newBig();
       $this->b2k['digits'][2*$this->k]=1;
       $this->bkplus1=$this->newBig();
       $this->bkplus1['digits'][$this->k+1]=1;
       
       $this->mu = $this->biDivide($this->b2k, $this->m);
       //dump($this->biHighIndex($this->block));
       $this->m=$this->BarrettMu_powMod($this->block,$this->e);
       $this->m=$this->toDec();
   }
   function getm()
   {
       return $this->m;
   }
   protected function toDigit()
   {
       $j=0;
       $block=$this->newBig();
       for($k=0;$k<$this->size;$j++){
           $block['digits'][$j]=$this->pw['digits'][$k++];
           $block['digits'][$j]+=$this->pw['digits'][$k++] <<8;
           
       }
       return $block;
   }
   protected function toDec()
   {
       $str='';
       $n = $this->biHighIndex($this->m);
       for ($i = $n; $i > -1; --$i) {
           $str.= dechex($this->m['digits'][$i]);
       }
       return $str;
   }
   protected function splitM($value)
   {       
           $result=$this->newBig();
           $len=strlen($value);
           for ($i = $len, $j = 0; $i > 0;$i -= 4, ++$j) {
               $result['digits'][$j] = hexdec(substr($value,max([$i - 4, 0]), min([$i, 4])));
           }
           return $result;     
   }
   function arrayCopy(&$src, $srcStart, &$dest, $destStart, $n)
   {
       $m = min([$srcStart + $n,count($src['digits'])]);
       for ($i = $srcStart,$j = $destStart; $i < $m; ++$i, ++$j) {
           $dest['digits'][$j] = $src['digits'][$i];
       }
   }
   function biShiftLeft($x,$n)
   {
       $digitCount =floor($n / $this->bitsPerDigit);
       $result= $this->newBig();
       $this->arrayCopy($x, 0, $result, $digitCount,
           count($result['digits'])- $digitCount);
       $bits = $n % $this->bitsPerDigit;
       $rightBits = $this->bitsPerDigit - $bits;
       for ($i = count($result['digits'])- 1, $i1 =$i - 1;$i > 0; --$i, --$i1) {
           $result['digits'][$i] = (($result['digits'][$i] << $bits) & $this->maxDigitVal) | (($result['digits'][$i1] & $this->highBitMasks[$bits]) >> ($rightBits));
       }
       $result['digits'][0] = (($result['digits'][$i] << $bits) & $this->maxDigitVal);
       $result['isNeg'] = $x['isNeg'];
       return $result;
   }
   function biMultiply($x, $y)
   {
       $result=$this->newBig();
       $n = $this->biHighIndex($x);
       $t = $this->biHighIndex($y);     
       for ($i = 0; $i <= $t; ++$i) {
           $c = 0;
           $k = $i;
           for ($j = 0; $j <=$n; ++$j, ++$k) {
               $uv = $result['digits'][$k] + $x['digits'][$j] * $y['digits'][$i] + $c;
               $result['digits'][$k] = $uv & $this->maxDigitVal;
               //$c = $uv >>$this->biRadixBits;
               $c = floor($uv / $this->biRadix);
           }
           $result['digits'][$i + $n + 1] = $c;
       }
       // Someone give me a logical xor, please.
       $result['isNeg'] = $x['isNeg'] != $y['isNeg'];
   }
   function BarrettMu_powMod($x, $y)
   {
       $result=$this->newBig();
       $result['digits'][0] = 1;
      // dump($x,$y);
       $a = $x;
       $k= $y;
       $count=0;
       while (true) {
           $count++;
           if (($k['digits'][0] & 1) != 0) $result = $this->BarrettMu_multiplyMod($result, $a);
          $k = $this->biShiftRight($k, 1);
           if ($k['digits'][0] == 0 && $this->biHighIndex($k) == 0) break;
           $a = $this->BarrettMu_multiplyMod($a, $a);
       }
       return $result;
   }
   function BarrettMu_multiplyMod($x, $y)
   {
       /*
        x = this.modulo(x);
        y = this.modulo(y);
        */
       $xy = $this->biMultiply($x, $y);
       return $this->BarrettMu_modulo($xy);
   }
   function BarrettMu_modulo($x)
   {
       $q1 = $this->biDivideByRadixPower($x, $this->k - 1);
       $q2 =$this-> biMultiply($q1, $this->mu);
       $q3 = $this->biDivideByRadixPower($q2, $this->k + 1);
       $r1 = $this->biModuloByRadixPower($x, $this->k + 1);
       $r2term = $this->biMultiply($q3, $this->m);
       $r2 = $this->biModuloByRadixPower($r2term, $this->k + 1);
       $r = $this->biSubtract($r1, $r2);
       if ($r['isNeg']) {
           $r = $this->biAdd($r, $this->bkplus1);
       }
       $rgtem = $this->biCompare($r, $this->m) >= 0;
       while ($rgtem) {
          $r = $this->biSubtract($r,$this->m);
           $rgtem = $this->biCompare($r, $this->m) >= 0;
       }
       return $r;
   }
   function biNumBits($x)
   {
       $n = $this->biHighIndex($x);
       $d = $x['digits'][$n];
       $m = ($n + 1) * $this->bitsPerDigit;
       for ($result= $m; $result > $m - $this->bitsPerDigit; --$result) {
           if (($d & 0x8000) != 0) break;
           $d <<= 1;
       }
       return $result;
   }
   function biDivideModulo($x, $y)
   {
       $nb = $this->biNumBits($x);
       $tb =$this-> biNumBits($y);
       $origYIsNeg =$y['isNeg'];
       if ($nb < $tb) {
           // |x| < |y|
           if ($x['isNeg']) {
               $q =$this->biCopy($this->bigOne);
               $q['isNeg'] = !$y['isNeg'];
               $x['isNeg'] = false;
               $y['isNeg'] = false;
               $r = $this->biSubtract($y, $x);
               // Restore signs, 'cause they're references.
               $x['isNeg'] = true;
               $y['isNeg'] = $origYIsNeg;
           } else {
               $q =$this->newBig();
               $r = $this->biCopy($x);
           }
           return [$q, $r];
       }
       
       $q =$this->newBig();
        $r =$x;
       
       // Normalize Y.
       $t =ceil($tb / $this->bitsPerDigit) - 1;
       $lambda = 0;
       while ($y['digits'][$t] < $this->biHalfRadix) {
           $y = $this->biShiftLeft($y, 1);
           ++$lambda;
           ++$tb;
           $t =ceil($tb / $this->bitsPerDigit) - 1;
       }
       // Shift r over to keep the quotient constant. We'll shift the
       // remainder back at the end.
       $r = $this->biShiftLeft($r, $lambda);
       $nb += $lambda; // Update the bit count for x.
       $n = ceil($nb / $this->bitsPerDigit) - 1;
       
       $b =$this->biMultiplyByRadixPower($y, $n - $t);
       while ($this->biCompare($r, $b) != -1) {
           ++$q['digits'][$n - $t];
          $r = $this->biSubtract($r, $b);
       }
       for ($i = $n;$i > $t; --$i) {
           $ri = ($i >= count($r['digits'])) ? 0 : $r['digits'][$i];
           $ri1 = ($i - 1 >= count($r['digits'])) ? 0 : $r['digits'][$i - 1];
           $ri2 = ($i - 2 >=count($r['digits'])) ? 0 : $r['digits'][$i - 2];
           $yt = ($t >= count($y['digits'])) ? 0 : $y['digits'][$t];
           $yt1 = ($t - 1 >=count($y['digits'])) ? 0 : $y['digits'][$t - 1];
           if ($ri == $yt) {
               $q['digits'][$i - $t - 1] = $this->maxDigitVal;
           } else {
               $q['digits'][$i - $t - 1] = floor(($ri * $this->biRadix + $ri1) / $yt);
           }
           
           $c1 = $q['digits'][$i -$t - 1] * (($yt * $this->biRadix) + $yt1);
           $c2 = ($ri *$this->biRadixSquared) + (($ri1 * $this->biRadix) + $ri2);
           while ($c1 > $c2) {
               --$q['digits'][$i -$t - 1];
               $c1 = $q['digits'][$i - $t - 1] * (($yt * $this->biRadix) |$yt1);
               $c2 = ($ri * $this->biRadix * $this->biRadix) + (($ri1 * $this->biRadix) + $ri2);
           }
           
           $b =$this->biMultiplyByRadixPower($y, $i -$t - 1);
           $r = $this->biSubtract($r, $this->biMultiplyDigit($b, $q['digits'][$i -$t - 1]));
           if ($r['isNeg']) {
               $r = $this->biAdd($r, $b);
               --$q['digits'][$i - $t - 1];
           }
       }
       $r = $this->biShiftRight($r, $lambda);
       // Fiddle with the signs and stuff to make sure that 0 <= r < y.
       $q['isNeg'] = $x['isNeg'] != $origYIsNeg;
       if ($x['isNeg']) {
           if ($origYIsNeg) {
               $q = $this->biAdd($q, $this->bigOne);
           } else {
               $q = $this->biSubtract($q, $this->bigOne);
           }
          $y = $this->biShiftRight($y, $lambda);
           $r = $this->biSubtract($y, $r);
       }
       // Check for the unbelievably stupid degenerate case of r == -0.
       if ($r['digits'][0] == 0 && $this->biHighIndex($r) == 0) $r['isNeg'] = false;     
       return [$q,$r];
   }
   function biDivideByRadixPower($x, $n)
   {
       $result= $this->newBig();
       $this->arrayCopy($x, $n, $result, 0, count($result['digits']) - $n);
       return $result;
   }
   function biModuloByRadixPower($x,$n)
   {
       $result= $this->newBig();
       $this->arrayCopy($x, 0, $result, 0, $n);
       return $result;
   }
   function biSubtract($x,$y)
   {
       if ($x['isNeg'] != $y['isNeg']) {
           $y['isNeg'] = !$y['isNeg'];
           $result= $this->biAdd($x,$y);
           $y['isNeg'] = !$y['isNeg'];
       } else {
           $result=$this->newBig();
           $c = 0;
           for ($i = 0;$i < count($x['digits']); ++$i) {
               $n = $x['digits'][$i] - $y['digits'][$i] + $c;
               $result['digits'][$i] = $n % $this->biRadix;
               // Stupid non-conforming modulus operation.
               if ($result['digits'][$i] < 0) $result['digits'][$i] += $this->biRadix;
               $c = 0 - (int)($n < 0);
           }
           // Fix up the negative sign, if any.
           if ($c == -1) {
               $c = 0;
               for ($i = 0; $i < count($x['digits']); ++$i) {
                  $n = 0 - $result['digits'][$i] + $c;
                  $result['digits'][$i] = $n % $this->biRadix;
                   // Stupid non-conforming modulus operation.
                   if ($result['digits'][$i] < 0) $result['digits'][$i] += $this->biRadix;
                   $c = 0 - (int)($n < 0);
               }
               // Result is opposite sign of arguments.
               $result['isNeg'] = !$x['isNeg'];
           } else {
               // Result is same sign.
               $result['isNeg'] = $x['isNeg'];
           }
       }
       return $result;
   }
   function biAdd($x,$y)
   {       
       if ($x['isNeg'] != $y['isNeg']) {
           $y['isNeg'] = !$y['isNeg'];
           $result=$this->biSubtract($x,$y);
           $y['isNeg'] = !$y['isNeg'];
       }
       else {
           $result=$this->newBig();
           $c = 0;
           for ($i = 0; $i < count($x['digits']); ++$i) {
              $n = $x['digits'][$i] + $y['digits'][$i] + $c;
             $result['digits'][$i] = $n % $this->biRadix;
              $c = (int)($n >= $this->biRadix);
           }
           $result['isNeg'] = $x['isNeg'];
       }
       return $result;
   }
   function biCompare($x,$y)
   {
       if ($x['isNeg'] != $y['isNeg']) {
           return 1 - 2 * (int)($x['isNeg']);
       }
       for ($i = count($x['digits']) - 1;$i >= 0; --$i) {
           if ($x['digits'][$i] != $y['digits'][$i]) {
               if ($x['isNeg']) {
                   return 1 - 2 * (int)($x['digits'][$i] > $y['digits'][$i]);
               } else {
                   return 1 - 2 * (int)($x['digits'][$i] < $y['digits'][$i]);
               }
           }
       }
       return 0;
   }
   
   function biShiftRight($x,$n)
   {      
       $digitCount =floor($n / $this->bitsPerDigit);
       $result =$this->newBig();
       $this->arrayCopy($x,$digitCount, $result, 0,
           count($x['digits'])- $digitCount);
       $bits = $n % $this->bitsPerDigit;
        $leftBits =$this->bitsPerDigit -$bits;
       for ($i = 0, $i1 =$i + 1; $i < count($result['digits']) - 1; ++$i,++$i1) {
           $result['digits'][$i] = ($result['digits'][$i] >> $bits) |
           (($result['digits'][$i1] & $this->lowBitMasks[$bits]) << $leftBits);
       }
       $result['digits'][count($result['digits'])- 1] >>= $bits;
       $result['isNeg'] = $x['isNeg'];
       return $result;     
   }
   function biCopy($bi)
   {
         return $bi;
   }
   function biDivide($x,$y)
   {
       return $this->biDivideModulo($x, $y)[0];
   }
   function biMultiplyByRadixPower($x, $n)
   {
       $result=$this->newBig();
       $this->arrayCopy($x, 0, $result, $n, count($result['digits'])- $n);
       return $result;
   }
   function biMultiplyDigit($x,$y)
   {      
       $result= $this->newBig();
       $n = $this->biHighIndex($x);
       $c = 0;
       for ($j = 0; $j <= $n; ++$j) {
           $uv = $result['digits'][$j] + $x['digits'][$j] * $y + $c;
           $result['digits'][$j] = $uv & $this->maxDigitVal;
           //$c = $uv >>$this->biRadixBits;
           $c = floor($uv / $this->biRadix);
       }
       $result['digits'][1 + $n] = $c;
       return $result;
   }
}