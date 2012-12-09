<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : Conversion method
 * Last Updated: $Date: 2009-02-04 20:03:59 +0000 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		2.1.0
 * @version		$Revision: 3887 $
 *
 */

class auth_sha256
{
	/**
	 * On Bits for sha256 mapping
	 *
	 * @access	private
	 * @var		array
	 */
	private $m_lOnBits	= array(1,3,7,15,31,63,127,255,511,1023,2047,4095,8191,16383,32767,65535,131071,262143,524287,1048575,2097151,4194303,8388607,16777215,33554431,67108863,134217727,268435455,536870911,1073741823,2147483647);
	
	/**
	 * 2nd power sha256 mapping
	 *
	 * @access	private
	 * @var		array
	 */
	private $m_l2Power	= array(1,2,4,8,16,32,64,128,256,512,1024,2048,4096,8192,16384,32768,65536,131072,262144,524288,1048576,2097152,4194304,8388608,16777216,33554432,67108864,134217728,268435456,536870912,1073741824);
	
	/**
	 * Hex mapping sha256 mapping
	 *
	 * @access	private
	 * @var		array
	 */
	private $K			= array(0x428A2F98,0x71374491,0xB5C0FBCF,0xE9B5DBA5,0x3956C25B,0x59F111F1,0x923F82A4,0xAB1C5ED5,0xD807AA98,0x12835B01,0x243185BE,0x550C7DC3,0x72BE5D74,0x80DEB1FE,0x9BDC06A7,0xC19BF174,0xE49B69C1,0xEFBE4786,0xFC19DC6,0x240CA1CC,0x2DE92C6F,0x4A7484AA,0x5CB0A9DC,0x76F988DA,0x983E5152,0xA831C66D,0xB00327C8,0xBF597FC7,0xC6E00BF3,0xD5A79147,0x6CA6351,0x14292967,0x27B70A85,0x2E1B2138,0x4D2C6DFC,0x53380D13,0x650A7354,0x766A0ABB,0x81C2C92E,0x92722C85,0xA2BFE8A1,0xA81A664B,0xC24B8B70,0xC76C51A3,0xD192E819,0xD6990624,0xF40E3585,0x106AA070,0x19A4C116,0x1E376C08,0x2748774C,0x34B0BCB5,0x391C0CB3,0x4ED8AA4A,0x5B9CCA4F,0x682E6FF3,0x748F82EE,0x78A5636F,0x84C87814,0x8CC70208,0x90BEFFFA,0xA4506CEB,0xBEF9A3F7,0xC67178F2);

	/**
	 * Perform SHA256 encoding
	 *
	 * @access	public
	 * @param	string		String to encode
	 * @return	string		Encoded string
	 */
  	public function SHA256($sMessage)
	{
		$HASH	= array( 0x6A09E667, 0xBB67AE85, 0x3C6EF372, 0xA54FF53A, 0x510E527F, 0x9B05688C, 0x1F83D9AB, 0x5BE0CD19);
		$M		= $this->ConvertToWordArray( $sMessage );
	
		for( $i = 0, $ij = count($M); $i < $ij; $i+=16 )
		{
			$a = $HASH[0];
			$b = $HASH[1];
			$c = $HASH[2];
			$d = $HASH[3];
			$e = $HASH[4];
			$f = $HASH[5];
			$g = $HASH[6];
			$h = $HASH[7];
			
			for( $j = 0; $j<64; $j++ )
			{
				if($j < 16) 
				{
					$W[$j] = $M[$j + $i];
				}
				else
				{
					$W[$j] = $this->AddUnsigned($this->AddUnsigned($this->AddUnsigned($this->Gamma1($W[$j - 2]), $W[$j - 7]), $this->Gamma0($W[$j - 15])), $W[$j - 16]);
				}
					
				$T1 = $this->AddUnsigned($this->AddUnsigned($this->AddUnsigned($this->AddUnsigned($h, $this->Sigma1($e)), $this->Ch($e, $f, $g)), $this->K[$j]), $W[$j]);
				$T2 = $this->AddUnsigned($this->Sigma0($a), $this->Maj($a, $b, $c));
				
				$h = $g;
				$g = $f;
				$f = $e;
				$e = $this->AddUnsigned($d, $T1);
				$d = $c;
				$c = $b;
				$b = $a;
				$a = $this->AddUnsigned($T1, $T2);
			}
			
			$HASH[0] = $this->AddUnsigned($a, $HASH[0]);
			$HASH[1] = $this->AddUnsigned($b, $HASH[1]);
			$HASH[2] = $this->AddUnsigned($c, $HASH[2]);
			$HASH[3] = $this->AddUnsigned($d, $HASH[3]);
			$HASH[4] = $this->AddUnsigned($e, $HASH[4]);
			$HASH[5] = $this->AddUnsigned($f, $HASH[5]);
			$HASH[6] = $this->AddUnsigned($g, $HASH[6]);
			$HASH[7] = $this->AddUnsigned($h, $HASH[7]);
		}
		
		for ($i=0; $i < 8; $i++)
		{
			$HASH[$i] = str_repeat("0",8-strlen(dechex($HASH[$i]))) . strtolower(dechex($HASH[$i]));
		}

		return $HASH[0].$HASH[1].$HASH[2].$HASH[3].$HASH[4].$HASH[5].$HASH[6].$HASH[7];
	}
	
