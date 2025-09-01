<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

namespace itnovum\openITCOCKPIT\ConfigGenerator;


class ConfigSymlink {

    /**
     * @var string
     */
    private $realOutfile;

    /**
     * @var string
     */
    private $linkedOutfile;

    /**
     * @var bool
     */
    private $requireNewSymlink;

    /**
     * ConfigSymlink constructor.
     * @param string $realOutfile
     * @param string $linkedOutfile
     */
    public function __construct($realOutfile, $linkedOutfile) {
        $this->realOutfile = $realOutfile;
        $this->linkedOutfile = $linkedOutfile;

        $requireNewSymlink = false;
        if ($this->realOutfile !== $this->linkedOutfile) {
            //Checking symlink states
            if (file_exists($this->linkedOutfile)) {
                if (is_file($this->linkedOutfile)) {
                    //This should be a symlink - legacy?
                    unlink($this->linkedOutfile);
                    $requireNewSymlink = true;
                }

                if (is_link($this->linkedOutfile)) {
                    //Generated config or default config?
                    $symlinkTarget = readlink($this->linkedOutfile);
                    if (stripos($symlinkTarget, '/var/lib/openitcockpit/etc/generated') === false) {
                        //This is the default config
                        unlink($this->linkedOutfile);
                        $requireNewSymlink = true;
                    }
                }
            }

            if (!file_exists($this->linkedOutfile)) {
                //Symlink does not exists at all!
                //We need to create it
                $requireNewSymlink = true;
            }
        }

        $this->requireNewSymlink = $requireNewSymlink;
    }

    public function link() {
        if ($this->requireNewSymlink && $this->realOutfile !== $this->linkedOutfile) {
            if (file_exists($this->realOutfile)) {
                if (is_dir(dirname($this->linkedOutfile))) {
                    symlink($this->realOutfile, $this->linkedOutfile);
                }
            }
        }
    }

}
