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


use Cake\Utility\Hash;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ConfigGenerator {

    protected $basePath = APP . 'config_templates' . DS;

    /**
     * Folder where Twig should search for the template.
     * Overwrite in Child-Class!
     * $basePath + $templateDir
     * @var string
     */
    protected $templateDir = '';

    /**
     * Name of the Template
     * Overwrite in Child-Class!
     * @var string
     */
    protected $template = '';

    /**
     * Path where the real config file will be generated
     * @var string
     */
    protected $realOutfile = '';

    /**
     * Full path where the config will be symlinked to!
     * @var string
     */
    protected $linkedOutfile = '';

    /**
     * Default values for configuration file
     * @var array
     */
    protected $defaults = [];

    /**
     * Overwrite in Child Class
     * @var string
     */
    protected $dbKey = 'Unknown';

    /**
     * @var array
     */
    public $validationErrors = [];

    /**
     * @var string
     */
    protected $commentChar = '#';

    /**
     * null for core configurations
     * string for module urls such as prometheus_module
     * @var null
     */
    protected $moduleUrl = null;

    /**
     * @return string
     */
    public function getTemplatePath() {
        return $this->basePath . $this->templateDir;
    }

    /**
     * @return string
     */
    public function getTemplateName() {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getTemplateNameWithPath() {
        return $this->getTemplatePath() . DS . $this->getTemplateName();
    }

    /**
     * @return string
     */
    public function getRealOutfile() {
        return $this->realOutfile;
    }


    public function getLinkedOutfile() {
        return $this->linkedOutfile;
    }

    /**
     * @param $value
     * @return float
     */
    public function asFloat($value) {
        return (float)$value;
    }

    /**
     * @param $value
     * @return int
     */
    public function asInt($value) {
        return (int)$value;
    }

    /**
     * @param $value
     * @return int
     */
    public function asBoolNumber($value) {
        $value = (bool)$value;

        if ($value === true) {
            return 1;
        }

        return 0;
    }

    /**
     * @param $value
     * @return string
     */
    public function asBoolString($value) {
        $value = (bool)$value;

        if ($value === true) {
            return 'true';
        }

        return 'false';
    }

    /**
     * @param $value
     * @return string
     */
    public function asUcfirstBoolString($value) {
        return ucfirst($this->asBoolString($value));
    }

    /**
     * @param $value
     * @return string
     */
    public function asString($value) {
        return (string)$value;
    }

    /**
     * @return array
     */
    public function getDefaults() {
        return $this->defaults;
    }

    /**
     * @param $data
     * @return array|bool
     */
    public function validate($data) {
        $error = [];
        $fakeModelName = 'Configfile';

        $Validator = new ConfigValidator();

        foreach ($this->defaults as $type => $fields) {
            foreach ($fields as $field => $defaultValue) {
                if (!isset($data[$type][$field])) {
                    $error[$fakeModelName][$field][] = __('This field cannot be left blank.');
                }

                $value = $data[$type][$field];

                switch ($type) {
                    case 'float':
                        if (!$Validator->assertFloat($value)) {
                            $error[$fakeModelName][$field][] = __('This field needs to be a float.');
                        }
                        break;

                    case 'int':
                        if (!$Validator->assertInt($value)) {
                            $error[$fakeModelName][$field][] = __('This field needs to be an integer.');
                        }
                        break;

                    case 'bool':
                        if (!$Validator->assertBool($value)) {
                            $error[$fakeModelName][$field][] = __('This field needs to be a boolean.');
                        }
                        break;

                    case 'string':
                        if (!$Validator->assertStringNotEmpty($value)) {
                            $error[$fakeModelName][$field][] = __('This field can not left be blank.');
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        $customResult = $this->customValidationRules($data);
        if (is_array($customResult) && !empty($customResult)) {
            $error = Hash::merge($error, $customResult);
        }

        if (empty($error)) {
            return true;
        }
        $this->validationErrors = $error;
        return false;
    }

    /**
     * @return string
     */
    public function getDbKey() {
        return $this->dbKey;
    }

    /**
     * @param array $dbRecords from CakePHP find
     * @return array
     */
    public function mergeDbResultWithDefaultConfiguration($dbRecords) {
        $mergedConfiguration = [];
        $dbRecords = $this->flatDbResult($dbRecords);

        foreach ($this->getDefaults() as $type => $fields) {
            foreach ($fields as $key => $defaultValue) {

                //Check for missing keys in database
                if (!isset($dbRecords[$key])) {
                    //Set default value for missing keys
                    $mergedConfiguration[$type][$key] = $defaultValue;
                    continue;
                }

                switch ($type) {
                    //Carst values from database into right format
                    case 'float':
                        $mergedConfiguration[$type][$key] = $this->asFloat($dbRecords[$key]);
                        break;

                    case 'int':
                        $mergedConfiguration[$type][$key] = $this->asInt($dbRecords[$key]);
                        break;

                    case 'bool':
                        $mergedConfiguration[$type][$key] = $this->asBoolNumber((int)$dbRecords[$key]);
                        break;

                    default:
                        $mergedConfiguration[$type][$key] = $dbRecords[$key];
                        break;
                }
            }

        }

        return $mergedConfiguration;
    }

    /**
     * @param array $dbRecords from CakePHP find
     * @return array
     */
    private function flatDbResult($dbResult) {
        $result = [];
        foreach ($dbResult as $record) {
            // ITC-2986 make default settings from environment variable look like from the database
            if (!isset($record['ConfigurationFile'])) {
                $record = [
                    'ConfigurationFile' => $record
                ];
            }

            $result[$record['ConfigurationFile']['key']] = $record['ConfigurationFile']['value'];
        }
        return $result;
    }

    /**
     * @param $requestData
     * @return array
     */
    public function convertRequestForSaveAll($requestData) {
        $records = [];

        foreach ($requestData as $type => $fields) {
            foreach ($fields as $key => $value) {
                $records[] = [
                    'ConfigurationFile' => [
                        'config_file' => $this->getDbKey(),
                        'key'         => $key,
                        'value'       => $value
                    ]
                ];
            }
        }
        return $records;
    }


    /**
     * @param $configToExport
     * @return bool|int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function saveConfigFile($configToExport) {
        //Do not call this method direct!
        //Call it from self::writeToFile() and use writeToFile to format $configToExport as required.
        //For example rewrite "1" to "yes" for yml or so

        $loader = new FilesystemLoader($this->getTemplatePath());
        $twig = new Environment($loader, ['debug' => true]);

        $FileHeader = new FileHeader();
        $configToExport['STATIC_FILE_HEADER'] = $FileHeader->getHeader($this->commentChar);
        $configToExport['IS_CONTAINER'] = IS_CONTAINER;

        $configDir = dirname($this->realOutfile);
        if (!is_dir($configDir)) {
            return false;
        }

        $ConfigSymlink = new ConfigSymlink($this->realOutfile, $this->linkedOutfile);
        $result = file_put_contents($this->realOutfile, $twig->render($this->getTemplateName(), $configToExport));
        $ConfigSymlink->link();

        return $result;
    }

    public function toArray() {
        return [
            'linkedOutfile'    => $this->getLinkedOutfile(),
            'realOutfile'      => $this->getRealOutfile(),
            'dbKey'            => $this->getDbKey(),
            'angularDirective' => $this->getAngularDirective(),
            'moduleUrl'        => $this->moduleUrl
        ];
    }

    /**
     * Serialize all config fields for Angular (not AngularJS)
     * This is used by the config file editor
     * @params array $mergedConfiguration
     * @return array|mixed
     */
    public function getFieldsForAngular(array $mergedConfiguration) {
        $angularFields = [];
        foreach ($mergedConfiguration as $type => $fields) {
            foreach ($fields as $field => $value) {

                $f = [
                    'type'        => $type,
                    'field'       => $field,
                    'value'       => $value,
                    'placeholder' => $value,
                    'help'        => $this->getHelpText($field)
                ];
                $angularFields[] = $f;
            }
        }

        return $angularFields;
    }

    /**
     * Overwrite this function in the Config File Class !
     * @param $key
     * @return string
     */
    public function getHelpText($key) {
        return '';
    }

}
