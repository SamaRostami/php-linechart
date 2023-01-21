<?xml version="1.0" encoding="UTF-8"?>
<svg viewBox="0 0 <?= $widthSVG ?> <?= $heightSVG ?>" width="<?= $widthSVG ?>" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient x2="0" y1="1" id="color-<?= $id ?>">
<?php foreach ($colors as $color): ?>
            <stop stop-color="<?= $color->code ?>" offset="<?= $color->offset ?>"></stop>
<?php endforeach ?>
        </linearGradient>
        <mask id="linechart-<?= $id ?>">
            <polyline
                stroke="#fff"
                stroke-width="<?= $strokeWidth ?>"
                fill="transparent"
                stroke-linecap="round"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
                transform="scale(<?= $widthScale ?> -<?= $heightScale ?>) translate(<?= $widthTranslate ?> -<?= $heightTranslate ?>)"
                points="<?= $points ?>">
            </polyline>
        </mask>
    </defs>
    <g>
        <rect
            width="<?= $widthSVG ?>"
            height="<?= $heightSVG ?>"
            fill="url(#color-<?= $id ?>)"
            mask="url(#linechart-<?= $id ?>)"
        />
    </g>
</svg>