	/**
	 * Left shift a value x bits
	 *
	 * @access	private
	 * @param	string		String to shift
	 * @param	integer		Number of bits to shift
	 * @return	string		Shifted string
	 */
	private function LShift($lValue, $iShiftBits) 
	{
		if ($iShiftBits == 0) 
		{
			return $lValue;
		}
		elseif ($iShiftBits == 31) 
		{
			if ($lValue & 1) 
			{
				return 0x80000000;
			}
			else
			{
				return 0;
			}
		}
		elseif ($iShiftBits < 0 Or $iShiftBits > 31) 
		{
			exit();
		}
		
		if ($lValue & $this->m_l2Power[31 - $iShiftBits]) 
		{
			return (($lValue & $this->m_lOnBits[31 - ($iShiftBits + 1)]) * $this->m_l2Power[$iShiftBits]) | 0x80000000;
		}
		else 
		{
			return (($lValue & $this->m_lOnBits[31 - $iShiftBits]) * $this->m_l2Power[$iShiftBits]);
		}
	}

	/**
	 * Right shift a value x bits
	 *
	 * @access	private
	 * @param	string		String to shift
	 * @param	integer		Number of bits to shift
	 * @return	string		Shifted string
	 */
	private function RShift($lValue, $iShiftBits)
	{
		if ($iShiftBits == 0) 
		{
			return $lValue;
		}
		elseif ($iShiftBits == 31) 
		{
			if ($lValue & 0x80000000) 
			{
				$RShift = 1;
			}
			else 
			{
				$RShift = 0;
			}
		}
		elseif ($iShiftBits < 0 Or $iShiftBits > 31) 
		{
			exit();
		}
		
		$RShift = floor(($lValue & 0x7FFFFFFE) / $this->m_l2Power[$iShiftBits]);
		
		if ($lValue & 0x80000000) 
		{
			$RShift = ($RShift | floor(0x40000000 / $this->m_l2Power[$iShiftBits - 1]));
		}

		return $RShift;
	}
	
	/**
	 * Add unsigned
	 *
	 * @access	private
	 * @param	integer		Number
	 * @param	integer		Number
	 * @return	string		Added unsigned integer
	 */
	private function AddUnsigned($lX, $lY)
	{
		$lX8 = $lX & 0x80000000;
		$lY8 = $lY & 0x80000000;
		$lX4 = $lX & 0x40000000;
		$lY4 = $lY & 0x40000000;
	 
		$lResult = ($lX & 0x3FFFFFFF) + ($lY & 0x3FFFFFFF);
	 
		if ($lX4 & $lY4) 
 		{
			$lResult = $lResult ^ 0x80000000 ^ $lX8 ^ $lY8;
		}
		elseif ($lX4 | $lY4) 
		{
			if ($lResult & 0x40000000) 
			{
				$lResult = $lResult ^ 0xC0000000 ^ $lX8 ^ $lY8;
			}
			else 
			{
				$lResult = $lResult ^ 0x40000000 ^ $lX8 ^ $lY8;
			}
		}
		else 
		{
			$lResult = $lResult ^ $lX8 ^ $lY8;
		}
		
		return $lResult;
	}
	
	/**
	 * Ch
	 *
	 * @access	private
	 * @param	integer		$x
	 * @param	integer		$y
	 * @param	integer		$z
	 * @return	mixed		No idea...
	 */
	private function Ch($x, $y, $z)
	{
		return (($x & $y) ^ ((~ $x) & $z));
	}
	
