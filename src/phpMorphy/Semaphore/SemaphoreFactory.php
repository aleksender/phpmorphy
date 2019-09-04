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

abstract class phpMorphy_Semaphore_SemaphoreFactory {
    /**
     * @static
     * @param string $key
     * @param bool $createEmpty
     * @return phpMorphy_Semaphore_SemaphoreInterface
     */
    static function create($key, $createEmpty = false) {
        if(!$createEmpty) {
            if (0 == strcasecmp(substr(PHP_OS, 0, 3), 'WIN')) {
                return new phpMorphy_Semaphore_Win($key);
            } else {
                return new phpMorphy_Semaphore_Nix($key);
            }
        } else {
            return new phpMorphy_Semaphore_Empty($key);
        }
    }
};