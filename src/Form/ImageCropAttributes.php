<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use InvalidArgumentException;
use Nowo\UiKitBundle\Form\ImageCrop;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function class_exists;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_finite;
use function is_float;
use function is_int;
use function is_string;
use function json_encode;
use function lcfirst;
use function number_format;
use function preg_match;
use function rtrim;
use function sprintf;
use function str_replace;
use function strval;
use function trim;
use function ucwords;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Attributes of UiKit 2.43's image crop step (`data-nowo-ui-image-crop*`) for an `<input type="file">`: a picked image is
 * cropped in the browser (UiKit's `image-crop` script) and the crop is what the form uploads. FormKit only prints the
 * attribute contract; the server still validates the upload as an ordinary file.
 *
 * With UiKit ^2.43 installed this delegates to `Nowo\UiKitBundle\Form\ImageCrop::attributes()` (validation and translated
 * labels). Without it, the same options are validated with the same rules and printed here; `-labels` is printed only
 * for explicit `labels`, otherwise the runtime uses its English texts.
 *
 * Options: `shape`, `aspect`, `max`, `min`, `format`, `quality`, `max_bytes`, `bake_circle`, `preview`, `labels`
 * (see docs/USAGE.md "Image crop on file inputs").
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class ImageCropAttributes
{
    public const ATTRIBUTE = 'data-nowo-ui-image-crop';

    public const SHAPES = ['square', 'circle'];

    public const FORMATS = ['auto', 'png', 'webp', 'jpeg'];

    public const MAX_RANGE = [16, 8192];

    public const DEFAULT_MAX = 1024;

    public const KEYS = ['shape', 'aspect', 'max', 'min', 'format', 'max_bytes', 'quality', 'bake_circle', 'preview', 'labels'];

    public const LABEL_KEYS = ['title', 'instructions', 'stage', 'zoom', 'zoom_status', 'apply', 'cancel', 'close', 'too_large', 'optimizing'];

    /**
     * Whether UiKit's `ImageCrop` (2.43+) is available to delegate to.
     */
    public static function uiKitAvailable(): bool
    {
        return class_exists(ImageCrop::class);
    }

    /**
     * Whether {@see attributes()} delegates to UiKit's `ImageCrop` for this `$uiKit` setting.
     *
     * @param bool|null $uiKit null = delegate to UiKit when it is available, false = never (FormKit's own printer)
     */
    public static function delegates(?bool $uiKit = null): bool
    {
        return ($uiKit ?? true) && self::uiKitAvailable();
    }

    /**
     * Validates the options through the same path that {@see attributes()} prints them with: UiKit's
     * `ImageCrop::normalize()` when it delegates (so a newer UiKit's rules and messages apply), FormKit's own
     * {@see normalize()} otherwise.
     *
     * @param array<mixed> $options
     * @param bool|null $uiKit same meaning as in {@see attributes()}
     *
     * @throws InvalidArgumentException on an unknown key or a bad value
     */
    public static function validate(array $options, ?bool $uiKit = null): void
    {
        if (self::delegates($uiKit)) {
            ImageCrop::normalize($options);

            return;
        }

        self::normalize($options);
    }

    /**
     * Attributes to print on the input.
     *
     * @param array<mixed> $options
     * @param bool|null $uiKit null = delegate to UiKit when it is available, false = never (FormKit's own printer)
     *
     * @throws InvalidArgumentException on an unknown key or a bad value
     *
     * @return array<string, string>
     */
    public static function attributes(array $options = [], ?TranslatorInterface $translator = null, ?bool $uiKit = null): array
    {
        if (self::delegates($uiKit)) {
            return ImageCrop::attributes($options, $translator);
        }

        $config     = self::normalize($options);
        $attributes = [
            self::ATTRIBUTE             => '',
            self::ATTRIBUTE . '-shape'  => $config['shape'],
            self::ATTRIBUTE . '-aspect' => $config['aspect'],
            self::ATTRIBUTE . '-max'    => (string) $config['max'],
            self::ATTRIBUTE . '-format' => $config['format'],
        ];
        if ($config['min'] !== 1) {
            $attributes[self::ATTRIBUTE . '-min'] = (string) $config['min'];
        }
        if ($config['max_bytes'] !== null) {
            $attributes[self::ATTRIBUTE . '-max-bytes'] = (string) $config['max_bytes'];
        }
        if ($config['format'] !== 'png') {
            $attributes[self::ATTRIBUTE . '-quality'] = rtrim(rtrim(number_format($config['quality'], 3, '.', ''), '0'), '.');
        }
        if ($config['bake_circle']) {
            $attributes[self::ATTRIBUTE . '-bake-circle'] = 'true';
        }
        if ($config['preview'] !== null) {
            $attributes[self::ATTRIBUTE . '-preview'] = $config['preview'];
        }
        if ($config['labels'] !== []) {
            $labels = [];
            foreach ($config['labels'] as $key => $text) {
                $labels[lcfirst(str_replace('_', '', ucwords($key, '_')))] = $text;
            }
            $attributes[self::ATTRIBUTE . '-labels'] = json_encode($labels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $attributes;
    }

    /**
     * The validated options with their defaults (same rules as UiKit 2.43's `ImageCrop::normalize()`), used when FormKit
     * prints the attributes itself (no UiKit to delegate to).
     *
     * @param array<mixed> $options
     *
     * @throws InvalidArgumentException
     *
     * @return array{shape: string, aspect: string, max: int, min: int, format: string, max_bytes: ?int, quality: float, bake_circle: bool, preview: ?string, labels: array<string, string>}
     */
    public static function normalize(array $options): array
    {
        $unknown = array_diff(array_map(strval(...), array_keys($options)), self::KEYS);
        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf('Unknown image crop option(s) "%s" (%s).', implode('", "', $unknown), implode(', ', self::KEYS)));
        }
        $shape = $options['shape'] ?? 'square';
        if (!in_array($shape, self::SHAPES, true)) {
            throw new InvalidArgumentException('The image crop shape is "square" or "circle".');
        }
        $format = $options['format'] ?? 'auto';
        if (!in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException('The image crop format is "auto", "png", "webp" or "jpeg".');
        }
        $maxBytes = $options['max_bytes'] ?? null;
        if ($maxBytes !== null && (!is_int($maxBytes) || $maxBytes < 1)) {
            throw new InvalidArgumentException('The image crop max_bytes is a positive integer (bytes) or null.');
        }
        $max = $options['max'] ?? self::DEFAULT_MAX;
        if (!is_int($max) || $max < self::MAX_RANGE[0] || $max > self::MAX_RANGE[1]) {
            throw new InvalidArgumentException(sprintf('The image crop max is an integer from %d to %d.', ...self::MAX_RANGE));
        }
        $min = $options['min'] ?? 1;
        if (!is_int($min) || $min < 1 || $min > self::MAX_RANGE[1]) {
            throw new InvalidArgumentException(sprintf('The image crop min is an integer from 1 to %d.', self::MAX_RANGE[1]));
        }
        $quality = $options['quality'] ?? 0.92;
        if ((!is_int($quality) && !is_float($quality)) || !is_finite((float) $quality) || $quality < 0 || $quality > 1) {
            throw new InvalidArgumentException('The image crop quality is a number from 0 to 1.');
        }
        $bake = $options['bake_circle'] ?? false;
        if (!is_bool($bake)) {
            throw new InvalidArgumentException('The image crop bake_circle is a boolean.');
        }
        if ($bake && ($shape !== 'circle' || $format === 'jpeg')) {
            throw new InvalidArgumentException('bake_circle needs the circle shape and a format with transparency (png or webp).');
        }
        $preview = $options['preview'] ?? null;
        if ($preview !== null && (!is_string($preview) || preg_match('/^[A-Za-z][\w:.-]{0,127}$/', $preview) !== 1)) {
            throw new InvalidArgumentException('The image crop preview is the id of an <img>.');
        }
        $labels = $options['labels'] ?? [];
        if (!is_array($labels) || array_diff(array_map(strval(...), array_keys($labels)), self::LABEL_KEYS) !== [] || array_filter($labels, static fn (mixed $label): bool => !is_string($label)) !== []) {
            throw new InvalidArgumentException(sprintf('The image crop labels are strings keyed %s.', implode(', ', self::LABEL_KEYS)));
        }

        /* @var array<string, string> $labels */
        return [
            'shape'       => $shape,
            'aspect'      => $shape === 'circle' ? '1' : self::aspect($options['aspect'] ?? 1),
            'max'         => $max,
            'min'         => $min,
            'format'      => $format,
            'max_bytes'   => $maxBytes,
            'quality'     => (float) $quality,
            'bake_circle' => $bake,
            'preview'     => $preview,
            'labels'      => $labels,
        ];
    }

    /**
     * The aspect as the runtime reads it: `free`, a ratio `w/h` or a positive number.
     *
     * @throws InvalidArgumentException
     */
    public static function aspect(mixed $aspect): string
    {
        if ($aspect === 'free') {
            return 'free';
        }
        if ((is_int($aspect) || is_float($aspect)) && $aspect > 0 && is_finite((float) $aspect)) {
            return rtrim(rtrim(number_format((float) $aspect, 6, '.', ''), '0'), '.');
        }
        if (is_string($aspect) && preg_match('/^(\d+(?:\.\d+)?)\s*[\/:]\s*(\d+(?:\.\d+)?)$/', trim($aspect), $match) === 1 && (float) $match[1] > 0 && (float) $match[2] > 0) {
            return $match[1] . '/' . $match[2];
        }
        if (is_string($aspect) && preg_match('/^\d+(?:\.\d+)?$/', trim($aspect)) === 1 && (float) $aspect > 0) {
            return trim($aspect);
        }

        throw new InvalidArgumentException('The image crop aspect is "free", a positive number or a ratio such as "16/9".');
    }
}
