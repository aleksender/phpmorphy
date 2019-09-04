<?php
/*
* This file is part of phpMorphy project
*
* Copyright (c) 2007-2012 Kamaev Vladimir <heromantor@users.sourceforge.net>
*
*     This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2 of the License, or (at your option) any later version.
*
*     This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
*     You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the
* Free Software Foundation, Inc., 59 Temple Place - Suite 330,
* Boston, MA 02111-1307, USA.
*/

class phpMorphy_Fsa_Sparse_Mem extends phpMorphy_Fsa_FsaAbstract {
    function walk($trans, $word, $readAnnot = true) {
        $__mem = $this->resource; $fsa_start = $this->fsa_start;

        for($i = 0, $c = strlen($word); $i < $c; $i++) {
            $prev_trans = $trans;
            $char = ord($word[$i]);

            /////////////////////////////////
            // find char in state begin
			// sparse version
			$result = true;
						list(, $trans) = unpack('V', substr($__mem, $fsa_start + (((($trans >> 10) & 0x3FFFFF) + $char + 1) << 2), 4));

			if(($trans & 0x0200) || ($trans & 0xFF) != $char) {
				$result = false;
			}
            // find char in state end
            /////////////////////////////////

            if(!$result) {
                $trans = $prev_trans;
                break;
            }
        }

        $annot = null;
        $result = false;
        $prev_trans = $trans;

        if($i >= $c) {
            // Read annotation when we walked all chars in word
            $result = true;

            if($readAnnot) {
                // read annot trans
                                list(, $trans) = unpack('V', substr($__mem, $fsa_start + ((($trans >> 10) & 0x3FFFFF) << 2), 4));

                if(0 == ($trans & 0x0100)) {
                    $result = false;
                } else {
                    $annot = $this->getAnnot($trans);
                }
            }
        }

        return array(
            'result' => $result,
            'last_trans' => $trans,
            'word_trans' => $prev_trans,
            'walked' => $i,
            'annot' => $annot
        );
    }

    function collect($startNode, $callback, $readAnnot = true, $path = '') {
        $total = 0;

        $stack = array();
        $stack_idx = array();
        $start_idx = 0;
        array_push($stack, null);
        array_push($stack_idx, null);

        $state = $this->readState((($startNode) >> 10) & 0x3FFFFF);

        do {
            for($i = $start_idx, $c = count($state); $i < $c; $i++) {
                $trans = $state[$i];

                if(($trans & 0x0100)) {
                    $total++;

                    if($readAnnot) {
                        $annot = $this->getAnnot($trans);
                    } else {
                        $annot = $trans;
                    }

                    if(!call_user_func($callback, $path, $annot)) {
                        return $total;
                    }
                } else {
                    $path .= chr(($trans & 0xFF));
                    array_push($stack, $state);
                    array_push($stack_idx, $i + 1);
                    $state = $this->readState((($trans) >> 10) & 0x3FFFFF);
                    $start_idx = 0;

                    break;
                }
            }

            if($i >= $c) {
                $state = array_pop($stack);
                $start_idx = array_pop($stack_idx);
                $path = substr($path, 0, -1);
            }
        } while(!empty($stack));

        return $total;
    }

    function readState($index) {
        $__mem = $this->resource; $fsa_start = $this->fsa_start;

        $result = array();

        $start_offset = $fsa_start + (($index) << 2);

        // first try read annot transition
                list(, $trans) = unpack('V', substr($__mem, $start_offset, 4));

        if(($trans & 0x0100)) {
            $result[] = $trans;
        }

        // read rest
        $start_offset += 4;
        foreach($this->getAlphabetNum() as $char) {
                        list(, $trans) = unpack('V', substr($__mem, $start_offset + (($char) << 2), 4));

//            if(!($trans & 0x0200) && ($trans & 0xFF) == $char) {
// TODO: check term and empty flags at once i.e. $trans & 0x0300
            if(!(($trans & 0x0200) || ($trans & 0x0100)) && ($trans & 0xFF) == $char) {

                $result[] = $trans;
            }
        }

        return $result;
    }

    function unpackTranses($rawTranses) {
        settype($rawTranses, 'array');
        $result = array();

        foreach($rawTranses as $rawTrans) {
            $result[] = array(
				'term'  => ($rawTrans & 0x0100) ? true : false,
				'empty' => ($rawTrans & 0x0200) ? true : false,
				'attr'  => ($rawTrans & 0xFF),
				'dest'  => (($rawTrans) >> 10) & 0x3FFFFF,
			);
        }

        return $result;
    }

    protected function readRootTrans() {
        $__mem = $this->resource; $fsa_start = $this->fsa_start;

                list(, $trans) = unpack('V', substr($__mem, $fsa_start + 4, 4));

        return $trans;
    }

    protected function readAlphabet() {
        $__mem = $this->resource; $fsa_start = $this->fsa_start;

                return substr($__mem, $this->header['alphabet_offset'], $this->header['alphabet_size']);
    }

    function getAnnot($trans) {
        if(!($trans & 0x0100)) {
            return null;
        }

        $__mem = $this->resource; $fsa_start = $this->fsa_start;

        $offset =
            $this->header['annot_offset'] +
            ((($trans & 0xFF) << 22) | (($trans >> 10) & 0x3FFFFF));

                $len = ord(substr($__mem, $offset, 1));

        if($len) {
            $annot = substr($__mem, $offset + 1, $len);
        } else {
            $annot = null;
        }

        return $annot;
    }

	function getAlphabetNum() {
		if(!isset($this->alphabet_num)) {
			$this->alphabet_num = array_map('ord', $this->getAlphabet());
		}

		return $this->alphabet_num;
	}
	protected $alphabet_num;}