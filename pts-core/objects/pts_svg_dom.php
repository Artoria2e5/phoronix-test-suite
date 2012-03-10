<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2012, Phoronix Media
	Copyright (C) 2011 - 2012, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class pts_svg_dom
{
	protected $dom;
	protected $svg;

	public function __construct($width, $height)
	{
		$dom = new DOMImplementation();
		$dtd = $dom->createDocumentType('svg', '-//W3C//DTD SVG 1.1//EN', 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd');
		$this->dom = $dom->createDocument(null, null, $dtd);
		$this->dom->formatOutput = PTS_IS_CLIENT;

		$pts_comment = $this->dom->createComment(pts_title(true) . ' [ http://www.phoronix-test-suite.com/ ]');
		$this->dom->appendChild($pts_comment);

		$this->svg = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');
		$this->svg->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$this->svg->setAttribute('version', '1.1');
		$this->svg->setAttribute('font-family', 'sans-serif');
		$this->svg->setAttribute('viewbox', '0 0 ' . $width . ' ' . $height);
		$this->svg->setAttribute('width', $width);
		$this->svg->setAttribute('height', $height);

		$this->dom->appendChild($this->svg);
	}
	public function render_image($save_as = null, &$format = null)
	{
		// XXX: Alias for output. With PTS 3.8 this is just here for API compatibility with OpenBenchmarking.org.
		$this->output($save_as, $format);
	}
	public function output($save_as = null, &$format = null)
	{
		$output_format = 'SVG';
		if(isset($_SERVER['HTTP_USER_AGENT']) || isset($_REQUEST['force_format']))
		{
			static $browser_renderer = null;

			if(isset($_REQUEST['force_format']))
			{
				// Don't nest the force_format within the browser_renderer null check in case its overriden by OpenBenchmarking.org dynamically
				$output_format = $_REQUEST['force_format'];
			}
			else if($browser_renderer == null)
			{
				$output_format = pts_render::renderer_compatibility_check($_SERVER['HTTP_USER_AGENT']);
			}
			else
			{
				$output_format = $browser_renderer;
			}
		}
		$format = $output_format;

		switch($output_format)
		{
			case 'JPEG':
				$output = pts_svg_dom_gd::svg_dom_to_gd($this->dom, 'JPEG');
				$output_format = 'jpg';
				break;
			case 'PNG':
				$output = pts_svg_dom_gd::svg_dom_to_gd($this->dom, 'PNG');
				$output_format = 'png';
				break;
			case 'SVG':
			default:
				$output = $this->save_xml();
				$output_format = 'svg';
				break;
		}

		if($save_as)
		{
			return file_put_contents(str_replace('BILDE_EXTENSION', $output_format, $save_as), $output);
		}
		else
		{
			return $output;
		}
	}
	public function save_xml()
	{
		return $this->dom->saveXML();
	}
	public static function sanitize_hex($hex)
	{
		return $hex; // don't shorten it right now until the gd code can handle shortened hex codes
		$hex = preg_replace('/(?<=^#)([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3\z/i', '\1\2\3', $hex);

		return strtolower($hex);
	}
	public function draw_svg_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1, $extra_elements = null)
	{
		$attributes = array('x1' => $start_x, 'y1' => $start_y, 'x2' => $end_x, 'y2' => $end_y, 'stroke' => $color, 'stroke-width' => $line_width);

		if($extra_elements != null)
		{
			$attributes = array_merge($attributes, $extra_elements);
		}

		$this->add_element('line', $attributes);
	}
	public function draw_svg_arc($center_x, $center_y, $radius, $offset_percent, $percent, $attributes)
	{
		$deg = ($percent * 360);
		$offset_deg = ($offset_percent * 360);
		$arc = $percent > 0.5 ? 1 : 0;

		$p1_x = round(cos(deg2rad($offset_deg)) * $radius) + $center_x;
		$p1_y = round(sin(deg2rad($offset_deg)) * $radius) + $center_y;
		$p2_x = round(cos(deg2rad($offset_deg + $deg)) * $radius) + $center_x;
		$p2_y = round(sin(deg2rad($offset_deg + $deg)) * $radius) + $center_y;

		$attributes['d'] = "M$center_x,$center_y L$p1_x,$p1_y A$radius,$radius 0 $arc,1 $p2_x,$p2_y Z";
		$this->add_element('path', $attributes);
	}
	public function add_element($element_type, $attributes = array())
	{
		$el = $this->dom->createElement($element_type);

		if(isset($attributes['xlink:href']) && $attributes['xlink:href'] != null && !in_array($element_type, array('image', 'a')))
		{
			$link = $this->dom->createElement('a');
			$link->setAttribute('xlink:href', $attributes['xlink:href']);
			$link->setAttribute('xlink:show', 'new');
			$link->appendChild($el);
			$this->svg->appendChild($link);
			unset($attributes['xlink:href']);
		}
		else
		{
			$this->svg->appendChild($el);
		}

		foreach($attributes as $name => $value)
		{
			$el->setAttribute($name, $value);
		}
	}
	public function add_text_element($text_string, $attributes)
	{
		$el = $this->dom->createElement('text');
		$text_node = $this->dom->createTextNode($text_string);
		$el->appendChild($text_node);

		if(isset($attributes['xlink:href']) && $attributes['xlink:href'] != null)
		{
			$link = $this->dom->createElement('a');
			$link->setAttribute('xlink:href', $attributes['xlink:href']);
			$link->setAttribute('xlink:show', 'new');
			$link->appendChild($el);
			$this->svg->appendChild($link);
			unset($attributes['xlink:href']);
		}
		else
		{
			$this->svg->appendChild($el);
		}

		foreach($attributes as $name => $value)
		{
			$el->setAttribute($name, $value);
		}
	}
	public function draw_rectangle_gradient($x1, $y1, $width, $height, $color, $next_color)
	{
		static $gradient_count = 1;

		$gradient = $this->dom->createElement('linearGradient');
		$gradient->setAttribute('id', 'g_' . $gradient_count);
		$gradient->setAttribute('x1', '0%');
		$gradient->setAttribute('y1', '0%');
		$gradient->setAttribute('x2', '100%');
		$gradient->setAttribute('y2', '0%');

		$stop = $this->dom->createElement('stop');
		$stop->setAttribute('offset', '0%');
		$stop->setAttribute('style', 'stop-color: ' . $color .'; stop-opacity: 1;');
		$gradient->appendChild($stop);

		$stop = $this->dom->createElement('stop');
		$stop->setAttribute('offset', '100%');
		$stop->setAttribute('style', 'stop-color: ' . $next_color .'; stop-opacity: 1;');
		$gradient->appendChild($stop);

		$defs = $this->dom->createElement('defs');
		$defs->appendChild($gradient);
		$this->svg->appendChild($defs);

		$rect = $this->dom->createElement('rect');
		$rect->setAttribute('x', $x1);
		$rect->setAttribute('y', $y1);
		$rect->setAttribute('width', $width);
		$rect->setAttribute('height', $height);
		//$rect->setAttribute('fill', $background_color);
		$rect->setAttribute('style', 'fill:url(#g_' .  $gradient_count . ')');
		$gradient_count++;

		$this->svg->appendChild($rect);
	}
	public static function html_embed_code($file_name, $file_type = 'SVG', $attributes = null, $is_xsl = false)
	{
		$attributes = pts_arrays::to_array($attributes);
		$file_name = str_replace('BILDE_EXTENSION', strtolower($file_type), $file_name);

		switch($file_type)
		{
			case 'SVG':
				$attributes['data'] = $file_name;

				if($is_xsl)
				{
					$html = '<object type="image/svg+xml">';

					foreach($attributes as $option => $value)
					{
						$html .= '<xsl:attribute name="' . $option . '">' . $value . '</xsl:attribute>';
					}
					$html .= '</object>';
				}
				else
				{
					$html = '<object type="image/svg+xml"';

					foreach($attributes as $option => $value)
					{
						$html .= $option . '="' . $value . '" ';
					}
					$html .= '/>';
				}
				break;
			default:
				$attributes['src'] = $file_name;

				if($is_xsl)
				{
					$html = '<img>';

					foreach($attributes as $option => $value)
					{
						$html .= '<xsl:attribute name="' . $option . '">' . $value . '</xsl:attribute>';
					}
					$html .= '</img>';
				}
				else
				{
					$html = '<img ';

					foreach($attributes as $option => $value)
					{
						$html .= $option . '="' . $value . '" ';
					}
					$html .= '/>';
				}
				break;
		}

		return $html;
	}
	public static function estimate_text_dimensions($text_string, $font_size)
	{
		$box_height = ceil(0.76 * $font_size);
		$box_width = ceil((0.76 * strlen($text_string) * $font_size) - ceil(strlen($text_string) * 1.05));

		// Width x Height
		return array($box_width, $box_height);
	}
	public static function embed_png_image($png_img_file)
	{
		return 'data:image/png;base64,' . base64_encode(file_get_contents($png_img_file));
	}
}

?>
