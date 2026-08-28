<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace App\itnovum\openITCOCKPIT\Core\Charts;

/**
 * AreaChart: Render modern dual Y-axis area charts as static PNG images.
 *
 * Supports two data series (each with its own Y axis and unit), theming, legend, axis customization,
 * and various display options. Uses PHP GD for all rendering.
 */
class AreaChart {


    /** @var \GdImage GD image buffer for chart rendering */
    private $image;

    /** @var string Theme: 'dark' or 'light' */
    private string $theme = 'dark';

    /** @var array Current color palette in use */
    private array $palette;

    /** @var array Color palette for dark theme */
    private $darkPalette = [
        'bg'      => [34, 38, 46],
        'grid'    => [60, 65, 80],
        'axis'    => [221, 226, 231],
        'label'   => [221, 226, 231],
        'series1' => [0, 199, 255],
        'series2' => [0, 90, 158],
    ];

    /** @var array Color palette for light theme */
    private array $lightPalette = [
        'bg'      => [247, 248, 250],
        'grid'    => [227, 230, 236],
        'axis'    => [34, 38, 46],
        'label'   => [34, 38, 46],
        'series1' => [50, 116, 217],
        'series2' => [163, 82, 204],
    ];

    /** @var bool Show data point markers (circles) if true */
    private bool $showMarkers = false;

    /** @var string Path to TTF font file for text rendering */
    private string $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

    /** @var int Chart width in pixels */
    private int $width = 1000;
    /** @var int Chart height in pixels */
    private int $height = 400;

    /** @var string Chart title */
    private string $title = '';
    /** @var array Legend labels for both series */
    private array $legend = ['', ''];
    /** @var array First data series */
    private array $series1 = [];
    /** @var array Second data series (optional) */
    private array $series2 = [];

    /** @var string Unit label for left Y axis (series 1) */
    private string $y1Unit = '';
    /** @var string Unit label for right Y axis (series 2) */
    private string $y2Unit = '';

    /** @var int|float Minimum X (timestamp) */
    private int|float $minTime;
    /** @var int|float Maximum X (timestamp) */
    private int|float $maxTime;
    /** @var float Minimum Y1 value */
    private float $minY1;
    /** @var ?float Maximum Y1 value */
    private ?float $maxY1;
    /** @var float|null Minimum Y2 value (if series2) */
    private ?float $minY2 = null;
    /** @var float|null Maximum Y2 value (if series2) */
    private ?float $maxY2 = null;

    // User override options for axis ranges and X start/end
    private ?float $y1MinOverride = null;
    private ?float $y1MaxOverride = null;
    private ?float $y2MinOverride = null;
    private ?float $y2MaxOverride = null;
    private ?int $xStartOverride = null;
    private ?int $xEndOverride = null;

    /** @var string|null Optional user-supplied date/time format for X axis labels */
    private ?string $xAxisFormatOverwrite = null;

    /** @var int|null Maximum allowed gap (in seconds) between points before breaking the area/line. Null disables gap detection. */
    private ?int $gapThresholdSeconds = null;

