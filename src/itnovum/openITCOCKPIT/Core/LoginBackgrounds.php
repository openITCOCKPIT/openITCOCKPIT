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

namespace itnovum\openITCOCKPIT\Core;


use itnovum\openITCOCKPIT\Core\Views\Logo;

class LoginBackgrounds {

    /**
     * @var array
     */
    private $images = [];

    public function __construct() {
        $this->images = [
            'default' => [
                'description' => '',
                'particles'   => 'default',
                'images'      => [
                    [
                        'image'  => 'spacex-71870.jpg',
                        'credit' => 'Photo by SpaceX on Unsplash'
                    ],
                    [
                        'image'  => 'nasa-53884.jpg',
                        'credit' => 'Photo by NASA on Unsplash'
                    ]
                ]
            ],

            'winter' => [
                'description' => '',
                'particles'   => 'snow',
                'images'      => [
                    [
                        'image'  => 'todd-diemer-67t2GJcD5PI-unsplash.jpg',
                        'credit' => 'Photo by Todd Diemer on Unsplash'
                    ],
                    [
                        'image'  => 'aaron-burden-5AiWn2U10cw-unsplash.jpg',
                        'credit' => 'Photo by Aaron Burden on Unsplash'
                    ]
                ]
            ],

            'spring' => [
                'description' => '',
                'particles'   => 'default',
                'images'      => [
                    [
                        'image'  => 'anders-jilden-O85h02qZ24w-unsplash.jpg',
                        'credit' => 'Photo by Anders Jildén on Unsplash'
                    ],
                    [
                        'image'  => 'markus-spiske--UwA6gLNAH0-unsplash.jpg',
                        'credit' => 'anders-jilden-O85h02qZ24w-unsplash'
                    ]
                ]
            ],

            'summer' => [
                'description' => '',
                'particles'   => 'default',
                'images'      => [
                    [
                        'image'  => 'sean-o-KMn4VEeEPR8-unsplash.jpg',
                        'credit' => 'Photo by Sean O. on Unsplash'
                    ],
                    [
                        'image'  => 'nattu-adnan-atSUvc1hMwk-unsplash.jpg',
                        'credit' => 'Photo by Nattu Adnan on Unsplash'
                    ]
                ]
            ],

            'fall' => [
                'description' => '',
                'particles'   => 'stars',
                'images'      => [
                    [
                        'image'  => 'dawid-zawila-r2GUfVbFroM-unsplash.jpg',
                        'credit' => 'Photo by Dawid Zawiła on Unsplash'
                    ],
                    [
                        'image'  => 'johannes-plenio-RwHv7LgeC7s-unsplash.jpg',
                        'credit' => 'Photo by Johannes Plenio on Unsplash'
                    ]
                ]
            ],

            'easter' => [
                'description' => __('Happy Easter'),
                'particles'   => 'stars',
                'images'      => [
                    [
                        'image'  => 'daniel-watson-ETXsxLOz_NY-unsplash.jpg',
                        'credit' => 'Photo by Daniel Watson on Unsplash'
                    ],
                    [
                        'image'  => 'easter-2197043.jpg',
                        'credit' => 'Photo by Rebekka D on Pixabay'
                    ]
                ]
            ],

            'valentines_day' => [
                'description' => __('Valentine\'s Day'),
                'particles'   => 'stars',
                'images'      => [
                    [
                        'image'  => 'christopher-beloch-P2fBIamIbQk-unsplash.jpg',
                        'credit' => 'Photo by Christopher Beloch on Unsplash'
                    ],
                    [
                        'image'  => 'heart-shape-1714807.jpg',
                        'credit' => 'Photo by skeeze on Pixabay'
                    ]
                ]
            ],

            'german_unity' => [
                'description' => __('German Unity Day'),
                'particles'   => 'stars',
                'images'      => [
                    [
                        'image'  => 'luis-diego-hernandez-zD_MlPGAWUQ-unsplash.jpg',
                        'credit' => 'Photo by Luis Diego Hernández on Unsplash'
                    ],
                    [
                        'image'  => 'hakon-sataoen-Oog0wehKxYs-unsplash.jpg',
                        'credit' => 'Photo by Håkon Sataøen on Unsplash'
                    ]
                ]
            ],

            'sysadmin_day' => [
                'description' => __('System Administrator Appreciation Day'),
                'particles'   => 'default',
                'images'      => [
                    [
                        'image'  => 'technology-1587673.jpg',
                        'credit' => 'Photo by Edgar Oliver on Pixabay'
                    ],
                    [
                        'image'  => 'server-1235959.jpg',
                        'credit' => 'Photo by Colossus Cloud on Pixabay'
                    ]
                ]
            ],

            'beer_day' => [
                'description' => __('International Beer Day'),
                'particles'   => 'stars',
                'images'      => [
                    [
                        'image'  => 'beer-3445988.jpg',
                        'credit' => 'Photo by rawpixel on Pixabay'
                    ],
                    [
                        'image'  => 'beer-2439237.jpg',
                        'credit' => 'Photo by Alexas_Fotos on Pixabay'
                    ]
                ]
            ],

            'halloween' => [
                'description' => __('Happy Halloween'),
                'particles'   => 'default',
                'images'      => [
                    [
                        'image'  => 'grayson-savio-QLtHhwOnuuI-unsplash.jpg',
                        'credit' => 'Photo by Grayson Savio on Unsplash'
                    ],
                    [
                        'image'  => 'neonbrand-ASNSoeead70-unsplash.jpg',
                        'credit' => 'Photo by NeONBRAND on Unsplash'
                    ]
                ]
            ],

            'christmas' => [
                'description' => __('Merry Christmas'),
                'particles'   => 'snow',
                'images'      => [
                    [
                        'image'  => 'christmas-tree-1149619.jpg',
                        'credit' => 'Photo by Free-Photos on Pixabay'
                    ],
                    [
                        'image'  => 'mister-james-rxqqVkGx870-unsplash.jpg',
                        'credit' => 'Photo by Mister James on Unsplash'
                    ]
                ]
            ],
            'cat_day' => [
                'description' => __('International Cat Day'),
                'particles'   => 'default',
                'images'      => [
                    [
                        'image'  => 'Luna.jpg',
                        'credit' => 'Luna'
                    ],
                    [
                        'image'  => 'Mia.jpg',
                        'credit' => 'Mia'
                    ]
                ]
            ],
            'chocolate_day' => [
                'description' => __('International Chocolate Day'),
                'particles'   => 'default',
                'images'      => [
                    [
                        'image'  => 'amirali-mirhashemian-RCVIlSXhYI0-unsplash.jpg',
                        'credit' => 'Photo by amirali mirhashemian on Unsplash'
                    ],
                    [
                        'image'  => 'malik-skydsgaard-dm1gBKem7XA-unsplash.jpg',
                        'credit' => 'Photo by Malik Skydsgaard on Unsplash'
                    ]
                ]
            ],
            'women_day' => [
                'description' => __('International Women\'s Day'),
                'particles'   => 'stars',
                'images'      => [
                    [
                        'image'  => 'eyestetix-studio-9SNSHSTpBmk-unsplash.jpg',
                        'credit' => 'Photo by Eyestetix Studio on Unsplash'
                    ],
                    [
                        'image'  => 'christina-wocintechchat-com-b6dfPNHa81w-unsplash.jpg',
                        'credit' => 'Photo by Christina @ wocintechchat.com on Unsplash'
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getAllImages() {
        return $this->images;
    }

    /**
     * @return array
     */
    public function getCategories() {
        return array_keys($this->images);
    }

    /**
     * @param $category
     * @return array
     */
    public function getCategory($category) {
        if (isset($this->images[$category])) {
            return $this->images[$category];
        }

        return $this->images['default'];
    }

    /**
     * @return array
     */
    public function getRandomCategory() {
        $categories = $this->getCategories();
        $size = sizeof($categories) - 1;

        $category = $categories[rand(0, $size)];

        return $this->getCategory($category);
    }

    /**
     * @return array
     */
    public function getImages() {
        $season = $this->getSeason();
        return $this->getCategory($season);
    }

    /**
     * @return string
     */
    public function getSeason() {
        $season = 'winter';

        $month = date('n');
        if ($month >= 3) {
            $season = 'spring'; //March (3)
        }

        if ($month >= 6) {
            $season = 'summer'; //June (6)
        }

        if ($month >= 9) {
            $season = 'fall'; //September (9)
        }

        if ($month >= 12) {
            $season = 'winter'; //December (12)
        }

        $today = date('d.m');

        // Easter sunday
        $easter_sunday = easter_date(date('Y'));

        //Good Friday
        if ($today === strtotime('last friday', $easter_sunday)) {
            $season = 'easter';
        }

        //Easter Saturday
        if ($today === strtotime('last saturday', $easter_sunday)) {
            $season = 'easter';
        }

        //Easter Sunday
        if ($today === date('d.m', $easter_sunday)) {
            $season = 'easter';
        }

        //Easter Monday
        if ($today === strtotime('next monday', $easter_sunday)) {
            $season = 'easter';
        }


        //Valentine's Day - 14. Feb
        if ($today === '14.02') {
            $season = 'valentines_day';
        }

        // International Women's Day - 08. Mar
        if ($today === '08.03') {
            $season = 'women_day';
        }

        // Sysadmin day - Last friday of July(7)
        if ($today === date('d.m', strtotime('last friday of july'))) {
            $season = 'sysadmin_day';
        }

        // International Beer Day - First friday of August(8)
        if ($today === date('d.m', strtotime('first friday of august'))) {
            $season = 'beer_day';
        }

        // International Cat Day - 08. Aug
        if ($today === '08.08') {
            $season = 'cat_day';
        }

        // International Chocolate Day - 13. Sep
        if ($today === '13.09') {
            $season = 'chocolate_day';
        }

        //German Unity Day - 3. Oct
        if ($today === '03.10') {
            $season = 'german_unity';
        }

        // Halloween - 31. Oct
        if ($today === '31.10') {
            $season = 'halloween';
        }

        // Christmas 24.12 - 26.12
        if ($today === '24.12' || $today === '25.12' || $today === '26.12') {
            $season = 'christmas';
        }

        return $season;

    }
}
