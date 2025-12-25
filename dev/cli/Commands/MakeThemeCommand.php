<?php

namespace Dev\Cli\Commands;

use RuntimeException;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeThemeCommand extends Command
{
    private $themeFile = '';
    protected static $defaultName = 'make:theme';
    protected static $defaultDescription = 'Generates a new color scheme.';

    public function __construct()
	{
	    parent::__construct();
	    $this->themeFile = realpath(__DIR__ . '/../../../config') . '/theme.php';
	}

    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Theme name')
            ->addOption(
            	'primary',
            	null,
            	InputOption::VALUE_OPTIONAL,
            	'Primary base color (hex)'
            )
            ->addOption(
            	'success',
            	null,
            	InputOption::VALUE_OPTIONAL,
            	'Success base color (hex)'
            )
            ->addOption(
            	'warning',
            	null,
            	InputOption::VALUE_OPTIONAL,
            	'Warning base color (hex)'
            )
            ->addOption(
            	'danger',
            	null,
            	InputOption::VALUE_OPTIONAL,
            	'Danger base color (hex)'
            )
            ->addOption(
            	'info',
            	null,
            	InputOption::VALUE_OPTIONAL,
            	'Info base color (hex)')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
	{
	    $io = new SymfonyStyle($input, $output);
	    $name = $input->getArgument('name');

	    // Load existing theme config or create new
		if (!file_exists($this->themeFile)) {
		    $io->writeln(
		    	"<fg=yellow;>Theme file not found. A new one will be created: {$this->themeFile}</>");
		    $config = [
		    	'default' => $name,
		    	'themes' => []
		    ];

		    // Ensure directory exists
		    $dir = dirname($this->themeFile);
		    if (!is_dir($dir)) {
		        mkdir($dir, 0755, true);
		    }

		    // Save initial empty config
		    $phpContent = "<?php\n\nreturn " . $this->arrayToPhp($config) . ";\n";
		    file_put_contents($this->themeFile, $phpContent);
		}

		if (!file_exists($this->themeFile)) {
		    $io->writeln(
		    	"<fg=yellow;>Theme file not found. A new one will be created: {$this->themeFile}</>"
		    );
		    $config = [
		    	'default' => $name,
		    	'themes' => []
		    ];
		} else {
		    $config = include $this->themeFile;

		    if (!is_array($config)) {
		        $io->writeln(
		        	"<fg=yellow;>Theme file is invalid. A new config will be created.</>"
		        );
		        $config = [
		        	'default' => $name,
		        	'themes' => []
		        ];
		    } elseif (!isset($config['themes']) || !is_array($config['themes'])) {
		        $config['themes'] = [];
		    }
		}

	    // Confirm overwrite if theme exists
	    if (isset($config['themes'][$name])) {
	        $overwrite = $io->confirm(
	            "Theme '$name' already exists. Do you want to overwrite it?",
	            false
	        );

	        if (!$overwrite) {
	            $io->info("Theme '$name' was not overwritten.");
	            return Command::SUCCESS;
	        }
	    }

	    // Gather existing primary colors
	    $existingPrimaryColors = [];
	    foreach ($config['themes'] as $theme) {
	        foreach (['light', 'dark'] as $mode) {
	            if (isset($theme[$mode]['primary'])) {
	                $existingPrimaryColors[] = strtoupper(
	                	$theme[$mode]['primary']
	                );
	            }
	        }
	    }

	    $baseColors = $usedColors = [];

	    foreach (['primary', 'success', 'warning', 'danger', 'info'] as $key) {
	        $optionColor = $input->getOption($key);

	        if ($optionColor) {
	            $color = strtoupper($optionColor);
	        } else {
	            $suggested = $key === 'primary' 
	                ? $this->randomHexColor()
	                : $this->generateUniqueColor(
	                	$baseColors['primary'], $usedColors, $key
	                );

				$this->showAvailableColors($io);

				$helper = $this->getHelper('question');
				$namedColors = $this->getNamedColors();

				$showName = ucfirst($key);
				$def = "<fg=$suggested>$suggested</>";
				$question = new Question(
				    "<info>{$showName}</info> color (hex or named) [$def]: ",
				    $suggested
				);

				$question->setAutocompleterValues($namedColors);

				$question->setValidator(function ($value) use ($namedColors) {
				    $value = trim($value);

				    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
				        return strtoupper($value);
				    }

				    if (in_array(strtolower($value), $namedColors)) {
				        return strtolower($value);
				    }

				    throw new RuntimeException(
				    	"Invalid color: $value. Must be hex or named color."
				    );
				});

				if ($baseColors) {
				    $maxLen = max(array_map('strlen', array_keys($baseColors)));
				    foreach ($baseColors as $k => $c) {
				        $label = str_pad(ucfirst($k), $maxLen, ' ', STR_PAD_RIGHT);
				        $io->writeln($label . ': ' . $this->colorBlock($c));
				    }
				}

				$color = $helper->ask($input, $output, $question);
	        }

	        $baseColors[$key] = $color;
	        $usedColors[] = $color;
	    }

	    $theme = [
	        'light' => ['bgColor' => '#f9f9f9'],
	        'dark'  => ['bgColor' => '#23233b'],
	    ];

	    foreach (['light', 'dark'] as $mode) {
		    foreach ($baseColors as $key => $color) {
		        
                // --- RESOLVE NAMED COLORS TO HEX HERE ---
                $baseHex = $this->resolveColorToHex($color);
                
                // Store the original user input (name or hex) in the theme
		        $theme[$mode][$key] = $color; 
		        // Use original input for UI config
		        $theme[$mode][$key . 'ButtonBg'] = $color;

		        // generate shades for reference palette (use resolved hex)
		        $shades = $this->generateShades($baseHex);

				// Hover: ALWAYS slightly darken the base color for a 'pressed'
				// look.  Factor of -0.05 darkens the RGB channels by
				// 5% of max (255).
				$hoverColor = $this->adjustColorBrightness($baseHex, -0.05);
				$theme[$mode][$key . 'ButtonHoverBg'] = $hoverColor;

				// Disabled: Use HSL method to desaturate and adjust lightness for a faded look.
				[$r, $g, $b] = $this->hexToRgb($baseHex);
				[$h, $s, $l] = $this->rgbToHsl($r, $g, $b);

				// Lightness target for disabled
				$disabledL = $mode === 'dark'
					? min(0.95, $l + 0.15)
					: max(0.05, $l - 0.15);

				// More desaturation for a clearer disabled state
				$disabledS = $s * 0.4;

				[$dr, $dg, $db] = $this->hslToRgb($h, $disabledS, $disabledL);

				$theme[$mode][$key . 'ButtonDisabledBg'] = sprintf("#%02X%02X%02X", $dr, $dg, $db);
		    }
		}

	    $config['default'] = $name;
	    $config['themes'][$name] = $theme;

	    $phpContent = "<?php\n\nreturn " . $this->arrayToPhp($config) . ";\n";
	    
	    file_put_contents($this->themeFile, $phpContent);

	    $io->info("Theme '$name' added successfully to theme config.");
	    
	    return Command::SUCCESS;
	}

    private function resolveColorToHex($color)
    {
        // Already hex? Return it.
        if (
        	str_starts_with($color, '#')
        	&& (strlen($color) === 7|| strlen($color) === 4
        )) {
            return strtoupper($color);
        }

        // Must be a named color. Get RGB.
        $rgb = $this->namedColorToRgb(strtolower($color));
        
        if ($rgb) {
            // Convert RGB array to hex string
            return sprintf("#%02X%02X%02X", $rgb[0], $rgb[1], $rgb[2]);
        }

        // Fallback for an unrecognized string
        return '#000000'; 
    }

	private function showAvailableColors(SymfonyStyle $io)
	{
	    $namedColors = $this->getNamedColors();
	    $columns = 5;
	    $count = 0;
	    $line = '';

	    foreach ($namedColors as $name) {
	        $line .= sprintf("%s %-20s ", $this->colorBlock($name), $name);
	        $count++;
	        if ($count % $columns === 0) {
	            $io->writeln($line);
	            $line = '';
	        }
	    }

	    if ($line !== '') {
	        $io->writeln($line);
	    }

	    $io->newLine();
	}

	private function getNamedColors()
	{
	    return [
	        // Basic colors
	        'black', 'white', 'red', 'green', 'blue', 'yellow', 'cyan', 'magenta',
	        'gray', 'grey', 'maroon', 'olive', 'lime', 'aqua', 'teal', 'navy',
	        'fuchsia', 'purple', 'silver', 'orange', 'gold', 'pink', 'brown',

	        // Extended CSS colors
	        'aliceblue','antiquewhite','aquamarine','azure','beige','bisque','blanchedalmond','blueviolet','burlywood',
	        'cadetblue','chartreuse','chocolate','coral','cornflowerblue','cornsilk','crimson','darkblue','darkcyan',
	        'darkgoldenrod','darkgray','darkgreen','darkgrey','darkkhaki','darkmagenta','darkolivegreen','darkorange',
	        'darkorchid','darkred','darksalmon','darkseagreen','darkslateblue','darkslategray','darkslategrey','darkturquoise',
	        'darkviolet','deeppink','deepskyblue','dimgray','dimgrey','dodgerblue','firebrick','floralwhite','forestgreen',
	        'gainsboro','ghostwhite','goldenrod','greenyellow','honeydew','hotpink','indianred','indigo','ivory','khaki',
	        'lavender','lavenderblush','lawngreen','lemonchiffon','lightblue','lightcoral','lightcyan','lightgoldenrodyellow',
	        'lightgray','lightgreen','lightgrey','lightpink','lightsalmon','lightseagreen','lightskyblue','lightslategray',
	        'lightslategrey','lightsteelblue','lightyellow','limegreen','linen','mediumaquamarine','mediumblue','mediumorchid',
	        'mediumpurple','mediumseagreen','mediumslateblue','mediumspringgreen','mediumturquoise','mediumvioletred','midnightblue',
	        'mintcream','mistyrose','moccasin','navajowhite','oldlace','olivedrab','orangered','orchid','palegoldenrod','palegreen',
	        'paleturquoise','palevioletred','papayawhip','peachpuff','peru','pink','plum','powderblue','rosybrown','royalblue',
	        'saddlebrown','salmon','sandybrown','seagreen','seashell','sienna','skyblue','slateblue','slategray','slategrey',
	        'snow','springgreen','steelblue','tan','thistle','tomato','turquoise','violet','wheat','whitesmoke','yellowgreen'
	    ];
	}

	private function colorBlock($color)
	{
	    if (!str_starts_with($color, '#')) {
	        // Convert named color to RGB
	        $rgb = $this->namedColorToRgb($color);
	        if (!$rgb) return '';
	        [$r, $g, $b] = $rgb;
	    } else {
	        $hex = ltrim($color, '#');
	        if (strlen($hex) === 3) {
	            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
	        }
	        $r = hexdec(substr($hex,0,2));
	        $g = hexdec(substr($hex,2,2));
	        $b = hexdec(substr($hex,4,2));
	    }

	    return "\033[48;2;{$r};{$g};{$b}m   \033[0m";
	}

	private function namedColorToRgb($name)
	{
	    $colors = [
	        'aliceblue'=>[240,248,255],'antiquewhite'=>[250,235,215],'aqua'=>[0,255,255],'aquamarine'=>[127,255,212],
	        'azure'=>[240,255,255],'beige'=>[245,245,220],'bisque'=>[255,228,196],'black'=>[0,0,0],'blanchedalmond'=>[255,235,205],
	        'blue'=>[0,0,255],'blueviolet'=>[138,43,226],'brown'=>[165,42,42],'burlywood'=>[222,184,135],'cadetblue'=>[95,158,160],
	        'chartreuse'=>[127,255,0],'chocolate'=>[210,105,30],'coral'=>[255,127,80],'cornflowerblue'=>[100,149,237],'cornsilk'=>[255,248,220],
	        'crimson'=>[220,20,60],'cyan'=>[0,255,255],'darkblue'=>[0,0,139],'darkcyan'=>[0,139,139],'darkgoldenrod'=>[184,134,11],
	        'darkgray'=>[169,169,169],'darkgrey'=>[169,169,169],'darkgreen'=>[0,100,0],'darkkhaki'=>[189,183,107],'darkmagenta'=>[139,0,139],
	        'darkolivegreen'=>[85,107,47],'darkorange'=>[255,140,0],'darkorchid'=>[153,50,204],'darkred'=>[139,0,0],'darksalmon'=>[233,150,122],
	        'darkseagreen'=>[143,188,143],'darkslateblue'=>[72,61,139],'darkslategray'=>[47,79,79],'darkslategrey'=>[47,79,79],'darkturquoise'=>[0,206,209],
	        'darkviolet'=>[148,0,211],'deeppink'=>[255,20,147],'deepskyblue'=>[0,191,255],'dimgray'=>[105,105,105],'dimgrey'=>[105,105,105],
	        'dodgerblue'=>[30,144,255],'firebrick'=>[178,34,34],'floralwhite'=>[255,250,240],'forestgreen'=>[34,139,34],'fuchsia'=>[255,0,255],
	        'gainsboro'=>[220,220,220],'ghostwhite'=>[248,248,255],'gold'=>[255,215,0],'goldenrod'=>[218,165,32],'gray'=>[128,128,128],
	        'grey'=>[128,128,128],'green'=>[0,128,0],'greenyellow'=>[173,255,47],'honeydew'=>[240,255,240],'hotpink'=>[255,105,180],
	        'indianred'=>[205,92,92],'indigo'=>[75,0,130],'ivory'=>[255,255,240],'khaki'=>[240,230,140],'lavender'=>[230,230,250],
	        'lavenderblush'=>[255,240,245],'lawngreen'=>[124,252,0],'lemonchiffon'=>[255,250,205],'lightblue'=>[173,216,230],'lightcoral'=>[240,128,128],
	        'lightcyan'=>[224,255,255],'lightgoldenrodyellow'=>[250,250,210],'lightgray'=>[211,211,211],'lightgreen'=>[144,238,144],'lightgrey'=>[211,211,211],
	        'lightpink'=>[255,182,193],'lightsalmon'=>[255,160,122],'lightseagreen'=>[32,178,170],'lightskyblue'=>[135,206,250],'lightslategray'=>[119,136,153],
	        'lightslategrey'=>[119,136,153],'lightsteelblue'=>[176,196,222],'lightyellow'=>[255,255,224],'lime'=>[0,255,0],'limegreen'=>[50,205,50],
	        'linen'=>[250,240,230],'magenta'=>[255,0,255],'maroon'=>[128,0,0],'mediumaquamarine'=>[102,205,170],'mediumblue'=>[0,0,205],
	        'mediumorchid'=>[186,85,211],'mediumpurple'=>[147,112,219],'mediumseagreen'=>[60,179,113],'mediumslateblue'=>[123,104,238],'mediumspringgreen'=>[0,250,154],
	        'mediumturquoise'=>[72,209,204],'mediumvioletred'=>[199,21,133],'midnightblue'=>[25,25,112],'mintcream'=>[245,255,250],'mistyrose'=>[255,228,225],
	        'moccasin'=>[255,228,181],'navajowhite'=>[255,222,173],'navy'=>[0,0,128],'oldlace'=>[253,245,230],'olive'=>[128,128,0],
	        'olivedrab'=>[107,142,35],'orange'=>[255,165,0],'orangered'=>[255,69,0],'orchid'=>[218,112,214],'palegoldenrod'=>[238,232,170],
	        'palegreen'=>[152,251,152],'paleturquoise'=>[175,238,238],'palevioletred'=>[219,112,147],'papayawhip'=>[255,239,213],'peachpuff'=>[255,218,185],
	        'peru'=>[205,133,63],'pink'=>[255,192,203],'plum'=>[221,160,221],'powderblue'=>[176,224,230],'purple'=>[128,0,128],
	        'rebeccapurple'=>[102,51,153],'red'=>[255,0,0],'rosybrown'=>[188,143,143],'royalblue'=>[65,105,225],'saddlebrown'=>[139,69,19],
	        'salmon'=>[250,128,114],'sandybrown'=>[244,164,96],'seagreen'=>[46,139,87],'seashell'=>[255,245,238],'sienna'=>[160,82,45],
	        'silver'=>[192,192,192],'skyblue'=>[135,206,235],'slateblue'=>[106,90,205],'slategray'=>[112,128,144],'slategrey'=>[112,128,144],
	        'snow'=>[255,250,250],'springgreen'=>[0,255,127],'steelblue'=>[70,130,180],'tan'=>[210,180,140],'teal'=>[0,128,128],
	        'thistle'=>[216,191,216],'tomato'=>[255,99,71],'turquoise'=>[64,224,208],'violet'=>[238,130,238],'wheat'=>[245,222,179],
	        'white'=>[255,255,255],'whitesmoke'=>[245,245,245],'yellow'=>[255,255,0],'yellowgreen'=>[154,205,50]
	    ];

	    $name = strtolower($name);

	    return $colors[$name] ?? null;
	}

    private function randomHexColor()
    {
        return sprintf(
        	"#%02X%02X%02X",
        	rand(0, 255),
        	rand(0, 255),
        	rand(0, 255)
        );
    }

    private function generateUniqueColor($primary, $usedColors, $key)
	{
	    // Ensure primary color is resolved to hex for math
        $primary = $this->resolveColorToHex($primary); 

	    $attempt = 0;
	    do {
	        [$r, $g, $b] = $this->hexToRgb($primary);
	        [$h, $s, $l] = $this->rgbToHsl($r, $g, $b);

	        // Define hue offset ranges per key
	        switch ($key) {
	            case 'success': $minOffset = 90;  $maxOffset = 150; break;
	            case 'warning': $minOffset = 30;  $maxOffset = 60;  break;
	            case 'danger':  $minOffset = 200; $maxOffset = 260; break;
	            case 'info':    $minOffset = 150; $maxOffset = 210; break;
	            default:       $minOffset = 0;   $maxOffset = 360; break;
	        }

	        // Apply a random hue offset within range
	        $offset = rand($minOffset, $maxOffset);
	        $h = ($h + $offset) % 360;

	        // Slight tweaks to saturation/lightness to increase distinction
	        $s = min(1, max(0.3, $s + rand(-10, 10)/100));
	        $l = min(0.9, max(0.15, $l + rand(-10, 10)/100));

	        [$r, $g, $b] = $this->hslToRgb($h, $s, $l);
	        $color = sprintf("#%02X%02X%02X", $r, $g, $b);
	        $attempt++;
	    } while (in_array($color, $usedColors) && $attempt < 20);

	    // Fallback: fully random if still colliding
	    if (in_array($color, $usedColors)) {
	        do {
	            $color = $this->randomHexColor();
	        } while (in_array($color, $usedColors));
	    }

	    return $color;
	}

    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
        	hexdec(substr($hex,0,2)),
        	hexdec(substr($hex,2,2)),
        	hexdec(substr($hex,4,2))
        ];
    }

    private function rgbToHsl($r, $g, $b)
    {
        $r /= 255; $g /= 255; $b /= 255;
        $max = max($r,$g,$b); $min = min($r,$g,$b);
        $h = $s = $l = ($max + $min)/2;

        if($max === $min){
            $h = $s = 0;
        } else {
            $d = $max - $min;
            
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            
            switch ($max) {
                case $r: $h = (($g - $b) / $d + ($g < $b ? 6 : 0)); break;
                case $g: $h = (($b - $r) / $d + 2); break;
                case $b: $h = (($r - $g) / $d + 4); break;
            }

            $h *= 60;
        }
        return [$h,$s,$l];
    }

    private function hslToRgb($h, $s, $l)
    {
        $h /= 360;
        $r = $g = $b = $l;

        if ($s != 0) {
            $q = $l < 0.5 ? $l*(1+$s) : $l + $s - $l*$s;
            $p = 2 * $l - $q;
            $r = $this->hue2rgb($p, $q, $h + 1/3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1/3);
        }
        return [
        	round($r*255),
        	round($g*255),
        	round($b*255)
        ];
    }

    private function hue2rgb($p,$q,$t){
        if ($t < 0) $t+=1;
        if ($t > 1) $t-=1;
        if ($t < 1/6) return $p + ($q-$p)*6*$t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q-$p) * (2/3-$t) *6;
        
        return $p;
    }

    private function generateShades($hexColor)
    {
        $hexColor = ltrim($hexColor,'#');

        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0]
            	.$hexColor[0]
            	.$hexColor[1]
            	.$hexColor[1]
            	.$hexColor[2]
            	.$hexColor[2];
        }
        
        $shades=[];
        $r = hexdec(substr($hexColor,0,2));
        $g = hexdec(substr($hexColor,2,2));
        $b = hexdec(substr($hexColor,4,2));

        for ($i = 0; $i <= 9; $i++) {
            $t = $i * 0.1;
            $newR = (int)($r + (255 - $r) * $t);
            $newG = (int)($g + (255 - $g) * $t);
            $newB = (int)($b + (255 - $b) * $t);
            $shades[] = sprintf("#%02X%02X%02X",$newR,$newG,$newB);
        }

        return $shades;
    }

    private function adjustColorBrightness($color, $factor)
	{
        // This method assumes $color is a valid hex code
	    $hex = ltrim($color, '#');
	    if (strlen($hex) === 3) {
	        $hex = preg_replace('/(.)/', '$1$1', $hex);
	    }

	    [$r, $g, $b] = [
	        hexdec(substr($hex, 0, 2)),
	        hexdec(substr($hex, 2, 2)),
	        hexdec(substr($hex, 4, 2)),
	    ];

	    // factor > 0 → lighten, factor < 0 → darken
	    $r = max(0, min(255, $r + (255 * $factor)));
	    $g = max(0, min(255, $g + (255 * $factor)));
	    $b = max(0, min(255, $b + (255 * $factor)));

	    return sprintf("#%02X%02X%02X", $r, $g, $b);
	}

    private function arrayToPhp(array $array, int $level=0)
    {
        $lines = ["["];
        
        $indent = str_repeat('    ', $level);

        foreach($array as $key => $value) {
            $keyStr = is_int($key) ? $key : "'$key'";
            
            if (is_array($value)) {
                $lines[] = "$indent    $keyStr => ".$this->arrayToPhp(
                	$value,$level+1
                ).",";
            } else {
                $lines[] = "$indent    $keyStr => '$value',";
            }
        }

        $lines[] = "$indent]";

        return implode("\n",$lines);
    }
}