	/**
	 * Maj
	 *
	 * @access	private
	 * @param	integer		$x
	 * @param	integer		$y
	 * @param	integer		$z
	 * @return	mixed		No idea...
	 */
	private function Maj($x, $y, $z)
	{
		return (($x & $y) ^ ($x & $z) ^ ($y & $z));
	}
	
	/**
	 * S
	 *
	 * @access	private
	 * @param	integer		$x
	 * @param	integer		$n
	 * @return	mixed		No idea...
	 */
	private function S($x, $n)
	{
		return ($this->RShift($x , ($n & $this->m_lOnBits[4])) | $this->LShift($x , (32 - ($n & $this->m_lOnBits[4]))));
	}
	
	/**
	 * R
	 *
	 * @access	private
	 * @param	integer		$x
	 * @param	integer		$n
	 * @return	mixed		No idea...
	 */
	private function R($x, $n)
	{
		return $this->RShift($x , ($n & $this->m_lOnBits[4]));
	}
	
	/**
	 * Sigma0
	 *
	 * @access	private
	 * @param	integer		$x
	 * @return	mixed		No idea...
	 */
	private function Sigma0($x)
	{
		return ($this->S($x, 2) ^ $this->S($x, 13) ^ $this->S($x, 22));
	}
	
	/**
	 * Sigma1
	 *
	 * @access	private
	 * @param	integer		$x
	 * @return	mixed		No idea...
	 */
	private function Sigma1($x)
	{
		return ($this->S($x, 6) ^ $this->S($x, 11) ^ $this->S($x, 25));
	}
	
	/**
	 * Gamma0
	 *
	 * @access	private
	 * @param	integer		$x
	 * @return	mixed		No idea...
	 */
	private function Gamma0($x)
	{
		return ($this->S($x, 7) ^ $this->S($x, 18) ^ $this->R($x, 3));
	}
	
	/**
	 * Gamma1
	 *
	 * @access	private
	 * @param	integer		$x
	 * @return	mixed		No idea...
	 */
	private function Gamma1($x)
	{
		return ($this->S($x, 17) ^ $this->S($x, 19) ^ $this->R($x, 10));
	}
	
	/**
	 * Convert to a word array
	 *
	 * @access	private
	 * @param	string		Word to convert
	 * @return	array		Word array
	 */
	private function ConvertToWordArray($sMessage)
	{
		$BITS_TO_A_BYTE = 8;
		$BYTES_TO_A_WORD = 4;
		$BITS_TO_A_WORD = 32;
		$MODULUS_BITS = 512;
		$CONGRUENT_BITS = 448;
		
		$lMessageLength = strlen($sMessage);
		
		$lNumberOfWords = (floor(($lMessageLength + floor(($MODULUS_BITS - $CONGRUENT_BITS) / $BITS_TO_A_BYTE)) / floor($MODULUS_BITS / $BITS_TO_A_BYTE)) + 1) * floor($MODULUS_BITS / $BITS_TO_A_WORD);
		for($i = 0; $i < $lNumberOfWords; $i++)
		{
			$lWordArray[$i]="";
		}
		
		$lBytePosition = 0;
		$lByteCount = 0;
		do
		{
			$lWordCount = floor($lByteCount / $BYTES_TO_A_WORD);
			
			$lBytePosition = (3 - ($lByteCount % $BYTES_TO_A_WORD)) * $BITS_TO_A_BYTE;
			
			$lByte = ord(substr($sMessage, $lByteCount, 1));
			
			$lWordArray[$lWordCount] = $lWordArray[$lWordCount] | $this->LShift($lByte , $lBytePosition);
			$lByteCount++;
		}
		while ($lByteCount < $lMessageLength);
	
		$lWordCount = floor($lByteCount / $BYTES_TO_A_WORD);
		$lBytePosition = (3 - ($lByteCount % $BYTES_TO_A_WORD)) * $BITS_TO_A_BYTE;
	
		$lWordArray[$lWordCount] = $lWordArray[$lWordCount] | $this->LShift(0x80 , $lBytePosition);
	
		$lWordArray[$lNumberOfWords - 1] = $this->LShift($lMessageLength , 3);
		$lWordArray[$lNumberOfWords - 2] = $this->RShift($lMessageLength , 29);
		
		return $lWordArray;
	}
}