@if(filled($metaTitle ?? $title ?? null))
    <meta name="title" content="{{ $metaTitle ?? $title }}">
    <meta property="og:title" content="{{ $metaTitle ?? $title }}">
    <meta name="twitter:title" content="{{ $metaTitle ?? $title }}">
@endif

@if(filled($metaDescription ?? $description ?? null))
    <meta name="description" content="{{ $metaDescription ?? $description }}">
    <meta property="og:description" content="{{ $metaDescription ?? $description }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? $description }}">
@endif

@if(filled($metaKeywords ?? $keywords ?? null))
    <meta name="keywords" content="{{ $metaKeywords ?? $keywords }}">
@endif

@if(!empty($image))
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:image" content="{{ $image }}">
@endif

@if(!empty($canonicalUrl))
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endif

<meta property="og:type" content="{{ $type ?? 'website' }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="twitter:card" content="{{ $twitterCard ?? 'summary' }}">