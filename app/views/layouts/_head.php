<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use app\assets\AppAsset;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(
    ['charset' => Yii::$app->charset],
    'charset',
);
$this->registerMetaTag(
    [
        'name' => 'viewport',
        'content' => 'width=device-width, initial-scale=1',
    ],
);
if (!empty($this->params['meta_description'])) {
    $this->registerMetaTag(
        [
            'name' => 'description',
            'content' => $this->params['meta_description'],
        ],
    );
}
if (!empty($this->params['meta_keywords'])) {
    $this->registerMetaTag(
        [
            'name' => 'keywords',
            'content' => $this->params['meta_keywords'],
        ],
    );
}
// Open Graph / Twitter Card metadata so the link unfurls with a preview card
// when shared in texts, chat apps, and social media.
$host = Yii::$app->request->hostInfo;
$ogImage = $host . '/images/og-image.png';
$ogTitle = 'Mission Deck — UAS Fleet Mission Control';
$ogDescription = 'Plan drone missions on an interactive map, monitor a live fleet, and track flight telemetry in real time.';
foreach ([
    ['property' => 'og:type', 'content' => 'website'],
    ['property' => 'og:title', 'content' => $ogTitle],
    ['property' => 'og:description', 'content' => $ogDescription],
    ['property' => 'og:url', 'content' => $host],
    ['property' => 'og:image', 'content' => $ogImage],
    ['property' => 'og:image:width', 'content' => '1200'],
    ['property' => 'og:image:height', 'content' => '630'],
    ['name' => 'twitter:card', 'content' => 'summary_large_image'],
    ['name' => 'twitter:title', 'content' => $ogTitle],
    ['name' => 'twitter:description', 'content' => $ogDescription],
    ['name' => 'twitter:image', 'content' => $ogImage],
] as $tag) {
    $this->registerMetaTag($tag, $tag['property'] ?? $tag['name']);
}

// Favicon: the app's drone logo (SVG, supported by modern browsers).
$this->registerLinkTag([
    'rel' => 'icon',
    'type' => 'image/svg+xml',
    'href' => Yii::getAlias('@web/images/mission-deck-logo.svg'),
]);