    /**
     * Constructs a new AreaChart with the given dimensions and initializes the image buffer.
     *
     * Sets the default theme to dark and prepares the GD image for drawing.
     *
     * @param int $width Chart width in pixels
     * @param int $height Chart height in pixels
     */
    public function __construct(int $width, int $height) {
        $this->palette = $this->darkPalette;
        $this->width = $width;
        $this->height = $height;
        // Create the GD image buffer and enable alpha blending
        $this->image = imagecreatetruecolor($width, $height);
        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);
    }


    /**
     * Sets the chart title text.
     *
     * @param string $title Title to display at the top of the chart
     */
    public function setTitle(string $title): void {
        $this->title = $title;
    }

    /**
     * Sets the legend labels for both data series.
     *
     * @param string $series1Label Label for the first series
     * @param string $series2Label Label for the second series
     */
    public function setLegend(string $series1Label, string $series2Label): void {
        $this->legend = [$series1Label, $series2Label];
    }

    /**
     * Sets the font file path for text rendering.
     *
     * You can pass any TrueType font file path here, for example:
     * - /usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf
     * - /usr/share/fonts/truetype/dejavu/DejaVuSans.ttf
     *
     * @param string $fontPath Path to TTF font file
     */
    public function setFont(string $fontPath): void {
        if (!is_file($fontPath) || !is_readable($fontPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Font file is not readable: %s',
                $fontPath
            ));
        }
        $this->font = $fontPath;
    }

    /**
     * Enable or disable data point markers (circles)
     * @param bool $show
     */
    public function setShowMarkers(bool $show): void {
        $this->showMarkers = $show;
    }

    /**
     * Sets the chart theme and updates the color palette.
     *
     * @param string $theme 'dark' or 'light'
     */
    public function setTheme(string $theme): void {
        $this->theme = $theme;
        $this->palette = $this->darkPalette;
        if ($theme === 'light') {
            $this->palette = $this->lightPalette;
        }
    }

    /**
     * Sets a custom color palette for the chart.
     *
     * Use this to override the default theme palettes with your own color array.
     * The palette array should define keys: 'bg', 'grid', 'axis', 'label', 'series1', 'series2'.
     *
     * @param array $palette Associative array of color values for chart elements
     */
    public function setPalette(array $palette): void {
        $requiredKeys = ['bg', 'grid', 'axis', 'label', 'series1', 'series2'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $palette)) {
                throw new \InvalidArgumentException(sprintf('Missing palette key: %s', $key));
            }
            if (!is_array($palette[$key]) || count($palette[$key]) !== 3) {
                throw new \InvalidArgumentException(sprintf(
                    'Palette key "%s" must be an RGB triplet [r,g,b]',
                    $key
                ));
            }
            foreach ($palette[$key] as $channel) {
                if (!is_int($channel) || $channel < 0 || $channel > 255) {
                    throw new \InvalidArgumentException(sprintf(
                        'Palette key "%s" has invalid RGB channel value',
                        $key
                    ));
                }
            }
        }
        $this->palette = $palette;
    }

    /**
     * Sets the unit label for the left Y axis.
     *
     * @param string $unit Unit text (e.g., '°C', 'MB/s')
     */
    public function setY1Unit(string $unit): void {
        $this->y1Unit = $unit;
    }

    /**
     * Sets the unit label for the right Y axis.
     *
     * @param string $unit Unit text (e.g., '%', 'rpm')
     */
    public function setY2Unit(string $unit): void {
        $this->y2Unit = $unit;
    }

    /**
     * Set a min and max range for the right Y-Axes
     * @param null|float $min Minimum value for Y-axis
     * @param null|float $max Maximum value for Y-axis
     */
    public function setY1Range(?float $min, ?float $max): void {
        $this->y1MinOverride = $min;
        $this->y1MaxOverride = $max;
    }

    /**
     * Set a min and max range for the right Y-Axes
     * @param null|float $min Minimum value for Y-axis
     * @param null|float $max Maximum value for Y-axis
     */
    public function setY2Range(?float $min, ?float $max): void {
        $this->y2MinOverride = $min;
        $this->y2MaxOverride = $max;
    }

    /**
     * Set start timestamps for X-Axes
     * @param null|int $start Start timestamp (Unix epoch)
     */
    public function setXStart(?int $start): void {
        $this->xStartOverride = $start;
    }

    /**
     * Set end timestamps for X-Axes
     * @param null|int $end End timestamp (Unix epoch)
     */
    public function setXEnd(?int $end): void {
        $this->xEndOverride = $end;
    }

    /**
     * Set a custom date/time format for X axis labels.
     *
     * If set, this format will override the automatic format selection.
     * Example: 'Y-m-d H:i', 'H:i', etc.
     *
     * @param string $format PHP date() format string
     */
    public function setXAxisFormat(string $format): void {
        $this->xAxisFormatOverwrite = $format;
    }

    /**
     * Set the maximum allowed gap (in seconds) between consecutive data points before breaking the area and line.
     * If not set or null, gap detection is disabled and all points are connected.
     *
     * @param int|null $seconds Gap threshold in seconds, or null to disable
     */
    public function setGapThreshold(?int $seconds): void {
        $this->gapThresholdSeconds = $seconds;
    }

    /**
     * Automatically calculates a gap threshold for the given series based on timestamp intervals.
     * Uses the mode "most common interval" and sets the threshold to 2x the mode.
     *
     * Call this after setting data with setData().
     *
     * @param int $seriesIndex 1 for series1, 2 for series2
     * @param float $multiplier Multiplier for the mode interval (default 2.0)
     * @return int|null The calculated threshold in seconds, or null if not enough data
     */
    public function getAutoGapThreshold(int $seriesIndex = 1, float $multiplier = 2.0): ?int {
        $series = ($seriesIndex === 2) ? $this->series2 : $this->series1;
        if (count($series) < 2) {
            return null;
        }
        // Calculate intervals between consecutive timestamps
        $intervals = [];
        for ($i = 1; $i < count($series); $i++) {
            $intervals[] = intval($series[$i]['timestamp'] - $series[$i - 1]['timestamp']);
        }
        if (empty($intervals)) {
            return null;
        }
        // Find the mode (most common interval)
        $counts = array_count_values($intervals);
        arsort($counts);
        $mode = (int)array_key_first($counts);
        $threshold = (int)round($mode * $multiplier);
        return $threshold;
    }

    /**
     * @param array<array{timestamp:mixed,value:mixed}> $series
     */
    private function validateSeries(array $series, string $seriesName, bool $allowEmpty): void {
        if (!$allowEmpty && empty($series)) {
            throw new \InvalidArgumentException(sprintf('%s must not be empty', $seriesName));
        }
        foreach ($series as $idx => $point) {
            if (!is_array($point) || !array_key_exists('timestamp', $point) || !array_key_exists('value', $point)) {
                throw new \InvalidArgumentException(sprintf(
                    '%s[%d] must contain keys "timestamp" and "value"',
                    $seriesName,
                    $idx
                ));
            }
            if (!is_numeric($point['timestamp']) || !is_numeric($point['value'])) {
                throw new \InvalidArgumentException(sprintf(
                    '%s[%d] timestamp/value must be numeric',
                    $seriesName,
                    $idx
                ));
            }
        }
    }

    /**
     *
     * /**
     * @param array<array{timestamp:mixed,value:mixed}> $series
     * @return array<array{timestamp:int,value:float}>
     */
    private function normalizeAndSortSeries(array $series): array {
        $normalized = array_map(function (array $point): array {
            return [
                'timestamp' => (int)$point['timestamp'],
                'value'     => (float)$point['value'],
            ];
        }, $series);
        usort($normalized, static function (array $a, array $b): int {
            return $a['timestamp'] <=> $b['timestamp'];
        });
        return $normalized;
    }

    private function mapX(int $timestamp, int $paddingLeft, int $plotWidth): float {
        if ($this->minTime === null || $this->maxTime === null || $this->maxTime === $this->minTime) {
            return $paddingLeft + ($plotWidth / 2);
        }
        return $paddingLeft + $plotWidth * ($timestamp - $this->minTime) / ($this->maxTime - $this->minTime);
    }

    /**
     * Sets the data series for the chart and computes axis ranges.
     *
     * This method updates the internal data arrays and calculates min/max for X and Y axes,
     * applying user overrides if set. Both series must be arrays of ['timestamp' => int, 'value' => float].
     *
     * @param array $series1 First data series (required)
     * @param array $series2 Second data series (optional)
     */
    public function setData(array $series1, array $series2 = []): void {
        $this->validateSeries($series1, 'series1', true);
        $this->validateSeries($series2, 'series2', true);

        $series1 = $this->normalizeAndSortSeries($series1);
        $series2 = $this->normalizeAndSortSeries($series2);

        $this->series1 = $series1;
        $this->series2 = $series2;

        $timestamps = [];
        if (!empty($series1)) {
            $timestamps = array_merge($timestamps, array_column($series1, 'timestamp'));
        }
        if (!empty($series2)) {
            $timestamps = array_merge($timestamps, array_column($series2, 'timestamp'));
        }

        if (empty($timestamps)) {
            // Empty chart defaults
            $now = time();
            $this->minTime = $this->xStartOverride ?? ($now - 3600);
            $this->maxTime = $this->xEndOverride ?? $now;
            if ($this->minTime > $this->maxTime) {
                [$this->minTime, $this->maxTime] = [$this->maxTime, $this->minTime];
            }

            $this->minY1 = $this->y1MinOverride ?? 0.0;
            $this->maxY1 = $this->y1MaxOverride ?? 1.0;
            if ($this->minY1 > $this->maxY1) {
                [$this->minY1, $this->maxY1] = [$this->maxY1, $this->minY1];
            }

            $this->minY2 = $this->y2MinOverride;
            $this->maxY2 = $this->y2MaxOverride;
            if ($this->minY2 !== null && $this->maxY2 !== null && $this->minY2 > $this->maxY2) {
                [$this->minY2, $this->maxY2] = [$this->maxY2, $this->minY2];
            }

            return;
        }

        // Use override for X axis if set, otherwise use data min/max
        $this->minTime = $this->xStartOverride !== null ? $this->xStartOverride : min($timestamps);
        $this->maxTime = $this->xEndOverride !== null ? $this->xEndOverride : max($timestamps);
        if ($this->minTime > $this->maxTime) {
            [$this->minTime, $this->maxTime] = [$this->maxTime, $this->minTime];
        }

        // Compute Y1 axis range (with override if set)
        $y1Vals = array_column($series1, 'value');
        $this->minY1 = $this->y1MinOverride !== null ? $this->y1MinOverride : min($y1Vals);
        $this->maxY1 = $this->y1MaxOverride !== null ? $this->y1MaxOverride : max($y1Vals);
        if ($this->minY1 > $this->maxY1) {
            [$this->minY1, $this->maxY1] = [$this->maxY1, $this->minY1];
        }

        // Compute Y2 axis range if second series is present
        if (!empty($series2)) {
            $y2Vals = array_column($series2, 'value');
            $this->minY2 = $this->y2MinOverride !== null ? $this->y2MinOverride : min($y2Vals);
            $this->maxY2 = $this->y2MaxOverride !== null ? $this->y2MaxOverride : max($y2Vals);
            if ($this->minY2 > $this->maxY2) {
                [$this->minY2, $this->maxY2] = [$this->maxY2, $this->minY2];
            }
        } else {
            $this->minY2 = null;
            $this->maxY2 = null;
        }
    }


    /**
     * Fills the chart background using the current theme's background color.
     *
     * This should be called before drawing any chart elements.
     */
    public function drawBase(): void {
        // Fill background
        $bg = imagecolorallocate($this->image, ...$this->palette['bg']);
        imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $bg);
    }

    /**
     * Draws the chart title centered at the top of the image.
     *
     * Uses the current theme's label color and font settings. If no title is set, nothing is drawn.
     */
    public function drawTitle(): void {
        $label = imagecolorallocate($this->image, ...$this->palette['label']);
        if ($this->title) {
            $fontSize = 16;
            // Calculate text width for horizontal centering
            $bbox = imagettfbbox($fontSize, 0, $this->font, $this->title);
            $textWidth = $bbox[2] - $bbox[0];
            $x = ($this->width - $textWidth) / 2;
            imagettftext($this->image, $fontSize, 0, intval($x), 30, $label, $this->font, $this->title);
        }
    }

    /**
     * Draws the chart legend with color boxes, series labels, and min/max values.
     *
     * The legend displays both series (if present), their colors, labels, and min/max values,
     * with min/max values right-aligned for clarity.
     */
    public function drawLegend(): void {
        $label = imagecolorallocate($this->image, ...$this->palette['label']);
        $series1Color = imagecolorallocate($this->image, ...$this->palette['series1']);
        $series2Color = imagecolorallocate($this->image, ...$this->palette['series2']);
        $legendX = 40;
        $legendY = $this->height - 60;
        $boxSize = 20;
        $spacingY = 30;

        // Draw color box and label for series 1
        imagefilledrectangle($this->image, $legendX, $legendY, $legendX + $boxSize, $legendY + $boxSize, $series1Color);
        $series1Min = isset($this->minY1) ? number_format($this->minY1, 3) : '';
        $series1Max = isset($this->maxY1) ? number_format($this->maxY1, 3) : '';
        $series1Text = $this->legend[0];
        imagettftext($this->image, 12, 0, $legendX + $boxSize + 10, $legendY + $boxSize - 5, $label, $this->font, $series1Text);
        // Show min/max for series 1, right-aligned
        if ($series1Min !== '' && $series1Max !== '') {
            $minMaxText = "min: $series1Min, max: $series1Max";
            $fontSize = 12;
            $bbox = imagettfbbox($fontSize, 0, $this->font, $minMaxText);
            $textWidth = $bbox[2] - $bbox[0];
            $legendRight = $this->width - 40 - $textWidth; // 40px from right edge
            imagettftext($this->image, $fontSize, 0, $legendRight, $legendY + $boxSize - 5, $label, $this->font, $minMaxText);
        }

        if (!empty($this->series2)) {
            // Draw color box and label for series 2 (if present)
            imagefilledrectangle($this->image, $legendX, $legendY + $spacingY, $legendX + $boxSize, $legendY + $boxSize + $spacingY, $series2Color);
            $series2Min = (isset($this->minY2) && $this->minY2 !== null) ? number_format($this->minY2, 3) : '';
            $series2Max = (isset($this->maxY2) && $this->maxY2 !== null) ? number_format($this->maxY2, 3) : '';
            $series2Text = $this->legend[1];
            imagettftext($this->image, 12, 0, $legendX + $boxSize + 10, $legendY + $boxSize + $spacingY - 5, $label, $this->font, $series2Text);
            // Show min/max for series 2, right-aligned
            if ($series2Min !== '' && $series2Max !== '') {
                $minMaxText2 = "min: $series2Min, max: $series2Max";
                $fontSize = 12;
                $bbox2 = imagettfbbox($fontSize, 0, $this->font, $minMaxText2);
                $textWidth2 = $bbox2[2] - $bbox2[0];
                $legendRight2 = $this->width - 40 - $textWidth2; // 40px from right edge
                imagettftext($this->image, $fontSize, 0, $legendRight2, $legendY + $boxSize + $spacingY - 5, $label, $this->font, $minMaxText2);
            }
        }
    }

    /**
     * Determines an appropriate date/time format for X axis labels based on the chart's time range.
     *
     * For short ranges, shows time (H:i), for longer ranges, shows date and time, or just date/year.
     *
     * @return string PHP date() format string suitable for the current X axis range
     */
    private function getXAxisTimeFormat(): string {
        // Use user-supplied format if set
        if (!empty($this->xAxisFormatOverwrite)) {
            return $this->xAxisFormatOverwrite;
        }
        if (!isset($this->minTime) || !isset($this->maxTime)) {
            return 'H:i'; // fallback
        }
        $range = $this->maxTime - $this->minTime;
        if ($range < 3600) {
            // Less than 1 hour: show minutes and seconds
            return 'H:i:s';
        } else if ($range < 86400) {
            // Less than 1 day: show hours and minutes
            return 'H:i';
        } else if ($range < (86400 * 31)) { // ~31 days
            // Less than 1 month: show day and hour
            return 'M d H:i';
        } else if ($range < (86400 * 365)) {
            // Less than 1 year: show month and day
            return 'M d';
        } else {
            // More than 1 year: show year and month
            return 'Y M';
        }
    }

    /**
     * Draws axes, grid lines, and axis labels for the chart.
     *
     * This method handles all axis/grid rendering, including dynamic padding for units,
     * Y and X axis ticks, axis labels, and vertical unit text. It also limits X ticks for clarity.
     */
    public function drawAxesAndGrid(): void {
        // Adjust padding if Y-axis units are present
        $paddingLeft = $this->y1Unit ? 120 : 70;
        $paddingRight = ($this->y2Unit && !empty($this->series2)) ? 120 : 70;
        $paddingTop = 40;
        $paddingBottom = 100;
        $plotWidth = $this->width - $paddingLeft - $paddingRight;
        $plotHeight = $this->height - $paddingTop - $paddingBottom;

        // Allocate colors for axes, grid, labels, and units
        $axis = imagecolorallocate($this->image, ...$this->palette['axis']);
        $grid = imagecolorallocate($this->image, ...$this->palette['grid']);
        $label = imagecolorallocate($this->image, ...$this->palette['label']);
        // Lighter color for units
        $unitColor = imagecolorallocate($this->image, 200, 200, 200);

        // Draw horizontal grid lines and Y axis ticks
        $numYTicks = 5;
        for ($i = 0; $i <= $numYTicks; $i++) {
            $y = $paddingTop + $plotHeight * $i / $numYTicks;
            imageline($this->image, $paddingLeft, $y, $this->width - $paddingRight, $y, $grid);
        }

        // Draw axes
        imageline($this->image, $paddingLeft, $paddingTop, $paddingLeft, $this->height - $paddingBottom, $axis); // Left Y
        imageline($this->image, $this->width - $paddingRight, $paddingTop, $this->width - $paddingRight, $this->height - $paddingBottom, $axis); // Right Y
        imageline($this->image, $paddingLeft, $this->height - $paddingBottom, $this->width - $paddingRight, $this->height - $paddingBottom, $axis); // X

        // Draw Y axis labels (left)
        $yLabelX = $this->y1Unit ? 60 : 10;
        for ($i = 0; $i <= $numYTicks; $i++) {
            $val = $this->maxY1 - ($this->maxY1 - $this->minY1) * $i / $numYTicks;
            $y = $paddingTop + $plotHeight * $i / $numYTicks;
            $text = number_format($val, 3);
            imagettftext($this->image, 12, 0, $yLabelX, $y + 5, $label, $this->font, $text);
        }
        // Draw vertical Y1 unit (if set)
        if ($this->y1Unit) {
            $fontSize = 12;
            $bbox = imagettfbbox($fontSize, 90, $this->font, $this->y1Unit);
            $textHeight = $bbox[2] - $bbox[0];
            $x = 25;
            $y = $paddingTop + $plotHeight / 2 + $textHeight / 2;
            imagettftext($this->image, $fontSize, 90, $x, $y, $unitColor, $this->font, $this->y1Unit);
        }

        // Draw right Y axis labels and unit (if series2 exists)
        if (!empty($this->series2) && $this->minY2 !== null && $this->maxY2 !== null) {
            $yLabelX2 = $this->y2Unit ? $this->width - $paddingRight + 10 : $this->width - $paddingRight + 10;
            for ($i = 0; $i <= $numYTicks; $i++) {
                $val = $this->maxY2 - ($this->maxY2 - $this->minY2) * $i / $numYTicks;
                $y = $paddingTop + $plotHeight * $i / $numYTicks;
                $text = number_format($val, 2);
                imagettftext($this->image, 12, 0, $yLabelX2, $y + 5, $label, $this->font, $text);
            }
            // Draw vertical Y2 unit (if set)
            if ($this->y2Unit) {
                $fontSize = 12;
                $bbox = imagettfbbox($fontSize, 90, $this->font, $this->y2Unit);
                $textHeight = $bbox[2] - $bbox[0];
                $x = $this->width - 35;
                $y = $paddingTop + $plotHeight / 2 + $textHeight / 2;
                imagettftext($this->image, $fontSize, 90, $x, $y, $unitColor, $this->font, $this->y2Unit);
            }
        }

        // Prepare X axis ticks (limit to maxXTicks for clarity)
        $maxXTicks = 5;
        $tickCount = count($this->series1);
        $tickTimestamps = array_column($this->series1, 'timestamp');
        if (empty($tickTimestamps) && !empty($this->series2)) {
            $tickTimestamps = array_column($this->series2, 'timestamp');
        }
        if (empty($tickTimestamps)) {
            $maxXTicks = 5;
            $tickTimestamps = [];
            $step = max(1, (int)(($this->maxTime - $this->minTime) / max(1, $maxXTicks - 1)));
            for ($i = 0; $i < $maxXTicks; $i++) {
                $tickTimestamps[] = $this->minTime + ($i * $step);
            }
            $tickTimestamps[$maxXTicks - 1] = $this->maxTime; // sauberer Endpunkt
        }

        $ticks = $tickTimestamps;

        // Fill in missing ticks if xStartOverride is set
        if (!empty($tickTimestamps) && $this->xStartOverride !== null && $this->xStartOverride < min($tickTimestamps)) {
            $firstTick = $this->xStartOverride;
            $lastTick = $this->maxTime ?? max($tickTimestamps);
            // Estimate interval from data
            $interval = ($tickCount > 1) ? max(1, (int)($tickTimestamps[1] - $tickTimestamps[0])) : 60;
            $ticks = [];
            for ($ts = $firstTick; $ts <= $lastTick; $ts += $interval) {
                $ticks[] = $ts;
            }
        } else {
            $ticks = $tickTimestamps;
        }
        // Limit ticks to maxXTicks
        $tickTotal = count($ticks);
        if ($tickTotal > $maxXTicks) {
            $step = ($tickTotal - 1) / ($maxXTicks - 1);
            $limitedTicks = [];
            for ($i = 0; $i < $maxXTicks; $i++) {
                $idx = (int)round($i * $step);
                if ($idx >= $tickTotal) $idx = $tickTotal - 1;
                $limitedTicks[] = $ticks[$idx];
            }
            $ticks = $limitedTicks;
        }
        // Draw X axis labels
        foreach ($ticks as $ts) {
            $x = $this->mapX((int)$ts, $paddingLeft, $plotWidth);
            $text = date($this->getXAxisTimeFormat(), $ts);
            // Center the label at the tick by calculating text width
            $bbox = imagettfbbox(12, 0, $this->font, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $labelX = $x - ($textWidth / 2);
            imagettftext($this->image, 12, 0, intval($labelX), intval($this->height - $paddingBottom + 30), $label, $this->font, $text);
        }

        // Draw vertical X grid lines
        foreach ($ticks as $ts) {
            $x = $this->mapX((int)$ts, $paddingLeft, $plotWidth);
            imageline($this->image, intval($x), intval($paddingTop), intval($x), intval($this->height - $paddingBottom), $grid);
        }
    }


    /**
     * Maps a data value to a Y pixel coordinate in the plot area.
     *
     * @param float $val The data value to map
     * @param float $min Minimum value of the axis
     * @param float $max Maximum value of the axis
     * @param int $paddingTop Top padding of the plot area
     * @param int $plotHeight Height of the plot area
     * @return float            Y pixel coordinate
     */
    private function mapY(float $val, float $min, float $max, int $paddingTop, int $plotHeight): float {
        // If min == max, center the value; otherwise, scale value to plot area
        if ($max == $min) return $paddingTop + $plotHeight / 2;
        return $paddingTop + $plotHeight * (1 - ($val - $min) / ($max - $min));
    }

    /**
     * Draws the filled area and line for a single data series.
     *
     * This method fills the area under the series, draws the series line,
     * and optionally draws circular markers at each data point.
     *
     * @param array $series Array of ['timestamp' => int, 'value' => float] points
     * @param float $minY Minimum Y value for scaling
     * @param float $maxY Maximum Y value for scaling
     * @param string $seriesColorKey Palette key for the series color
     * @param int $paddingLeft Left padding of plot area
     * @param int $paddingRight Right padding of plot area
     * @param int $paddingTop Top padding of plot area
     * @param int $paddingBottom Bottom padding of plot area
     * @param int $plotWidth Width of plot area
     * @param int $plotHeight Height of plot area
     * @param int $fillAlpha Alpha for area fill (0-127)
     * @param int $lineAlpha Alpha for line (0-127)
     * @param int $lineThickness Line thickness in pixels
     */
    private function drawSeriesAreaAndLine(
        array $series, float $minY, float $maxY, string $seriesColorKey,
        int   $paddingLeft, int $paddingRight, int $paddingTop, int $paddingBottom,
        int   $plotWidth, int $plotHeight,
        int   $fillAlpha = 60, int $lineAlpha = 0, int $lineThickness = 2
    ) {
        $fillColor = $this->palette[$seriesColorKey];
        // Make fill color lighter for area fill
        $lighter = [
            min(255, $fillColor[0] + 40),
            min(255, $fillColor[1] + 40),
            min(255, $fillColor[2] + 40)
        ];
        $fill = imagecolorallocatealpha($this->image, $lighter[0], $lighter[1], $lighter[2], $fillAlpha);
        $line = imagecolorallocatealpha($this->image, $fillColor[0], $fillColor[1], $fillColor[2], $lineAlpha);

        // Gap-aware area and line drawing
        $segments = [];
        $currentSegment = [];
        $lastTs = null;
        foreach ($series as $pt) {
            // Map data point to plot coordinates
            $x = $this->mapX((int)$pt['timestamp'], $paddingLeft, $plotWidth);
            $y = $this->mapY($pt['value'], $minY, $maxY, $paddingTop, $plotHeight);
            if ($lastTs !== null && $this->gapThresholdSeconds !== null && ($pt['timestamp'] - $lastTs) > $this->gapThresholdSeconds) {
                // Gap detected, start new segment
                if (count($currentSegment) > 0) {
                    $segments[] = $currentSegment;
                }
                $currentSegment = [];
            }
            $currentSegment[] = ['x' => $x, 'y' => $y, 'timestamp' => $pt['timestamp']];
            $lastTs = $pt['timestamp'];

            // Draw marker if enabled
            if ($this->showMarkers) {
                $markerColor = imagecolorallocate($this->image, $fillColor[0], $fillColor[1], $fillColor[2]);
                imagefilledellipse($this->image, (int)$x, (int)$y, 8, 8, $markerColor);
                $borderColor = imagecolorallocate($this->image, 255, 255, 255);
                imageellipse($this->image, (int)$x, (int)$y, 8, 8, $borderColor);
            }
        }
        if (count($currentSegment) > 0) {
            $segments[] = $currentSegment;
        }

        // Draw each segment as a separate area and line
        foreach ($segments as $segment) {
            if (count($segment) < 2) continue; // Need at least 2 points for area/line
            // Area polygon: segment points + down to X axis at both ends
            $areaPoints = [];
            foreach ($segment as $pt) {
                $areaPoints[] = [$pt['x'], $pt['y']];
            }
            // Close polygon to X axis
            $areaPoints[] = [$segment[count($segment) - 1]['x'], $this->height - $paddingBottom];
            $areaPoints[] = [$segment[0]['x'], $this->height - $paddingBottom];
            imagefilledpolygon($this->image, array_merge(...$areaPoints), $fill);

            // Draw line for this segment
            imagesetthickness($this->image, $lineThickness);
            for ($i = 1; $i < count($segment); $i++) {
                imageline($this->image, intval($segment[$i - 1]['x']), intval($segment[$i - 1]['y']), intval($segment[$i]['x']), intval($segment[$i]['y']), $line);
            }
            imagesetthickness($this->image, 1); // Reset to default
        }
    }

    /**
     * Render the chart to the internal GD image buffer.
     *
     * This method draws the background, title, legend, axes, grid, and all data series
     * using the current chart settings and data. It is called automatically by getImage().
     * Call this if you need to update the chart after changing settings or data.
     */
    public function render(): void {
        $this->drawBase();           // Background fill
        $this->drawTitle();          // Chart title (if set)
        $this->drawLegend();         // Series legend (with min/max)
        $this->drawAxesAndGrid();    // Axes, grid lines, and axis labels

        // Calculate plot area and padding (matches axes/grid logic)
        $paddingLeft = $this->y1Unit ? 120 : 70;
        $paddingRight = ($this->y2Unit && !empty($this->series2)) ? 120 : 70;
        $paddingTop = 40;
        $paddingBottom = 100;
        $plotWidth = $this->width - $paddingLeft - $paddingRight;
        $plotHeight = $this->height - $paddingTop - $paddingBottom;

        // Draw first series (area and line)
        $this->drawSeriesAreaAndLine(
            $this->series1,
            $this->minY1,
            $this->maxY1,
            'series1',
            $paddingLeft,
            $paddingRight,
            $paddingTop,
            $paddingBottom,
            $plotWidth,
            $plotHeight,
            60, // fill alpha
            0,  // line alpha
            1   // line thickness (adjustable)
        );

        // Draw second series if present
        if (!empty($this->series2) && $this->minY2 !== null && $this->maxY2 !== null) {
            $this->drawSeriesAreaAndLine(
                $this->series2,
                $this->minY2,
                $this->maxY2,
                'series2',
                $paddingLeft,
                $paddingRight,
                $paddingTop,
                $paddingBottom,
                $plotWidth,
                $plotHeight,
                60, // fill alpha
                0,  // line alpha
                1   // line thickness (adjustable)
            );
        }
    }


    /**
     * Get the rendered chart image as a GdImage object.
     *
     * This method calls render() to ensure the chart is fully drawn with all current settings and data.
     * You can then use the returned GdImage with imagepng(), imagejpeg(), etc., or further manipulate it.
     *
     * @return \GdImage The rendered chart image
     */
    public function getImage(): \GdImage {
        $this->render();
        return $this->image;
    }

    public function getImageAsPngStream(): string {
        $this->render();

        $image = $this->image;

        // In case we want to force it to be in memory 100% we can use php://memory
        // php://temp will use memory, expect the image is to large (> 2mb, it will be written to disk)
        $fp = fopen('php://temp', 'w+b');
        if ($fp === false) {
            throw new \RuntimeException('Unable to open temporary stream for PNG output.');
        }

        try {
            if (!imagepng($image, $fp)) {
                throw new \RuntimeException('Failed to encode PNG image.');
            }

            rewind($fp);
            $png = stream_get_contents($fp);
            if ($png === false) {
                throw new \RuntimeException('Failed to read PNG stream.');
            }
        } finally {
            fclose($fp);
            imagedestroy($image);
        }

        return $png;
    }
}



